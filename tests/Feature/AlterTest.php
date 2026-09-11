<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\System;
use App\Support\Front;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AlterTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_system_can_create_several_alters(): void
    {
        $system = System::factory()->create();

        foreach (['kai', 'nori'] as $handle) {
            $this->actingAs($system)->post('/alters', [
                'name' => ucfirst($handle),
                'handle' => $handle,
                'privacy_level' => 'public',
            ])->assertRedirect('/alters');
        }

        $this->assertCount(2, $system->alters()->get());
    }

    public function test_the_first_alter_becomes_the_active_front(): void
    {
        $system = System::factory()->create();

        $this->actingAs($system)->post('/alters', [
            'name' => 'Kai',
            'handle' => 'kai',
            'privacy_level' => 'public',
        ]);

        $this->assertSame($system->alters()->first()->getKey(), session(Front::SESSION_KEY));
    }

    public function test_an_avatar_can_be_uploaded(): void
    {
        Storage::fake('public');
        $system = System::factory()->create();

        $this->actingAs($system)->post('/alters', [
            'name' => 'Kai',
            'handle' => 'kai',
            'privacy_level' => 'public',
            'avatar' => UploadedFile::fake()->image('kai.jpg'),
        ]);

        $path = $system->alters()->first()->avatar_path;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_a_system_cannot_edit_an_alter_of_another_system(): void
    {
        $mine = Alter::factory()->create();
        $theirs = Alter::factory()->create();

        $this->actingAs($mine->system)
            ->get("/alters/{$theirs->id}/edit")
            ->assertNotFound();
    }

    public function test_an_alter_can_be_deleted(): void
    {
        $alter = Alter::factory()->create();

        $this->actingAsFront($alter)->delete("/alters/{$alter->id}")->assertRedirect('/alters');

        $this->assertDatabaseMissing('alters', ['id' => $alter->id]);
    }
}
