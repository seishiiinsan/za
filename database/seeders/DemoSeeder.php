<?php

namespace Database\Seeders;

use App\Enums\MessagingMode;
use App\Enums\PrivacyLevel;
use App\Models\Alter;
use App\Models\AlterNotification;
use App\Models\Block;
use App\Models\Comment;
use App\Models\Conversation;
use App\Models\Post;
use App\Models\System;
use App\Support\Handles;
use App\Support\Messaging;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Jeu d'essai de démonstration : environ 5 000 enregistrements cohérents.
 *
 * Cohérents au sens où les relations tiennent debout — on ne suit que des
 * alters d'autres systèmes, un post co-écrit reste en attente tant qu'un
 * invité n'a pas accepté, un commentaire arrive après son post, une
 * conversation se lit dans l'ordre. Le texte est écrit (voir DemoContent),
 * pas rempli au hasard.
 *
 *     php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    protected const SYSTEMS = 60;

    protected const FOLLOWS = 700;

    protected const POSTS = 900;

    protected const COMMENTS = 450;

    protected const REACTIONS = 1000;

    protected const CONVERSATIONS = 110;

    protected const NOTIFICATIONS = 300;

    protected const BLOCKS = 24;

    /** @var Collection<int, Alter> */
    protected Collection $alters;

    /** @var array<int, string> */
    protected array $usedHandles = [];

    /** Ouvertures récemment employées : deux posts voisins ne commencent pas pareil. */
    protected array $recentOpeners = [];

    public function run(): void
    {
        $handles = app(Handles::class);
        $messaging = app(Messaging::class);
        $start = Carbon::now()->subMonths(6);

        DB::transaction(function () use ($handles, $messaging, $start) {
            $systems = $this->seedSystems($start);
            $this->alters = $this->seedAlters($systems, $handles, $start);

            $this->seedFollows();
            $posts = $this->seedPosts($start);
            $this->seedComments($posts);
            $this->seedReactions($posts);
            $this->seedConversations($messaging, $start);
            $this->seedBlocks();
            $this->seedNotifications($start);
            $this->seedShowcase($messaging, $start);
        });

        $this->report();
    }

    /** @return Collection<int, System> */
    protected function seedSystems(Carbon $start): Collection
    {
        return collect(range(1, self::SYSTEMS))->map(function (int $index) use ($start) {
            // Un système sur cinq expose une messagerie partagée : c'est le seul
            // endroit où un compte a une surface publique.
            $shared = $index % 5 === 0;

            return System::create([
                // Le premier système garde l'adresse documentée dans le README.
                'email' => $index === 1 ? 'system@za.test' : "systeme{$index}@za.test",
                'email_verified_at' => $start->copy()->addDays(random_int(0, 20)),
                'password' => Hash::make('password'),
                'display_name' => $shared ? DemoContent::SYSTEM_NAMES[$index % count(DemoContent::SYSTEM_NAMES)] : null,
                'description' => $shared ? DemoContent::SYSTEM_DESCRIPTIONS[$index % count(DemoContent::SYSTEM_DESCRIPTIONS)] : null,
                'settings' => [
                    'messaging_mode' => $shared ? MessagingMode::Shared->value : MessagingMode::Personal->value,
                    'show_message_author' => $shared && $index % 10 === 0,
                    'switch_threshold_hours' => 5,
                ],
            ]);
        });
    }

    /**
     * @param  Collection<int, System>  $systems
     * @return Collection<int, Alter>
     */
    protected function seedAlters(Collection $systems, Handles $handles, Carbon $start): Collection
    {
        $alters = collect();

        foreach ($systems as $index => $system) {
            // Entre un et six alters : un système n'a pas de taille standard.
            $count = [1, 2, 2, 3, 3, 3, 4, 4, 5, 6][$index % 10];

            // Le système de démonstration porte les trois noms du README.
            $names = $index === 0 ? ['Kai', 'Nori', 'Sora'] : [];
            $count = $index === 0 ? 3 : $count;

            for ($position = 0; $position < $count; $position++) {
                $name = $names[$position] ?? DemoContent::NAMES[($index * 3 + $position) % count(DemoContent::NAMES)];

                $alter = $system->alters()->create([
                    'name' => $name,
                    'handle' => $index === 0
                    ? Str::lower($name)
                    : $this->uniqueHandle($name, $handles),
                    'pronouns' => DemoContent::PRONOUNS[($index + $position) % count(DemoContent::PRONOUNS)] ?: null,
                    'bio' => DemoContent::BIOS[($index * 2 + $position) % count(DemoContent::BIOS)],
                    'privacy_level' => $index === 0
                        ? PrivacyLevel::Public
                        : $this->privacyFor($index * 10 + $position),
                    'settings' => [
                        'show_connections' => $position === 0 && $index % 7 === 0,
                        'notify_system' => $index % 3 === 0,
                        'color' => $position % 2 === 0 ? ($index + $position) % 6 : null,
                    ],
                    'created_at' => $start->copy()->addDays(random_int(0, 30)),
                ]);

                // Le système est déjà en main : inutile de le relire plus tard.
                $alters->push($alter->setRelation('system', $system));
            }
        }

        return $alters;
    }

    /** Une majorité de profils publics, le reste réparti sur les autres grades. */
    protected function privacyFor(int $seed): PrivacyLevel
    {
        return match ($seed % 10) {
            0, 1 => PrivacyLevel::Private,
            2 => PrivacyLevel::Unlisted,
            3 => PrivacyLevel::ReadOnly,
            default => PrivacyLevel::Public,
        };
    }

    protected function uniqueHandle(string $name, Handles $handles): string
    {
        do {
            $handle = Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z]/', '')
                ->append('_', Str::lower(Str::random(4)))->value();
            $key = $handles->normalize($handle);
        } while (isset($this->usedHandles[$key]));

        $this->usedHandles[$key] = true;

        return $handle;
    }

    /** Follows entre alters de systèmes différents, acceptés sauf chez les privés. */
    protected function seedFollows(): void
    {
        $rows = [];
        $seen = [];
        // Un alter en lecture seule suit sans publier : il garde son feed.
        $targets = $this->alters->filter(fn (Alter $alter) => $alter->privacy_level !== PrivacyLevel::ReadOnly);

        while (count($rows) < self::FOLLOWS) {
            $follower = $this->alters->random();
            $followed = $targets->random();

            if ($follower->system_id === $followed->system_id) {
                continue;
            }

            $pair = "{$follower->id}-{$followed->id}";

            if (isset($seen[$pair])) {
                continue;
            }

            $seen[$pair] = true;
            $accepted = $followed->privacy_level !== PrivacyLevel::Private || random_int(1, 100) <= 70;

            $rows[] = [
                'follower_alter_id' => $follower->id,
                'followed_alter_id' => $followed->id,
                'accepted' => $accepted,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        collect($rows)->chunk(500)->each(fn (Collection $chunk) => DB::table('follows')->insert($chunk->all()));
    }

    /** @return Collection<int, Post> */
    protected function seedPosts(Carbon $start): Collection
    {
        $authors = $this->alters->filter(fn (Alter $alter) => $alter->privacy_level !== PrivacyLevel::ReadOnly)->values();
        $posts = collect();

        for ($index = 0; $index < self::POSTS; $index++) {
            $author = $authors->random();
            $writtenAt = $start->copy()->addMinutes(random_int(0, 6 * 30 * 24 * 60));

            $post = Post::create([
                'content' => $this->composePost(),
                'status' => Post::STATUS_PUBLISHED,
                'created_at' => $writtenAt,
                'updated_at' => $writtenAt,
            ]);

            $post->authors()->attach($author->getKey(), ['accepted' => true]);

            // Un post sur huit est écrit à deux ; un sur trois parmi ceux-là
            // attend encore l'accord de l'invité.
            if ($index % 8 === 0) {
                $coAuthor = $authors->random();

                if ($coAuthor->id !== $author->id) {
                    $accepted = $index % 24 !== 0;
                    $post->authors()->attach($coAuthor->getKey(), ['accepted' => $accepted]);
                    $post->syncStatus();
                }
            }

            $posts->push($post);
        }

        return $posts;
    }

    protected function composePost(): string
    {
        do {
            $opener = DemoContent::POST_OPENERS[array_rand(DemoContent::POST_OPENERS)];
        } while (in_array($opener, $this->recentOpeners, true));

        $this->recentOpeners[] = $opener;
        $this->recentOpeners = array_slice($this->recentOpeners, -8);

        $parts = [
            $opener,
            DemoContent::POST_MIDDLES[array_rand(DemoContent::POST_MIDDLES)],
            DemoContent::POST_CLOSERS[array_rand(DemoContent::POST_CLOSERS)],
        ];

        return trim(implode(' ', array_filter($parts)));
    }

    /** @param Collection<int, Post> $posts */
    protected function seedComments(Collection $posts): void
    {
        $published = $posts->where('status', Post::STATUS_PUBLISHED)->values();
        $speakers = $this->alters->filter(fn (Alter $alter) => $alter->privacy_level !== PrivacyLevel::ReadOnly)->values();

        for ($index = 0; $index < self::COMMENTS; $index++) {
            $post = $published->random();
            $author = $speakers->random();
            // Un commentaire arrive après son post, jamais avant.
            $writtenAt = $post->created_at->copy()->addMinutes(random_int(5, 60 * 72));

            $comment = new Comment([
                'content' => DemoContent::COMMENTS[array_rand(DemoContent::COMMENTS)],
            ]);
            $comment->alter_id = $author->getKey();
            $comment->created_at = $writtenAt;
            $comment->updated_at = $writtenAt;

            $post->comments()->save($comment);
        }
    }

    /** @param Collection<int, Post> $posts */
    protected function seedReactions(Collection $posts): void
    {
        $published = $posts->where('status', Post::STATUS_PUBLISHED)->values();
        $readers = $this->alters->filter(fn (Alter $alter) => $alter->privacy_level !== PrivacyLevel::ReadOnly)->values();
        $rows = [];
        $seen = [];

        while (count($rows) < self::REACTIONS) {
            $post = $published->random();
            $reader = $readers->random();
            $pair = "{$post->id}-{$reader->id}";

            if (isset($seen[$pair])) {
                continue;
            }

            $seen[$pair] = true;
            $reactedAt = $post->created_at->copy()->addMinutes(random_int(1, 60 * 48));

            $rows[] = [
                'post_id' => $post->id,
                'alter_id' => $reader->id,
                'created_at' => $reactedAt,
                'updated_at' => $reactedAt,
            ];
        }

        collect($rows)->chunk(500)->each(fn (Collection $chunk) => DB::table('reactions')->insert($chunk->all()));
    }

    protected function seedConversations(Messaging $messaging, Carbon $start): void
    {
        $speakers = $this->alters->filter(fn (Alter $alter) => $alter->privacy_level !== PrivacyLevel::ReadOnly)->values();

        for ($index = 0; $index < self::CONVERSATIONS; $index++) {
            $mine = $speakers->random();
            $theirs = $speakers->random();

            if ($mine->system_id === $theirs->system_id) {
                continue;
            }

            $conversation = $messaging->conversationBetween(
                $messaging->correspondentFor($mine),
                $messaging->correspondentFor($theirs),
            );

            $writtenAt = $start->copy()->addDays(random_int(0, 170));

            // Un fil se lit dans l'ordre : chaque message suit le précédent.
            foreach (range(1, random_int(3, 12)) as $turn) {
                $speaker = $turn % 2 === 0 ? $theirs : $mine;
                $writtenAt = $writtenAt->copy()->addMinutes(random_int(2, 240));

                $message = $messaging->send(
                    $conversation,
                    $speaker,
                    DemoContent::MESSAGES[array_rand(DemoContent::MESSAGES)],
                );

                $message->forceFill(['created_at' => $writtenAt, 'updated_at' => $writtenAt])->save();
            }
        }
    }

    protected function seedBlocks(): void
    {
        $created = 0;

        while ($created < self::BLOCKS) {
            $blocker = $this->alters->random();
            $target = $this->alters->random();

            if ($blocker->system_id === $target->system_id) {
                continue;
            }

            $isSystemBlock = $created % 3 === 0;

            Block::firstOrCreate([
                'blocker_alter_id' => $blocker->id,
                'target_type' => $isSystemBlock ? Block::TARGET_SYSTEM : Block::TARGET_ALTER,
                'target_ref_id' => $isSystemBlock ? $target->system_id : $target->id,
            ]);

            $created++;
        }
    }

    protected function seedNotifications(Carbon $start): void
    {
        $types = [
            AlterNotification::TYPE_FOLLOW,
            AlterNotification::TYPE_FOLLOW_REQUEST,
            AlterNotification::TYPE_REACTION,
            AlterNotification::TYPE_COMMENT,
            AlterNotification::TYPE_MESSAGE,
            AlterNotification::TYPE_POST_INVITATION,
        ];

        $rows = [];

        for ($index = 0; $index < self::NOTIFICATIONS; $index++) {
            $recipient = $this->alters->random();
            $actor = $this->alters->random();
            $happenedAt = $start->copy()->addDays(random_int(0, 179));

            $rows[] = [
                'uuid' => (string) Str::uuid(),
                'alter_id' => $recipient->id,
                'type' => $types[$index % count($types)],
                // Une notification de boîte commune se lit pour tout le système.
                'group_uuid' => $index % 7 === 0 ? (string) Str::uuid() : null,
                'payload' => json_encode(['actor' => $actor->name, 'handle' => $actor->handle]),
                'read_at' => $index % 3 === 0 ? $happenedAt->copy()->addHours(2) : null,
                'created_at' => $happenedAt,
                'updated_at' => $happenedAt,
            ];
        }

        collect($rows)->chunk(500)
            ->each(fn (Collection $chunk) => DB::table('alter_notifications')->insert($chunk->all()));
    }

    /**
     * Le compte de démonstration mérite un feed plein : sans abonnements ni
     * posts, l'application s'ouvre sur des écrans vides.
     */
    protected function seedShowcase(Messaging $messaging, Carbon $start): void
    {
        $system = System::where('email', 'system@za.test')->firstOrFail();
        $others = $this->alters
            ->filter(fn (Alter $alter) => $alter->system_id !== $system->id)
            ->filter(fn (Alter $alter) => $alter->privacy_level === PrivacyLevel::Public)
            ->values();

        foreach ($system->alters()->get() as $alter) {
            $alter->setRelation('system', $system);
            $follows = $others->random(min(30, $others->count()));

            foreach ($follows as $followed) {
                DB::table('follows')->insertOrIgnore([
                    'follower_alter_id' => $alter->id,
                    'followed_alter_id' => $followed->id,
                    'accepted' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Quelques abonnés en retour, dont des demandes à traiter.
            foreach ($others->random(8) as $index => $follower) {
                DB::table('follows')->insertOrIgnore([
                    'follower_alter_id' => $follower->id,
                    'followed_alter_id' => $alter->id,
                    'accepted' => $index > 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach (range(1, 10) as $step) {
                $writtenAt = $start->copy()->addDays(random_int(0, 179))->addMinutes(random_int(0, 1400));

                $post = Post::create([
                    'content' => $this->composePost(),
                    'status' => Post::STATUS_PUBLISHED,
                    'created_at' => $writtenAt,
                    'updated_at' => $writtenAt,
                ]);

                $post->authors()->attach($alter->getKey(), ['accepted' => true]);
            }

            // Deux conversations par alter, avec des gens qu'il suit.
            foreach ($follows->random(2) as $correspondent) {
                $correspondent->loadMissing('system');
                $conversation = $messaging->conversationBetween(
                    $messaging->correspondentFor($alter),
                    $messaging->correspondentFor($correspondent),
                );

                $writtenAt = $start->copy()->addDays(random_int(150, 179));

                foreach (range(1, random_int(4, 9)) as $turn) {
                    $speaker = $turn % 2 === 0 ? $correspondent : $alter;
                    $writtenAt = $writtenAt->copy()->addMinutes(random_int(3, 180));

                    $messaging->send($conversation, $speaker, DemoContent::MESSAGES[array_rand(DemoContent::MESSAGES)])
                        ->forceFill(['created_at' => $writtenAt, 'updated_at' => $writtenAt])->save();
                }
            }
        }
    }

    protected function report(): void
    {
        $tables = [
            'systems', 'alters', 'follows', 'posts', 'post_authors', 'comments',
            'reactions', 'conversations', 'conversation_participants', 'messages',
            'alter_notifications', 'blocks', 'correspondents',
        ];

        $total = 0;

        foreach ($tables as $table) {
            $count = DB::table($table)->count();
            $total += $count;
            $this->command?->line(sprintf('  %-28s %6d', $table, $count));
        }

        $this->command?->info(sprintf('  %-28s %6d', 'total', $total));
    }
}
