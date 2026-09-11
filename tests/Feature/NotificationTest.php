<?php

namespace Tests\Feature;

use App\Enums\MessagingMode;
use App\Models\Alter;
use App\Models\AlterNotification;
use App\Models\Conversation;
use App\Models\Post;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_actions_notify_the_alter_concerned(): void
    {
        $author = Alter::factory()->create();
        $actor = Alter::factory()->create();
        $post = Post::factory()->create();
        $post->authors()->attach($author->getKey(), ['accepted' => true]);

        $this->actingAsFront($actor)->post("/alters/{$author->uuid}/follow");
        $this->actingAsFront($actor)->post("/posts/{$post->uuid}/reaction");
        $this->actingAsFront($actor)->post("/posts/{$post->uuid}/comments", ['content' => 'Joli']);

        $this->assertEqualsCanonicalizing(
            [AlterNotification::TYPE_FOLLOW, AlterNotification::TYPE_REACTION, AlterNotification::TYPE_COMMENT],
            $author->notifications()->pluck('type')->all()
        );
    }

    public function test_an_author_is_not_notified_of_its_own_reaction(): void
    {
        $author = Alter::factory()->create();
        $post = Post::factory()->create();
        $post->authors()->attach($author->getKey(), ['accepted' => true]);

        $this->actingAsFront($author)->post("/posts/{$post->uuid}/reaction");

        $this->assertDatabaseCount('alter_notifications', 0);
    }

    public function test_notifications_stay_at_the_alter_level_by_default(): void
    {
        $system = System::factory()->create();
        $kai = Alter::factory()->for($system)->create();
        $actor = Alter::factory()->create();

        $this->actingAsFront($actor)->post("/alters/{$kai->uuid}/follow");

        $this->actingAs($system)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page->has('notifications', 0));
    }

    public function test_an_alter_can_escalate_its_notifications_to_the_system(): void
    {
        $system = System::factory()->create();
        $kai = Alter::factory()->for($system)->create(['settings' => ['notify_system' => true]]);
        $nori = Alter::factory()->for($system)->create();
        $actor = Alter::factory()->create();

        $this->actingAsFront($actor)->post("/alters/{$kai->uuid}/follow");
        $this->actingAsFront($actor)->post("/alters/{$nori->uuid}/follow");

        $this->actingAs($system)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->has('notifications', 1)
                ->where('notifications.0.for', $kai->name));
    }

    public function test_a_delegate_sees_the_notifications_of_the_alter_it_covers(): void
    {
        $system = System::factory()->create();
        $nori = Alter::factory()->for($system)->create();
        $kai = Alter::factory()->for($system)->create(['settings' => ['delegate_to' => $nori->uuid]]);
        $actor = Alter::factory()->create();

        $this->actingAsFront($actor)->post("/alters/{$kai->uuid}/follow");

        $this->actingAsFront($nori)
            ->get('/notifications')
            ->assertInertia(fn ($page) => $page
                ->has('notifications', 1)
                ->where('notifications.0.delegated', true)
                ->where('notifications.0.for', $kai->name));

        // Un alter tiers du même système ne voit rien.
        $third = Alter::factory()->for($system)->create();
        $this->actingAsFront($third)
            ->get('/notifications')
            ->assertInertia(fn ($page) => $page->has('notifications', 0));
    }

    public function test_delegation_only_works_inside_the_system(): void
    {
        $system = System::factory()->create();
        $kai = Alter::factory()->for($system)->create();
        $stranger = Alter::factory()->create();

        $this->actingAs($system)->put("/alters/{$kai->uuid}", [
            'name' => $kai->name,
            'handle' => $kai->handle,
            'privacy_level' => 'public',
            'delegate_to' => $stranger->uuid,
        ]);

        $this->assertNull($kai->fresh()->delegate());
    }

    public function test_a_shared_messaging_notifies_every_alter_of_the_recipient_system(): void
    {
        $recipient = System::factory()->create([
            'display_name' => 'Constellation',
            'settings' => ['messaging_mode' => MessagingMode::Shared->value],
        ]);
        $kai = Alter::factory()->for($recipient)->create(['handle' => 'kai']);
        $nori = Alter::factory()->for($recipient)->create();
        $sender = Alter::factory()->create();

        $this->actingAsFront($sender)->post('/conversations', ['handle' => 'kai']);
        $conversation = Conversation::sole();
        $this->actingAsFront($sender)->post("/conversations/{$conversation->uuid}/messages", [
            'content' => 'Bonjour',
        ]);

        $this->assertSame(1, $kai->notifications()->count());
        $this->assertSame(1, $nori->notifications()->count());
    }

    public function test_reading_a_shared_mailbox_notification_marks_it_read_for_everyone(): void
    {
        $recipient = System::factory()->create([
            'display_name' => 'Constellation',
            'settings' => ['messaging_mode' => MessagingMode::Shared->value],
        ]);
        $kai = Alter::factory()->for($recipient)->create(['handle' => 'kai']);
        $nori = Alter::factory()->for($recipient)->create();
        $sender = Alter::factory()->create();

        $this->actingAsFront($sender)->post('/conversations', ['handle' => 'kai']);
        $conversation = Conversation::sole();
        $this->actingAsFront($sender)->post("/conversations/{$conversation->uuid}/messages", [
            'content' => 'Bonjour',
        ]);

        // Kai lit : la boîte est commune, Nori n'a plus rien à traiter.
        $this->actingAsFront($kai)->post("/notifications/{$kai->notifications()->sole()->uuid}/read");

        $this->assertNotNull($kai->notifications()->sole()->read_at);
        $this->assertNotNull($nori->notifications()->sole()->read_at);
    }

    public function test_an_individual_notification_stays_unread_for_the_others(): void
    {
        $system = System::factory()->create();
        $kai = Alter::factory()->for($system)->create();
        $nori = Alter::factory()->for($system)->create();
        $actor = Alter::factory()->create();

        $this->actingAsFront($actor)->post("/alters/{$kai->uuid}/follow");
        $this->actingAsFront($actor)->post("/alters/{$nori->uuid}/follow");

        $this->actingAsFront($kai)->post("/notifications/{$kai->notifications()->sole()->uuid}/read");

        $this->assertNotNull($kai->notifications()->sole()->read_at);
        $this->assertNull($nori->notifications()->sole()->read_at);
    }

    public function test_a_notification_is_marked_read_by_its_own_system(): void
    {
        $alter = Alter::factory()->create();
        $actor = Alter::factory()->create();

        $this->actingAsFront($actor)->post("/alters/{$alter->uuid}/follow");
        $notification = AlterNotification::sole();

        $this->actingAsFront($actor)->post("/notifications/{$notification->uuid}/read")->assertNotFound();

        $this->actingAsFront($alter)->post("/notifications/{$notification->uuid}/read");
        $this->assertNotNull($notification->fresh()->read_at);
    }
}
