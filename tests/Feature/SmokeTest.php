<?php

namespace Tests\Feature;

use App\Models\Alter;
use App\Models\Conversation;
use App\Models\Post;
use App\Models\System;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Parcours de toutes les pages sur un jeu de données complet.
 *
 * Le mode strict d'Eloquent transforme un chargement tardif en erreur 500 :
 * une page qui n'est jamais ouverte avec des données réelles cache ce genre de
 * panne jusqu'à la première visite.
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected System $system;

    protected Alter $kai;

    protected Alter $sora;

    protected Post $post;

    protected Conversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->system = System::factory()->create();
        $this->kai = Alter::factory()->for($this->system)->create(['handle' => 'kai']);
        Alter::factory()->for($this->system)->create(['handle' => 'nori']);
        $this->sora = Alter::factory()->create(['handle' => 'sora', 'name' => 'Sora']);

        // Une seule ligne : `following` et `followers` décrivent la même relation.
        $this->kai->following()->attach($this->sora->getKey(), ['accepted' => true]);
        $this->sora->following()->attach($this->kai->getKey(), ['accepted' => true]);

        // Un post d'en face, commenté et aimé, plus un co-post en attente.
        $this->post = Post::factory()->create();
        $this->post->authors()->attach($this->sora->getKey(), ['accepted' => true]);

        $this->actingAsFront($this->kai)->post("/posts/{$this->post->uuid}/reaction");
        $this->actingAsFront($this->kai)->post("/posts/{$this->post->uuid}/comments", ['content' => 'Bien dit']);
        $this->actingAsFront($this->sora)->post('/posts', [
            'content' => 'À deux ?',
            'co_authors' => [$this->kai->uuid],
        ]);

        // Une conversation avec un message reçu, pour l'aperçu de la liste.
        $this->actingAsFront($this->kai)->post('/conversations', ['handle' => 'sora']);
        $this->conversation = Conversation::sole();
        $this->actingAsFront($this->sora)->post("/conversations/{$this->conversation->uuid}/messages", [
            'content' => 'On se voit jeudi ?',
        ]);
    }

    /** @return array<int, array<int, string>> */
    public static function pages(): array
    {
        return [
            ['/feed'],
            ['/dashboard'],
            ['/search?q=sor'],
            ['/alters'],
            ['/alters/create'],
            ['/follows'],
            ['/follows/requests'],
            ['/posts/invitations'],
            ['/blocks'],
            ['/conversations'],
            ['/notifications'],
            ['/settings'],
            ['/settings/messaging'],
            ['/settings/security'],
            ['/@sora'],
        ];
    }

    #[DataProvider('pages')]
    public function test_every_page_opens(string $page): void
    {
        $this->actingAsFront($this->kai)->get($page)->assertOk();
    }

    public function test_the_conversation_opens(): void
    {
        $this->actingAsFront($this->kai)
            ->get("/conversations/{$this->conversation->uuid}")
            ->assertOk();
    }

    public function test_the_public_pages_open_for_a_visitor(): void
    {
        // La préparation a ouvert des sessions : on repart en visiteur.
        auth()->logout();
        session()->flush();

        $this->get('/')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/@sora')->assertOk();
        $this->get('/search?q=sor')->assertOk();
    }
}
