<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\AlterNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationBellTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_bell_carries_its_notifications_on_every_page(): void
    {
        $alter = Alter::factory()->create();
        $actor = Alter::factory()->create();

        $this->actingAsFront($actor)->post("/alters/{$alter->uuid}/follow");

        $this->actingAsFront($alter)
            ->get('/feed')
            ->assertInertia(fn ($page) => $page
                ->where('notifications.unread', 1)
                ->has('notifications.items', 1));
    }

    public function test_opening_the_bell_marks_everything_read(): void
    {
        $alter = Alter::factory()->create();
        $actor = Alter::factory()->create();

        $this->actingAsFront($actor)->post("/alters/{$alter->uuid}/follow");
        $this->actingAsFront($alter)->post('/notifications/read-all');

        $this->assertNotNull(AlterNotification::sole()->read_at);
    }

    public function test_a_notification_is_dismissed_by_its_own_system(): void
    {
        $alter = Alter::factory()->create();
        $actor = Alter::factory()->create();

        $this->actingAsFront($actor)->post("/alters/{$alter->uuid}/follow");
        $notification = AlterNotification::sole();

        $this->actingAsFront($actor)->delete("/notifications/{$notification->uuid}")->assertNotFound();

        $this->actingAsFront($alter)->delete("/notifications/{$notification->uuid}");
        $this->assertDatabaseCount('alter_notifications', 0);
    }
}
