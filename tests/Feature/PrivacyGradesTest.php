<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Grades non-listé et lecture (v2). Les grades public et privé sont couverts
 * par PrivacyTest.
 */
class PrivacyGradesTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_unlisted_alter_leaves_the_search_but_keeps_its_profile(): void
    {
        $alter = Alter::factory()->unlisted()->create(['handle' => 'nori']);
        $post = Post::factory()->create(['content' => 'Toujours lisible']);
        $post->authors()->attach($alter->getKey(), ['accepted' => true]);

        $this->get('/search?q=nori')
            ->assertInertia(fn ($page) => $page->has('results.data', 0));

        // Le lien direct fonctionne : le grade masque la recherche, pas la trace.
        $this->get('/@nori')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('visible', true)->has('posts.data', 1));
    }

    public function test_an_unlisted_alter_is_followed_without_approval(): void
    {
        $follower = Alter::factory()->create();
        $target = Alter::factory()->unlisted()->create();

        $this->actingAsFront($follower)->post("/alters/{$target->uuid}/follow");

        $this->assertDatabaseHas('follows', [
            'follower_alter_id' => $follower->id,
            'followed_alter_id' => $target->id,
            'accepted' => true,
        ]);
    }

    public function test_a_read_only_alter_has_no_public_profile(): void
    {
        $alter = Alter::factory()->readOnly()->create(['handle' => 'kai']);

        $this->get('/@kai')->assertNotFound();
        $this->get('/search?q=kai')
            ->assertInertia(fn ($page) => $page->has('results.data', 0));
    }

    public function test_a_read_only_alter_cannot_publish(): void
    {
        $alter = Alter::factory()->readOnly()->create();

        $this->actingAsFront($alter)
            ->post('/posts', ['content' => 'Interdit'])
            ->assertForbidden();

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_a_read_only_alter_cannot_be_invited_as_co_author(): void
    {
        $author = Alter::factory()->create();
        $readOnly = Alter::factory()->readOnly()->create();

        $this->actingAsFront($author)->post('/posts', [
            'content' => 'Sans co-auteur finalement',
            'co_authors' => [$readOnly->uuid],
        ]);

        $this->assertDatabaseCount('post_authors', 1);
        $this->assertSame(Post::STATUS_PUBLISHED, Post::sole()->status);
    }

    public function test_a_read_only_alter_still_reads_its_feed(): void
    {
        $viewer = Alter::factory()->readOnly()->create();
        $followed = Alter::factory()->create();
        $viewer->following()->attach($followed->getKey(), ['accepted' => true]);

        $post = Post::factory()->create(['content' => 'Lisible']);
        $post->authors()->attach($followed->getKey(), ['accepted' => true]);

        $this->actingAsFront($viewer)
            ->get('/feed')
            ->assertInertia(fn ($page) => $page->has('posts.data', 1));
    }
}
