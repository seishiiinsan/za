<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\System;
use App\Support\Handles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class HandlePolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_handle_too_short_or_reserved_is_refused(): void
    {
        $system = System::factory()->create();

        foreach (['ka', 'admin', 'Support', 'kai-nuit', 'kai nuit'] as $handle) {
            $this->actingAs($system)
                ->post('/alters', ['name' => 'Kai', 'handle' => $handle, 'privacy_level' => 'public'])
                ->assertSessionHasErrors('handle');
        }

        $this->assertDatabaseCount('alters', 0);
    }

    public function test_two_look_alike_handles_cannot_coexist(): void
    {
        Alter::factory()->create(['handle' => 'kainuit']);
        $system = System::factory()->create();

        // @ka1nu1t, @kai_nuit : même forme canonique, donc même personne à l'œil.
        foreach (['ka1nu1t', 'kai_nuit', 'KAINUIT'] as $handle) {
            $this->actingAs($system)
                ->post('/alters', ['name' => 'Sosie', 'handle' => strtolower($handle), 'privacy_level' => 'public'])
                ->assertSessionHasErrors('handle');
        }
    }

    public function test_a_handle_changes_once_a_month(): void
    {
        $alter = Alter::factory()->create(['handle' => 'kai']);

        $this->actingAs($alter->system)->put("/alters/{$alter->uuid}", [
            'name' => $alter->name,
            'handle' => 'kainuit',
            'privacy_level' => 'public',
        ])->assertRedirect('/alters');

        $this->assertSame('kainuit', $alter->fresh()->handle);

        // Un second changement dans la foulée est refusé.
        $this->actingAs($alter->system)->put("/alters/{$alter->uuid}", [
            'name' => $alter->name,
            'handle' => 'kaijour',
            'privacy_level' => 'public',
        ])->assertSessionHasErrors('handle');

        Carbon::setTestNow(now()->addDays(Handles::COOLDOWN_DAYS + 1));

        $this->actingAs($alter->system)->put("/alters/{$alter->uuid}", [
            'name' => $alter->name,
            'handle' => 'kaijour',
            'privacy_level' => 'public',
        ])->assertRedirect('/alters');

        $this->assertSame('kaijour', $alter->fresh()->handle);

        Carbon::setTestNow();
    }

    public function test_a_released_handle_stays_in_quarantine(): void
    {
        $alter = Alter::factory()->create(['handle' => 'kai']);
        $other = System::factory()->create();

        $this->actingAs($alter->system)->put("/alters/{$alter->uuid}", [
            'name' => $alter->name,
            'handle' => 'kainuit',
            'privacy_level' => 'public',
        ]);

        // Personne ne récupère @kai pour hériter de ses abonnés.
        $this->actingAs($other)
            ->post('/alters', ['name' => 'Usurpateur', 'handle' => 'kai', 'privacy_level' => 'public'])
            ->assertSessionHasErrors('handle');

        Carbon::setTestNow(now()->addDays(Handles::COOLDOWN_DAYS + 1));

        $this->actingAs($other)
            ->post('/alters', ['name' => 'Nouveau', 'handle' => 'kai', 'privacy_level' => 'public'])
            ->assertRedirect('/alters');

        Carbon::setTestNow();
    }

    public function test_an_alter_can_take_back_its_own_quarantined_handle(): void
    {
        $alter = Alter::factory()->create(['handle' => 'kai']);

        $this->actingAs($alter->system)->put("/alters/{$alter->uuid}", [
            'name' => $alter->name, 'handle' => 'kainuit', 'privacy_level' => 'public',
        ]);

        Carbon::setTestNow(now()->addDays(Handles::COOLDOWN_DAYS + 1));

        $this->actingAs($alter->system)->put("/alters/{$alter->uuid}", [
            'name' => $alter->name, 'handle' => 'kai', 'privacy_level' => 'public',
        ])->assertRedirect('/alters');

        $this->assertSame('kai', $alter->fresh()->handle);

        Carbon::setTestNow();
    }

    public function test_deleting_an_alter_frees_its_handle_at_once(): void
    {
        $alter = Alter::factory()->create(['handle' => 'kai']);
        $other = System::factory()->create();

        $this->actingAsFront($alter)->delete("/alters/{$alter->uuid}");

        $this->actingAs($other)
            ->post('/alters', ['name' => 'Nouveau', 'handle' => 'kai', 'privacy_level' => 'public'])
            ->assertRedirect('/alters');

        $this->assertSame('kai', $other->alters()->sole()->handle);
    }

    public function test_a_restore_reclaims_the_handle_when_it_is_still_free(): void
    {
        $alter = Alter::factory()->create(['handle' => 'kai']);

        $this->actingAsFront($alter)->delete("/alters/{$alter->uuid}");
        $this->actingAs($alter->system)->post("/alters/{$alter->uuid}/restore")->assertRedirect('/alters');

        $this->assertSame('kai', $alter->fresh()->handle);
        $this->assertNotSoftDeleted('alters', ['id' => $alter->id]);
    }

    public function test_a_restore_asks_for_a_new_handle_when_the_old_one_is_taken(): void
    {
        $alter = Alter::factory()->create(['handle' => 'kai']);
        $other = System::factory()->create();

        $this->actingAsFront($alter)->delete("/alters/{$alter->uuid}");
        $this->actingAs($other)->post('/alters', ['name' => 'Nouveau', 'handle' => 'kai', 'privacy_level' => 'public']);

        $this->actingAs($alter->system)
            ->from('/alters')
            ->post("/alters/{$alter->uuid}/restore")
            ->assertRedirect('/alters')
            ->assertSessionHas('error');

        $this->assertSoftDeleted('alters', ['id' => $alter->id]);

        $this->actingAs($alter->system)
            ->post("/alters/{$alter->uuid}/restore", ['handle' => 'kainuit'])
            ->assertRedirect('/alters');

        $this->assertSame('kainuit', $alter->fresh()->handle);
        $this->assertNotSoftDeleted('alters', ['id' => $alter->id]);
    }

    public function test_the_alters_screen_says_whether_the_handle_can_be_taken_back(): void
    {
        $alter = Alter::factory()->create(['handle' => 'kai']);

        $this->actingAsFront($alter)->delete("/alters/{$alter->uuid}");

        $this->actingAs($alter->system)
            ->get('/alters')
            ->assertInertia(fn ($page) => $page
                ->where('trashed.0.previous_handle', 'kai')
                ->where('trashed.0.handle_available', true));
    }
}
