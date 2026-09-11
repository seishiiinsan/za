<?php

namespace Tests\Feature;

use App\Models\Alter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlterColorTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_alter_chooses_its_colour(): void
    {
        $alter = Alter::factory()->create();

        $this->actingAs($alter->system)->put("/alters/{$alter->uuid}", [
            'name' => $alter->name,
            'handle' => $alter->handle,
            'privacy_level' => 'public',
            'color' => 3,
        ])->assertRedirect('/alters');

        $this->assertSame(3, $alter->fresh()->colorIndex());
        $this->get("/@{$alter->handle}")
            ->assertInertia(fn ($page) => $page->where('alter.color', 3));
    }

    public function test_an_out_of_range_colour_is_refused(): void
    {
        $alter = Alter::factory()->create();

        $this->actingAs($alter->system)->put("/alters/{$alter->uuid}", [
            'name' => $alter->name,
            'handle' => $alter->handle,
            'privacy_level' => 'public',
            'color' => 42,
        ])->assertSessionHasErrors('color');
    }
}
