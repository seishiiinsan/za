<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\Post;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_dashboard_aggregates_every_alter_of_the_system(): void
    {
        $system = System::factory()->create();
        $kai = Alter::factory()->for($system)->create();
        $nori = Alter::factory()->for($system)->create();
        $stranger = Alter::factory()->create();

        foreach ([$kai, $nori, $stranger] as $alter) {
            $post = Post::factory()->create();
            $post->authors()->attach($alter->getKey(), ['accepted' => true]);
        }

        $this->actingAs($system)
            ->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('alters.data', 2)
                ->has('posts.data', 2));
    }

    public function test_the_aggregated_feed_has_no_public_route(): void
    {
        $alter = Alter::factory()->create();

        // Le profil public d'un alter ne dit rien des autres alters du système.
        $sibling = Alter::factory()->for($alter->system)->create(['name' => 'Nori', 'handle' => 'nori']);

        $this->get("/@{$alter->handle}")
            ->assertDontSee($sibling->handle)
            ->assertDontSee($sibling->name);

        $this->get('/dashboard')->assertRedirect('/login');
    }
}
