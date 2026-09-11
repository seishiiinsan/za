<?php

namespace Tests\Feature;

use App\Enums\PrivacyLevel;
use App\Models\Alter;
use App\Models\Post;
use App\Models\System;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le jeu d'essai doit rester cohérent : il sert de terrain de démonstration et
 * de test de charge pour les écrans.
 */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DemoSeeder::class);
    }

    public function test_it_produces_a_large_dataset(): void
    {
        $total = collect([
            'systems', 'alters', 'follows', 'posts', 'post_authors', 'comments', 'reactions',
            'conversations', 'conversation_participants', 'messages', 'alter_notifications',
            'blocks', 'correspondents',
        ])->sum(fn (string $table) => DB::table($table)->count());

        $this->assertGreaterThan(5000, $total);
    }

    public function test_nobody_follows_an_alter_of_their_own_system(): void
    {
        $crossing = DB::table('follows')
            ->join('alters as followers', 'followers.id', '=', 'follows.follower_alter_id')
            ->join('alters as followed', 'followed.id', '=', 'follows.followed_alter_id')
            ->whereColumn('followers.system_id', 'followed.system_id')
            ->count();

        $this->assertSame(0, $crossing);
    }

    public function test_a_read_only_alter_never_publishes(): void
    {
        $readOnly = Alter::where('privacy_level', PrivacyLevel::ReadOnly->value)->pluck('id');

        $this->assertGreaterThan(0, $readOnly->count());
        $this->assertSame(0, DB::table('post_authors')->whereIn('alter_id', $readOnly)->count());
    }

    public function test_a_co_post_waiting_for_consent_stays_pending(): void
    {
        $pending = Post::where('status', Post::STATUS_PENDING)->get();

        $this->assertGreaterThan(0, $pending->count());

        foreach ($pending->take(5) as $post) {
            $this->assertTrue($post->authors()->wherePivot('accepted', false)->exists());
        }
    }

    public function test_comments_and_reactions_come_after_their_post(): void
    {
        $earlyComment = DB::table('comments')
            ->join('posts', 'posts.id', '=', 'comments.post_id')
            ->whereColumn('comments.created_at', '<', 'posts.created_at')
            ->count();

        $earlyReaction = DB::table('reactions')
            ->join('posts', 'posts.id', '=', 'reactions.post_id')
            ->whereColumn('reactions.created_at', '<', 'posts.created_at')
            ->count();

        $this->assertSame(0, $earlyComment);
        $this->assertSame(0, $earlyReaction);
    }

    public function test_the_documented_demo_account_opens_on_a_full_feed(): void
    {
        $system = System::where('email', 'system@za.test')->firstOrFail();
        $kai = $system->alters()->where('handle', 'kai')->firstOrFail();

        $this->assertGreaterThanOrEqual(20, $kai->following()->wherePivot('accepted', true)->count());

        // Le feed s'ouvre plein. Le compte exact dépend du tirage du seeder :
        // on vérifie qu'il y a de quoi lire, pas un nombre précis.
        $this->actingAsFront($kai)
            ->get('/feed')
            ->assertInertia(function ($page) {
                $this->assertGreaterThanOrEqual(10, count($page->toArray()['props']['posts']['data']));
            });
    }

    public function test_the_privacy_levels_are_actually_mixed(): void
    {
        // Une répartition ratée passe inaperçue : sans alters publics, le jeu
        // d'essai n'a plus rien à montrer.
        foreach ([PrivacyLevel::Private, PrivacyLevel::Unlisted, PrivacyLevel::ReadOnly] as $level) {
            $this->assertGreaterThan(5, Alter::where('privacy_level', $level->value)->count());
        }

        $this->assertGreaterThan(
            Alter::count() / 2,
            Alter::where('privacy_level', PrivacyLevel::Public->value)->count(),
        );
    }

    public function test_handles_stay_unique_under_the_canonical_form(): void
    {
        $this->assertSame(
            Alter::count(),
            Alter::distinct()->count('handle_key'),
        );
    }
}
