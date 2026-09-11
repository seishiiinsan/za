<?php

namespace Tests\Feature;

use App\Http\Resources\AlterResource;
use App\Models\Alter;
use App\Models\Post;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Invariant d'anti-corrélation : `system_id` ne sort jamais d'une surface publique
 * et rien ne doit permettre de relier deux alters d'un même système.
 *
 * Ce test est bloquant en CI.
 */
class AntiCorrelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_public_response_leaks_the_system(): void
    {
        [$system, $kai, $nori] = $this->systemWithTwoAlters();
        $viewer = Alter::factory()->create();

        foreach ($this->publicResponses($kai, $nori) as $label => $response) {
            $this->assertNoSystemLeak($response, $system, $label);
        }

        // Mêmes surfaces, cette fois vues par un autre alter connecté.
        $this->actingAsFront($viewer);

        foreach ($this->publicResponses($kai, $nori) as $label => $response) {
            $this->assertNoSystemLeak($response, $system, $label);
        }
    }

    public function test_a_public_profile_never_mentions_a_sibling_alter(): void
    {
        [, $kai, $nori] = $this->systemWithTwoAlters();

        $this->get("/@{$kai->handle}")
            ->assertDontSee($nori->handle)
            ->assertDontSee($nori->name);
    }

    public function test_the_public_alter_serializer_carries_no_system_field(): void
    {
        $alter = Alter::factory()->create();

        $payload = (new AlterResource($alter))->resolve();

        $this->assertSame([
            'id', 'name', 'handle', 'pronouns', 'bio', 'avatar_url', 'privacy_level', 'is_private', 'deleted',
        ], array_keys($payload));
    }

    public function test_the_model_never_serializes_system_id(): void
    {
        $alter = Alter::factory()->create();

        $this->assertArrayNotHasKey('system_id', $alter->toArray());
    }

    public function test_public_identifiers_are_opaque(): void
    {
        [, $kai, $nori] = $this->systemWithTwoAlters();

        // Deux alters d'un même système ont des ids internes consécutifs :
        // seul l'identifiant public opaque doit sortir.
        $this->assertSame($kai->id + 1, $nori->id);

        foreach ([$kai, $nori] as $alter) {
            $this->get("/@{$alter->handle}")
                ->assertInertia(fn ($page) => $page
                    ->where('alter.id', $alter->uuid)
                    ->where('alter.id', fn ($id) => ! is_numeric($id)));
        }

        $this->get("/@{$kai->handle}")->assertDontSee('"id":'.$kai->id, false);
    }

    public function test_search_does_not_recommend_accounts(): void
    {
        [, $kai] = $this->systemWithTwoAlters();

        // Une recherche vide ne propose rien : aucune suggestion de comptes.
        $this->get('/search')
            ->assertInertia(fn ($page) => $page->has('results.data', 0));

        $this->get("/search?q={$kai->handle}")
            ->assertInertia(fn ($page) => $page->has('results.data', 1));
    }

    /** @return array{0: System, 1: Alter, 2: Alter} */
    protected function systemWithTwoAlters(): array
    {
        $system = System::factory()->create(['display_name' => 'Constellation']);
        $kai = Alter::factory()->for($system)->create(['name' => 'Kai', 'handle' => 'kai']);
        $nori = Alter::factory()->for($system)->create(['name' => 'Nori', 'handle' => 'nori']);

        foreach ([$kai, $nori] as $alter) {
            $post = Post::factory()->create();
            $post->authors()->attach($alter->getKey(), ['accepted' => true]);
        }

        return [$system, $kai, $nori];
    }

    /** @return array<string, TestResponse> */
    protected function publicResponses(Alter $kai, Alter $nori): array
    {
        return [
            'accueil' => $this->get('/'),
            'profil (html)' => $this->get("/@{$kai->handle}"),
            'profil (inertia)' => $this->get("/@{$kai->handle}", ['X-Inertia' => 'true', 'X-Inertia-Version' => '']),
            'profil du second alter' => $this->get("/@{$nori->handle}"),
            'recherche' => $this->get('/search?q=a'),
        ];
    }

    protected function assertNoSystemLeak(TestResponse $response, System $system, string $label): void
    {
        $body = $response->getContent();

        $this->assertStringNotContainsString('system_id', $body, "Fuite `system_id` sur : {$label}");
        $this->assertStringNotContainsString($system->email, $body, "Fuite e-mail système sur : {$label}");
        $this->assertStringNotContainsString('Constellation', $body, "Fuite nom système sur : {$label}");

        if ($decoded = json_decode($body, true)) {
            $this->assertFalse($this->hasKeyDeep($decoded, 'system_id'), "Clé `system_id` dans le payload : {$label}");
        }
    }

    /** @param array<mixed> $payload */
    protected function hasKeyDeep(array $payload, string $needle): bool
    {
        foreach ($payload as $key => $value) {
            if ($key === $needle) {
                return true;
            }

            if (is_array($value) && $this->hasKeyDeep($value, $needle)) {
                return true;
            }
        }

        return false;
    }
}
