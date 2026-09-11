<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\Conversation;
use App\Support\Messaging;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationListTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_handle_is_matched_whatever_its_casing(): void
    {
        $kai = Alter::factory()->create();
        Alter::factory()->create(['handle' => 'sora']);

        // « Sora » et « @Sora » désignent @sora : la recherche passe par la
        // forme canonique du handle.
        foreach (['Sora', '@sora', 'SORA'] as $typed) {
            $this->actingAsFront($kai)->post('/conversations', ['handle' => $typed])->assertRedirect();
        }

        $this->assertSame(1, Conversation::count());
    }

    public function test_the_list_shows_the_last_message_and_its_read_state(): void
    {
        $kai = Alter::factory()->create();
        $sora = Alter::factory()->create(['handle' => 'sora']);
        $messaging = app(Messaging::class);

        $this->actingAsFront($kai)->post('/conversations', ['handle' => 'sora']);
        $conversation = Conversation::sole();

        $this->actingAsFront($kai)->post("/conversations/{$conversation->uuid}/messages", ['content' => 'Premier']);
        $this->actingAsFront($kai)->post("/conversations/{$conversation->uuid}/messages", ['content' => 'Dernier']);

        $this->actingAsFront($kai)
            ->get('/conversations')
            ->assertInertia(fn ($page) => $page
                ->where('conversations.0.last_message.excerpt', 'Dernier')
                ->where('conversations.0.last_message.author', 'Toi')
                // Ses propres messages ne rendent pas une conversation non lue.
                ->where('conversations.0.unread', false));

        // Vu d'en face, le dernier message reçu marque la conversation non lue.
        $this->actingAsFront($sora)
            ->get('/conversations')
            ->assertInertia(fn ($page) => $page->where('conversations.0.unread', true));

        // L'ouvrir vaut lecture.
        $this->actingAsFront($sora)->get("/conversations/{$conversation->uuid}");
        $this->actingAsFront($sora)
            ->get('/conversations')
            ->assertInertia(fn ($page) => $page->where('conversations.0.unread', false));
    }

    public function test_the_list_names_the_author_of_a_received_message(): void
    {
        $kai = Alter::factory()->create();
        $sora = Alter::factory()->create(['handle' => 'sora', 'name' => 'Sora']);

        $this->actingAsFront($kai)->post('/conversations', ['handle' => 'sora']);
        $conversation = Conversation::sole();

        // Le message vient d'en face : l'aperçu doit nommer son auteur.
        $this->actingAsFront($sora)->post("/conversations/{$conversation->uuid}/messages", [
            'content' => 'On se voit jeudi ?',
        ]);

        $this->actingAsFront($kai)
            ->get('/conversations')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('conversations.0.last_message.author', 'Sora')
                ->where('conversations.0.last_message.mine', false)
                ->where('conversations.0.unread', true));
    }

    public function test_the_picker_offers_the_alters_already_known(): void
    {
        $kai = Alter::factory()->create();
        $followed = Alter::factory()->create();
        $follower = Alter::factory()->create();
        $stranger = Alter::factory()->create();

        $kai->following()->attach($followed->getKey(), ['accepted' => true]);
        $kai->followers()->attach($follower->getKey(), ['accepted' => true]);

        $this->actingAsFront($kai)
            ->get('/conversations')
            ->assertInertia(fn ($page) => $page->has('contacts.data', 2))
            ->assertDontSee($stranger->handle);
    }
}
