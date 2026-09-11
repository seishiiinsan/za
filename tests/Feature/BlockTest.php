<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\Block;
use App\Models\Post;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_blocking_an_alter_spares_the_other_alters_of_its_system(): void
    {
        $viewer = Alter::factory()->create();
        $system = System::factory()->create();
        $blocked = Alter::factory()->for($system)->create();
        $sibling = Alter::factory()->for($system)->create();

        $this->actingAsFront($viewer)->post("/alters/{$blocked->uuid}/block", ['target' => 'alter']);

        $this->actingAsFront($viewer)->get("/@{$blocked->handle}")->assertNotFound();
        $this->actingAsFront($viewer)->get("/@{$sibling->handle}")->assertOk();

        // Réciproque : l'alter bloqué ne voit plus le bloqueur non plus.
        $this->actingAsFront($blocked)->get("/@{$viewer->handle}")->assertNotFound();
        $this->actingAsFront($sibling)->get("/@{$viewer->handle}")->assertOk();
    }

    public function test_blocking_the_system_closes_the_multi_alter_path(): void
    {
        $viewer = Alter::factory()->create();
        $system = System::factory()->create();
        $blocked = Alter::factory()->for($system)->create();
        $sibling = Alter::factory()->for($system)->create();

        $this->actingAsFront($viewer)->post("/alters/{$blocked->uuid}/block", ['target' => 'system']);

        $this->actingAsFront($viewer)->get("/@{$blocked->handle}")->assertNotFound();
        $this->actingAsFront($viewer)->get("/@{$sibling->handle}")->assertNotFound();
        $this->actingAsFront($sibling)->get("/@{$viewer->handle}")->assertNotFound();
    }

    public function test_a_system_block_never_names_the_system(): void
    {
        $viewer = Alter::factory()->create();
        $blocked = Alter::factory()->create();

        $this->actingAsFront($viewer)->post("/alters/{$blocked->uuid}/block", ['target' => 'system']);

        $response = $this->actingAsFront($viewer)->get('/blocks');

        $response->assertInertia(fn ($page) => $page
            ->has('systemBlocks', 1)
            ->has('alters.data', 0))
            ->assertDontSee($blocked->handle)
            ->assertDontSee('system_id');
    }

    public function test_a_block_hides_posts_and_cuts_the_follow(): void
    {
        $viewer = Alter::factory()->create();
        $blocked = Alter::factory()->create();
        $viewer->following()->attach($blocked->getKey(), ['accepted' => true]);

        $post = Post::factory()->create(['content' => 'Invisible']);
        $post->authors()->attach($blocked->getKey(), ['accepted' => true]);

        $this->actingAsFront($viewer)->post("/alters/{$blocked->uuid}/block", ['target' => 'alter']);

        $this->assertDatabaseCount('follows', 0);
        $this->actingAsFront($viewer)
            ->get('/feed')
            ->assertInertia(fn ($page) => $page->has('posts.data', 0));
        $this->actingAsFront($viewer)
            ->get("/search?q={$blocked->handle}")
            ->assertInertia(fn ($page) => $page->has('results.data', 0));
    }

    public function test_a_blocked_co_author_hides_the_whole_post(): void
    {
        $viewer = Alter::factory()->create();
        $author = Alter::factory()->create();
        $blocked = Alter::factory()->create();
        $viewer->following()->attach($author->getKey(), ['accepted' => true]);

        $post = Post::factory()->create(['content' => 'À deux']);
        $post->authors()->attach([
            $author->getKey() => ['accepted' => true],
            $blocked->getKey() => ['accepted' => true],
        ]);

        $this->actingAsFront($viewer)->post("/alters/{$blocked->uuid}/block", ['target' => 'alter']);

        $this->actingAsFront($viewer)
            ->get('/feed')
            ->assertInertia(fn ($page) => $page->has('posts.data', 0));
    }

    public function test_a_blocked_alter_cannot_follow_or_react(): void
    {
        $viewer = Alter::factory()->create();
        $blocked = Alter::factory()->create();

        $post = Post::factory()->create();
        $post->authors()->attach($viewer->getKey(), ['accepted' => true]);

        $this->actingAsFront($viewer)->post("/alters/{$blocked->uuid}/block", ['target' => 'alter']);

        $this->actingAsFront($blocked)->post("/alters/{$viewer->uuid}/follow")->assertNotFound();
        $this->actingAsFront($blocked)->post("/posts/{$post->uuid}/reaction")->assertNotFound();
    }

    public function test_an_alter_cannot_block_inside_its_own_system(): void
    {
        $system = System::factory()->create();
        $kai = Alter::factory()->for($system)->create();
        $nori = Alter::factory()->for($system)->create();

        $this->actingAsFront($kai)
            ->post("/alters/{$nori->uuid}/block", ['target' => 'alter'])
            ->assertStatus(422);

        $this->assertDatabaseCount('blocks', 0);
    }

    public function test_a_block_can_be_lifted(): void
    {
        $viewer = Alter::factory()->create();
        $blocked = Alter::factory()->create();

        $this->actingAsFront($viewer)->post("/alters/{$blocked->uuid}/block", ['target' => 'alter']);
        $block = Block::sole();

        $this->actingAsFront($viewer)->delete("/blocks/{$block->id}");

        $this->assertDatabaseCount('blocks', 0);
        $this->actingAsFront($viewer)->get("/@{$blocked->handle}")->assertOk();
    }
}
