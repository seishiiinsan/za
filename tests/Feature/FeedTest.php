<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_feed_only_shows_posts_of_followed_alters(): void
    {
        $viewer = Alter::factory()->create();
        $followed = Alter::factory()->create();
        $stranger = Alter::factory()->create();

        $viewer->following()->attach($followed->getKey(), ['accepted' => true]);

        $this->postBy($followed, 'Vu');
        $this->postBy($stranger, 'Pas vu');

        $this->actingAsFront($viewer)
            ->get('/feed')
            ->assertInertia(fn ($page) => $page
                ->component('Feed/Index')
                ->has('posts.data', 1)
                ->where('posts.data.0.content', 'Vu'));
    }

    public function test_pending_posts_stay_out_of_the_feed(): void
    {
        $viewer = Alter::factory()->create();
        $followed = Alter::factory()->create();
        $viewer->following()->attach($followed->getKey(), ['accepted' => true]);

        $post = Post::factory()->create(['status' => Post::STATUS_PENDING]);
        $post->authors()->attach($followed->getKey(), ['accepted' => true]);

        $this->actingAsFront($viewer)
            ->get('/feed')
            ->assertInertia(fn ($page) => $page->has('posts.data', 0));
    }

    protected function postBy(Alter $alter, string $content): Post
    {
        $post = Post::factory()->create(['content' => $content]);
        $post->authors()->attach($alter->getKey(), ['accepted' => true]);

        return $post;
    }
}
