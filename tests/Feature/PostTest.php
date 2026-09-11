<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\Post;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_solo_post_creates_exactly_one_author_row(): void
    {
        $alter = Alter::factory()->create();

        $this->actingAsFront($alter)
            ->from('/feed')
            ->post('/posts', ['content' => 'Premier post'])
            ->assertRedirect('/feed');

        $post = Post::sole();
        $this->assertDatabaseCount('post_authors', 1);
        $this->assertTrue($post->authors()->whereKey($alter->getKey())->exists());
        $this->assertSame(Post::STATUS_PUBLISHED, $post->status);
    }

    public function test_a_post_is_attributed_to_the_active_front(): void
    {
        $system = System::factory()->create();
        $kai = Alter::factory()->for($system)->create();
        $nori = Alter::factory()->for($system)->create();

        $this->actingAsFront($kai)->put('/front', ['alter_id' => $nori->uuid]);
        $this->actingAsFront($nori)->post('/posts', ['content' => 'Écrit par Nori']);

        $this->assertTrue(Post::sole()->authors()->whereKey($nori->getKey())->exists());
    }

    public function test_an_author_can_edit_and_delete_its_post(): void
    {
        $alter = Alter::factory()->create();
        $post = Post::factory()->create();
        $post->authors()->attach($alter->getKey(), ['accepted' => true]);

        $this->actingAsFront($alter)->put("/posts/{$post->uuid}", ['content' => 'Corrigé']);
        $this->assertSame('Corrigé', $post->fresh()->content);

        $this->actingAsFront($alter)->delete("/posts/{$post->uuid}");
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_another_system_cannot_touch_the_post(): void
    {
        $mine = Alter::factory()->create();
        $theirs = Alter::factory()->create();
        $post = Post::factory()->create();
        $post->authors()->attach($mine->getKey(), ['accepted' => true]);

        $this->actingAsFront($theirs)->delete("/posts/{$post->uuid}")->assertNotFound();
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_the_schema_already_supports_several_authors(): void
    {
        $post = Post::factory()->create(['status' => Post::STATUS_PENDING]);
        $a = Alter::factory()->create();
        $b = Alter::factory()->create();

        $post->authors()->attach([$a->getKey() => ['accepted' => true], $b->getKey() => ['accepted' => false]]);
        $post->syncStatus();
        $this->assertSame(Post::STATUS_PENDING, $post->fresh()->status);

        $post->authors()->updateExistingPivot($b->getKey(), ['accepted' => true]);
        $post->syncStatus();
        $this->assertSame(Post::STATUS_PUBLISHED, $post->fresh()->status);
    }
}
