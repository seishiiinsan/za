<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_alter_likes_then_unlikes_a_post(): void
    {
        $author = Alter::factory()->create();
        $reader = Alter::factory()->create();
        $post = $this->postBy($author);

        $this->actingAsFront($reader)->post("/posts/{$post->uuid}/reaction");
        $this->assertDatabaseHas('reactions', ['post_id' => $post->id, 'alter_id' => $reader->id]);

        // Deux likes du même alter ne comptent qu'une fois.
        $this->actingAsFront($reader)->post("/posts/{$post->uuid}/reaction");
        $this->assertDatabaseCount('reactions', 1);

        $this->actingAsFront($reader)->delete("/posts/{$post->uuid}/reaction");
        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_a_comment_is_attributed_to_the_active_front(): void
    {
        $author = Alter::factory()->create();
        $reader = Alter::factory()->create();
        $post = $this->postBy($author);

        $this->actingAsFront($reader)->post("/posts/{$post->uuid}/comments", ['content' => 'Joli']);

        $comment = Comment::sole();
        $this->assertSame($reader->id, $comment->alter_id);
        $this->assertSame('Joli', $comment->content);
    }

    public function test_a_read_only_alter_can_neither_like_nor_comment(): void
    {
        $author = Alter::factory()->create();
        $reader = Alter::factory()->readOnly()->create();
        $post = $this->postBy($author);

        $this->actingAsFront($reader)->post("/posts/{$post->uuid}/reaction")->assertForbidden();
        $this->actingAsFront($reader)
            ->post("/posts/{$post->uuid}/comments", ['content' => 'Interdit'])
            ->assertForbidden();

        $this->assertDatabaseCount('reactions', 0);
        $this->assertDatabaseCount('comments', 0);
    }

    public function test_an_unlisted_alter_reacts_and_leaves_a_public_trace(): void
    {
        $author = Alter::factory()->create();
        $reader = Alter::factory()->unlisted()->create();
        $post = $this->postBy($author);

        $this->actingAsFront($reader)->post("/posts/{$post->uuid}/comments", ['content' => 'Passé par là']);

        // La trace reste lisible depuis le profil de l'auteur du post.
        $this->get("/@{$author->handle}")
            ->assertInertia(fn ($page) => $page
                ->where('posts.data.0.comments_count', 1)
                ->where('posts.data.0.comments.0.author.handle', $reader->handle));
    }

    public function test_a_private_post_cannot_be_reacted_to_by_a_stranger(): void
    {
        $author = Alter::factory()->private()->create();
        $stranger = Alter::factory()->create();
        $post = $this->postBy($author);

        $this->actingAsFront($stranger)->post("/posts/{$post->uuid}/reaction")->assertNotFound();
    }

    public function test_only_the_comment_author_or_the_post_author_can_remove_a_comment(): void
    {
        $author = Alter::factory()->create();
        $reader = Alter::factory()->create();
        $stranger = Alter::factory()->create();
        $post = $this->postBy($author);

        $this->actingAsFront($reader)->post("/posts/{$post->uuid}/comments", ['content' => 'Joli']);
        $comment = Comment::sole();

        $this->actingAsFront($stranger)->delete("/comments/{$comment->uuid}")->assertNotFound();
        $this->actingAsFront($author)->delete("/comments/{$comment->uuid}");

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_the_reader_state_is_exposed_to_the_feed(): void
    {
        $viewer = Alter::factory()->create();
        $author = Alter::factory()->create();
        $viewer->following()->attach($author->getKey(), ['accepted' => true]);
        $post = $this->postBy($author);

        $this->actingAsFront($viewer)->post("/posts/{$post->uuid}/reaction");

        $this->actingAsFront($viewer)
            ->get('/feed')
            ->assertInertia(fn ($page) => $page
                ->where('posts.data.0.reactions_count', 1)
                ->where('posts.data.0.reacted', true));
    }

    protected function postBy(Alter $alter): Post
    {
        $post = Post::factory()->create();
        $post->authors()->attach($alter->getKey(), ['accepted' => true]);

        return $post->fresh();
    }
}
