<?php

namespace Tests\Feature;

use App\Models\Alter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FollowTest extends TestCase
{
    use RefreshDatabase;

    public function test_following_a_public_alter_is_immediate(): void
    {
        $follower = Alter::factory()->create();
        $target = Alter::factory()->create();

        $this->actingAsFront($follower)->post("/alters/{$target->uuid}/follow");

        $this->assertDatabaseHas('follows', [
            'follower_alter_id' => $follower->id,
            'followed_alter_id' => $target->id,
            'accepted' => true,
        ]);
    }

    public function test_following_a_private_alter_creates_a_request(): void
    {
        $follower = Alter::factory()->create();
        $target = Alter::factory()->private()->create();

        $this->actingAsFront($follower)->post("/alters/{$target->uuid}/follow");

        $this->assertDatabaseHas('follows', [
            'follower_alter_id' => $follower->id,
            'followed_alter_id' => $target->id,
            'accepted' => false,
        ]);
    }

    public function test_a_request_can_be_approved_then_the_follow_can_be_removed(): void
    {
        $follower = Alter::factory()->create();
        $target = Alter::factory()->private()->create();
        $target->followers()->attach($follower->getKey(), ['accepted' => false]);

        $this->actingAsFront($target)->post("/follows/requests/{$follower->uuid}");
        $this->assertDatabaseHas('follows', [
            'follower_alter_id' => $follower->id,
            'accepted' => true,
        ]);

        $this->actingAsFront($follower)->delete("/alters/{$target->uuid}/follow");
        $this->assertDatabaseCount('follows', 0);
    }

    public function test_a_request_can_be_rejected(): void
    {
        $follower = Alter::factory()->create();
        $target = Alter::factory()->private()->create();
        $target->followers()->attach($follower->getKey(), ['accepted' => false]);

        $this->actingAsFront($target)->delete("/follows/requests/{$follower->uuid}");

        $this->assertDatabaseCount('follows', 0);
    }

    public function test_an_alter_cannot_follow_itself(): void
    {
        $alter = Alter::factory()->create();

        $this->actingAsFront($alter)->post("/alters/{$alter->uuid}/follow")->assertStatus(422);
    }

    public function test_connection_lists_are_hidden_by_default(): void
    {
        $target = Alter::factory()->create();
        $follower = Alter::factory()->create();
        $target->followers()->attach($follower->getKey(), ['accepted' => true]);

        $this->get("/@{$target->handle}")
            ->assertInertia(fn ($page) => $page->where('connections', null)->where('counts.followers', 1));

        $target->update(['settings' => ['show_connections' => true]]);

        $this->get("/@{$target->handle}")
            ->assertInertia(fn ($page) => $page->has('connections.followers.data', 1));
    }
}
