<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\Post;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_co_post_stays_pending_until_every_author_accepts(): void
    {
        $author = Alter::factory()->create();
        $invited = Alter::factory()->create();

        $this->actingAsFront($author)->post('/posts', [
            'content' => 'Écrit à deux',
            'co_authors' => [$invited->uuid],
        ]);

        $post = Post::sole();
        $this->assertSame(Post::STATUS_PENDING, $post->status);
        $this->assertDatabaseCount('post_authors', 2);

        // Invisible tant que le consentement manque.
        $this->get("/@{$invited->handle}")
            ->assertInertia(fn ($page) => $page->has('posts.data', 0));

        $this->actingAsFront($invited)->post("/posts/{$post->uuid}/invitation");

        $this->assertSame(Post::STATUS_PUBLISHED, $post->fresh()->status);
        $this->get("/@{$invited->handle}")
            ->assertInertia(fn ($page) => $page->has('posts.data', 1));
    }

    public function test_a_cross_post_follows_the_same_path_as_a_co_front(): void
    {
        $system = System::factory()->create();
        $kai = Alter::factory()->for($system)->create();
        $nori = Alter::factory()->for($system)->create();   // co-front
        $sora = Alter::factory()->create();                  // cross-post

        foreach ([$nori, $sora] as $invited) {
            $this->actingAsFront($kai)->post('/posts', [
                'content' => 'Avec '.$invited->name,
                'co_authors' => [$invited->uuid],
            ]);
        }

        // Aucune règle spéciale même-système : les deux posts sont en attente.
        $this->assertSame(2, Post::where('status', Post::STATUS_PENDING)->count());
    }

    public function test_declining_removes_the_author_and_publishes_the_rest(): void
    {
        $author = Alter::factory()->create();
        $invited = Alter::factory()->create();

        $this->actingAsFront($author)->post('/posts', [
            'content' => 'Sans toi alors',
            'co_authors' => [$invited->uuid],
        ]);

        $post = Post::sole();
        $this->actingAsFront($invited)->delete("/posts/{$post->uuid}/invitation");

        $this->assertSame(Post::STATUS_PUBLISHED, $post->fresh()->status);
        $this->assertDatabaseCount('post_authors', 1);
    }

    public function test_an_invitation_only_concerns_the_invited_alter(): void
    {
        $author = Alter::factory()->create();
        $invited = Alter::factory()->create();
        $stranger = Alter::factory()->create();

        $this->actingAsFront($author)->post('/posts', [
            'content' => 'Écrit à deux',
            'co_authors' => [$invited->uuid],
        ]);

        $post = Post::sole();

        $this->actingAsFront($stranger)->post("/posts/{$post->uuid}/invitation")->assertNotFound();
        $this->actingAsFront($author)->post("/posts/{$post->uuid}/invitation")->assertNotFound();

        $this->actingAsFront($invited)
            ->get('/posts/invitations')
            ->assertInertia(fn ($page) => $page->has('invitations.data', 1));
    }

    public function test_a_pending_co_post_stays_out_of_the_feed(): void
    {
        $viewer = Alter::factory()->create();
        $author = Alter::factory()->create();
        $invited = Alter::factory()->create();
        $viewer->following()->attach($author->getKey(), ['accepted' => true]);

        $this->actingAsFront($author)->post('/posts', [
            'content' => 'Pas encore public',
            'co_authors' => [$invited->uuid],
        ]);

        $this->actingAsFront($viewer)
            ->get('/feed')
            ->assertInertia(fn ($page) => $page->has('posts.data', 0));
    }
}
