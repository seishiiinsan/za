<?php

namespace Tests\Feature;

use App\Http\Resources\PostResource;
use App\Models\Alter;
use App\Models\Post;
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

    public function test_an_avatar_is_reencoded_and_loses_its_metadata(): void
    {
        Storage::fake('public');
        $system = System::factory()->create();

        // JPEG porteur d'un marqueur de métadonnées.
        $source = imagecreatetruecolor(40, 40);
        ob_start();
        imagejpeg($source);
        $jpeg = (string) ob_get_clean();
        imagedestroy($source);
        $jpeg = substr($jpeg, 0, 2)."\xFF\xE1\x00\x16Exif\x00\x00GPS-SECRET".substr($jpeg, 2);

        $this->actingAs($system)->post('/alters', [
            'name' => 'Kai',
            'handle' => 'kai',
            'privacy_level' => 'public',
            'avatar' => UploadedFile::fake()->createWithContent('kai.jpg', $jpeg),
        ]);

        $path = $system->alters()->first()->avatar_path;
        $stored = Storage::disk('public')->get($path);

        $this->assertStringNotContainsString('GPS-SECRET', $stored);
        $this->assertStringNotContainsString('Exif', $stored);
        $this->assertNotSame($jpeg, $stored);
    }

    public function test_a_system_cannot_edit_an_alter_of_another_system(): void
    {
        $mine = Alter::factory()->create();
        $theirs = Alter::factory()->create();

        $this->actingAs($mine->system)
            ->get("/alters/{$theirs->uuid}/edit")
            ->assertNotFound();
    }

    public function test_a_deleted_alter_is_soft_deleted_and_restorable(): void
    {
        $alter = Alter::factory()->create();

        $this->actingAsFront($alter)->delete("/alters/{$alter->uuid}")->assertRedirect('/alters');

        $this->assertSoftDeleted('alters', ['id' => $alter->id]);
        // Plus aucune surface publique ne le connaît.
        $this->get("/@{$alter->handle}")->assertNotFound();
        $this->get("/search?q={$alter->handle}")
            ->assertInertia(fn ($page) => $page->has('results.data', 0));

        $this->actingAs($alter->system)
            ->post("/alters/{$alter->uuid}/restore")
            ->assertRedirect('/alters');

        $this->assertNotSoftDeleted('alters', ['id' => $alter->id]);
        $this->get("/@{$alter->handle}")->assertOk();
    }

    public function test_a_deleted_author_is_shown_as_unknown(): void
    {
        $alter = Alter::factory()->create();
        $post = Post::factory()->create();
        $post->authors()->attach($alter->getKey(), ['accepted' => true]);

        $alter->delete();

        $payload = json_decode(json_encode(
            (new PostResource($post->load('authors')))->resolve()
        ), true);

        $this->assertSame('Inconnu', $payload['authors'][0]['name']);
        $this->assertNull($payload['authors'][0]['handle']);
        $this->assertNull($payload['authors'][0]['avatar_url']);
    }
}
