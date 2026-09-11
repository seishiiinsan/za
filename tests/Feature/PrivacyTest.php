<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_private_alter_is_searchable_but_hides_its_posts(): void
    {
        $target = Alter::factory()->private()->create(['name' => 'Nori', 'handle' => 'nori']);
        $post = Post::factory()->create(['content' => 'Secret']);
        $post->authors()->attach($target->getKey(), ['accepted' => true]);

        $this->get('/search?q=nori')
            ->assertInertia(fn ($page) => $page->has('results.data', 1));

        $this->get('/@nori')
            ->assertInertia(fn ($page) => $page->where('visible', false)->where('posts', null))
            ->assertDontSee('Secret');
    }

    public function test_an_accepted_follower_sees_the_private_posts(): void
    {
        $viewer = Alter::factory()->create();
        $target = Alter::factory()->private()->create(['handle' => 'nori']);
        $target->followers()->attach($viewer->getKey(), ['accepted' => true]);

        $post = Post::factory()->create(['content' => 'Secret']);
        $post->authors()->attach($target->getKey(), ['accepted' => true]);

        $this->actingAsFront($viewer)
            ->get('/@nori')
            ->assertInertia(fn ($page) => $page->where('visible', true)->has('posts.data', 1));
    }

    public function test_a_pending_follower_still_sees_nothing(): void
    {
        $viewer = Alter::factory()->create();
        $target = Alter::factory()->private()->create(['handle' => 'nori']);
        $target->followers()->attach($viewer->getKey(), ['accepted' => false]);

        $this->actingAsFront($viewer)
            ->get('/@nori')
            ->assertInertia(fn ($page) => $page->where('visible', false)->where('hasPendingRequest', true));
    }

    public function test_a_public_alter_is_visible_to_guests(): void
    {
        $target = Alter::factory()->create(['handle' => 'kai']);

        $this->get('/@kai')->assertInertia(fn ($page) => $page->where('visible', true));
    }
}
