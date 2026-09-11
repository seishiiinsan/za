<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\System;
use App\Support\Front;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontSwitchTest extends TestCase
{
    use RefreshDatabase;

    public function test_switching_front_keeps_the_session_authenticated(): void
    {
        $system = System::factory()->create();
        [$kai, $nori] = [
            Alter::factory()->for($system)->create(),
            Alter::factory()->for($system)->create(),
        ];

        $this->actingAsFront($kai)
            ->from('/alters')
            ->put('/front', ['alter_id' => $nori->id])
            ->assertRedirect('/alters');

        $this->assertAuthenticatedAs($system);
        $this->assertSame($nori->getKey(), session(Front::SESSION_KEY));
    }

    public function test_a_system_cannot_switch_to_a_foreign_alter(): void
    {
        $mine = Alter::factory()->create();
        $theirs = Alter::factory()->create();

        $this->actingAsFront($mine)
            ->put('/front', ['alter_id' => $theirs->id])
            ->assertNotFound();
    }
}
