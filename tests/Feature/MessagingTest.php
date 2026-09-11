<?php

namespace Tests\Feature;

use App\Enums\MessagingMode;
use App\Models\Alter;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\System;
use App\Support\Messaging;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_personal_systems_talk_alter_to_alter(): void
    {
        $kai = Alter::factory()->create();
        $sora = Alter::factory()->create(['handle' => 'sora']);

        $this->actingAsFront($kai)->post('/conversations', ['handle' => 'sora']);

        $conversation = Conversation::sole();
        $this->assertSame(MessagingMode::Personal, $conversation->effective_mode);
        $this->assertCount(2, $conversation->participants);
    }

    public function test_a_shared_system_exposes_one_correspondent(): void
    {
        $shared = $this->sharedSystem('Constellation');
        $kai = Alter::factory()->for($shared)->create();
        $nori = Alter::factory()->for($shared)->create();
        $sora = Alter::factory()->create(['handle' => 'sora']);

        // Deux alters du même système partagé écrivent : une seule conversation.
        $this->actingAsFront($kai)->post('/conversations', ['handle' => 'sora']);
        $this->actingAsFront($nori)->post('/conversations', ['handle' => 'sora']);

        $this->assertSame(1, Conversation::count());
        $this->assertSame(MessagingMode::Shared, Conversation::sole()->effective_mode);
    }

    public function test_the_conversation_title_is_the_system_name_never_an_alter(): void
    {
        $shared = $this->sharedSystem('Constellation');
        $kai = Alter::factory()->for($shared)->create(['name' => 'Kai', 'handle' => 'kai']);
        $sora = Alter::factory()->create(['handle' => 'sora']);

        $this->actingAsFront($kai)->post('/conversations', ['handle' => 'sora']);
        $conversation = Conversation::sole();
        $this->actingAsFront($kai)->post("/conversations/{$conversation->uuid}/messages", [
            'content' => 'Bonjour',
        ]);

        $this->actingAsFront($sora)
            ->get('/conversations')
            ->assertInertia(fn ($page) => $page->where('conversations.0.title', 'Constellation'))
            ->assertDontSee('Kai')
            ->assertDontSee('kai');

        $this->actingAsFront($sora)
            ->get("/conversations/{$conversation->uuid}")
            ->assertInertia(fn ($page) => $page
                ->where('conversation.title', 'Constellation')
                ->where('messages.0.author_name', 'Constellation')
                ->where('messages.0.author_alter', null));
    }

    public function test_the_author_option_shows_the_alter_without_renaming_the_conversation(): void
    {
        $shared = $this->sharedSystem('Constellation', showAuthor: true);
        $kai = Alter::factory()->for($shared)->create(['name' => 'Kai']);
        $sora = Alter::factory()->create(['handle' => 'sora']);

        $this->actingAsFront($kai)->post('/conversations', ['handle' => 'sora']);
        $conversation = Conversation::sole();
        $this->actingAsFront($kai)->post("/conversations/{$conversation->uuid}/messages", [
            'content' => 'Bonjour',
        ]);

        $this->actingAsFront($sora)
            ->get("/conversations/{$conversation->uuid}")
            ->assertInertia(fn ($page) => $page
                ->where('conversation.title', 'Constellation')
                ->where('messages.0.author_name', 'Constellation')
                ->where('messages.0.author_alter.name', 'Kai'));
    }

    public function test_the_four_combinations_share_one_path(): void
    {
        $personal = Alter::factory()->create(['handle' => 'perso']);
        $shared = Alter::factory()->for($this->sharedSystem('Partagé'))->create(['handle' => 'partage']);

        foreach ([[$personal, 'partage'], [$shared, 'perso']] as [$author, $handle]) {
            $this->actingAsFront($author)->post('/conversations', ['handle' => $handle]);
        }

        // alter↔système et système↔alter aboutissent à la même conversation.
        $this->assertSame(1, Conversation::count());
        $this->assertSame(MessagingMode::Shared, Conversation::sole()->effective_mode);
    }

    public function test_a_message_records_the_fronting_alter(): void
    {
        $shared = $this->sharedSystem('Constellation');
        $kai = Alter::factory()->for($shared)->create();
        $sora = Alter::factory()->create(['handle' => 'sora']);

        $this->actingAsFront($kai)->post('/conversations', ['handle' => 'sora']);
        $conversation = Conversation::sole();
        $this->actingAsFront($kai)->post("/conversations/{$conversation->uuid}/messages", ['content' => 'Coucou']);

        // Toujours enregistré, même quand l'option d'affichage est coupée :
        // c'est ce qui rendra la migration de mode possible.
        $this->assertSame($kai->id, Message::sole()->author_alter_id);
    }

    public function test_a_stranger_cannot_read_a_conversation(): void
    {
        $kai = Alter::factory()->create();
        $sora = Alter::factory()->create(['handle' => 'sora']);
        $stranger = Alter::factory()->create();

        $this->actingAsFront($kai)->post('/conversations', ['handle' => 'sora']);
        $conversation = Conversation::sole();

        $this->actingAsFront($stranger)->get("/conversations/{$conversation->uuid}")->assertNotFound();
        $this->actingAsFront($stranger)
            ->post("/conversations/{$conversation->uuid}/messages", ['content' => 'Intrus'])
            ->assertNotFound();
    }

    public function test_a_blocked_alter_cannot_open_a_conversation(): void
    {
        $kai = Alter::factory()->create();
        $sora = Alter::factory()->create(['handle' => 'sora']);

        $this->actingAsFront($sora)->post("/alters/{$kai->uuid}/block", ['target' => 'alter']);

        $this->actingAsFront($kai)->post('/conversations', ['handle' => 'sora'])->assertNotFound();
    }

    public function test_the_messaging_mode_is_a_system_setting(): void
    {
        $system = System::factory()->create();
        Alter::factory()->for($system)->create();

        $this->actingAs($system)->put('/settings/messaging', [
            'mode' => 'shared',
            'display_name' => 'Constellation',
            'description' => 'Un compte, plusieurs voix.',
            'show_message_author' => true,
        ])->assertRedirect();

        $this->assertSame(MessagingMode::Shared, app(Messaging::class)->modeOf($system->fresh()));
        $this->assertSame('Constellation', $system->fresh()->display_name);
    }

    protected function sharedSystem(string $name, bool $showAuthor = false): System
    {
        return System::factory()->create([
            'display_name' => $name,
            'settings' => [
                'messaging_mode' => MessagingMode::Shared->value,
                'show_message_author' => $showAuthor,
            ],
        ]);
    }
}
