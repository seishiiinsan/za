<?php

namespace Tests\Feature;

use App\Enums\MessagingMode;
use App\Models\Alter;
use App\Models\Conversation;
use App\Models\Correspondent;
use App\Models\Message;
use App\Models\System;
use App\Support\Messaging;
use App\Support\MessagingModeMigration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MessagingModeMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected Messaging $messaging;

    protected MessagingModeMigration $migration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messaging = app(Messaging::class);
        $this->migration = app(MessagingModeMigration::class);
    }

    public function test_outgoing_messages_follow_their_author(): void
    {
        [$system, $kai, $nori, $other, $conversation] = $this->sharedConversation();

        $this->write($conversation, $this->messaging->systemCorrespondent($system), $kai, 'De Kai', '2026-01-01 10:00');
        $this->write($conversation, $this->messaging->systemCorrespondent($system), $nori, 'De Nori', '2026-01-01 11:00');

        $this->migration->split($system->fresh());

        $this->assertSame(
            $this->conversationOf($kai, $other)->getKey(),
            Message::where('content', 'De Kai')->sole()->conversation_id
        );
        $this->assertSame(
            $this->conversationOf($nori, $other)->getKey(),
            Message::where('content', 'De Nori')->sole()->conversation_id
        );
    }

    public function test_an_outgoing_message_without_author_is_kept_out_of_every_conversation(): void
    {
        [$system, , , , $conversation] = $this->sharedConversation();

        $this->write($conversation, $this->messaging->systemCorrespondent($system), null, 'Sans auteur', '2026-01-01 10:00');

        $this->migration->split($system->fresh());

        $orphan = Message::where('content', 'Sans auteur')->sole();
        $this->assertNull($orphan->conversation_id);
        $this->assertSame($conversation->getKey(), $orphan->origin_conversation_id);
    }

    public function test_rule_one_same_alter_before_and_after_ignores_the_threshold(): void
    {
        [$system, $kai, , $other, $conversation] = $this->sharedConversation();

        $this->write($conversation, $this->messaging->systemCorrespondent($system), $kai, 'Avant', '2026-01-01 00:00');
        $this->write($conversation, $other, null, 'Entrant', '2026-01-02 00:00');
        $this->write($conversation, $this->messaging->systemCorrespondent($system), $kai, 'Après', '2026-01-03 00:00');

        $this->migration->split($system->fresh());

        // Écarts très au-delà du seuil, mais le même alter encadre le message.
        $this->assertSame(
            $this->conversationOf($kai, $other)->getKey(),
            Message::where('content', 'Entrant')->sole()->conversation_id
        );
    }

    public function test_rule_two_ambiguous_context_duplicates_the_message(): void
    {
        [$system, $kai, $nori, $other, $conversation] = $this->sharedConversation();

        $this->write($conversation, $this->messaging->systemCorrespondent($system), $kai, 'Avant', '2026-01-01 10:00');
        $this->write($conversation, $other, null, 'Entrant', '2026-01-01 11:00');
        $this->write($conversation, $this->messaging->systemCorrespondent($system), $nori, 'Après', '2026-01-01 12:00');

        $this->migration->split($system->fresh());

        $copies = Message::where('content', 'Entrant')->get();
        $this->assertCount(2, $copies);
        $this->assertEqualsCanonicalizing([
            $this->conversationOf($kai, $other)->getKey(),
            $this->conversationOf($nori, $other)->getKey(),
        ], $copies->pluck('conversation_id')->all());
    }

    public function test_rules_three_and_four_use_the_only_neighbour(): void
    {
        [$system, $kai, $nori, $other, $conversation] = $this->sharedConversation();

        $this->write($conversation, $this->messaging->systemCorrespondent($system), $kai, 'Avant', '2026-01-01 10:00');
        $this->write($conversation, $other, null, 'Après Kai', '2026-01-01 11:00');

        $this->write($conversation, $other, null, 'Avant Nori', '2026-01-05 10:00');
        $this->write($conversation, $this->messaging->systemCorrespondent($system), $nori, 'Réponse', '2026-01-05 11:00');

        $this->migration->split($system->fresh());

        $this->assertSame(
            $this->conversationOf($kai, $other)->getKey(),
            Message::where('content', 'Après Kai')->sole()->conversation_id
        );
        $this->assertSame(
            $this->conversationOf($nori, $other)->getKey(),
            Message::where('content', 'Avant Nori')->sole()->conversation_id
        );
    }

    public function test_rule_five_keeps_an_unattributable_message(): void
    {
        [$system, $kai, , , $conversation] = $this->sharedConversation();
        $other = $conversation->participants->firstWhere('type', Correspondent::TYPE_ALTER);

        $this->write($conversation, $this->messaging->systemCorrespondent($system), $kai, 'Il y a longtemps', '2026-01-01 00:00');
        $this->write($conversation, $other, null, 'Trop loin', '2026-01-04 00:00');

        $this->migration->split($system->fresh());

        $this->assertNull(Message::where('content', 'Trop loin')->sole()->conversation_id);
    }

    public function test_a_merge_restores_the_detached_messages_in_order(): void
    {
        [$system, $kai, , , $conversation] = $this->sharedConversation();
        $other = $conversation->participants->firstWhere('type', Correspondent::TYPE_ALTER);

        $this->write($conversation, $this->messaging->systemCorrespondent($system), $kai, 'Premier', '2026-01-01 00:00');
        $this->write($conversation, $other, null, 'Perdu', '2026-01-04 00:00');

        $this->migration->split($system->fresh());
        $this->assertNull(Message::where('content', 'Perdu')->sole()->conversation_id);

        $this->migration->merge($system->fresh());

        $merged = Message::where('content', 'Perdu')->sole();
        $this->assertNotNull($merged->conversation_id);

        $contents = Conversation::find($merged->conversation_id)->messages()->pluck('content');
        $this->assertSame(['Premier', 'Perdu'], $contents->all());
    }

    public function test_a_merge_does_not_group_distinct_alters_of_the_other_side(): void
    {
        $system = $this->system(MessagingMode::Personal);
        $kai = Alter::factory()->for($system)->create();
        $nori = Alter::factory()->for($system)->create();
        $sora = Alter::factory()->create();
        $rin = Alter::factory()->create();

        // L'autre côté est en perso : deux personnes distinctes en face.
        $this->conversationOf($kai, $this->messaging->alterCorrespondent($sora));
        $this->conversationOf($nori, $this->messaging->alterCorrespondent($rin));

        $this->migration->merge($system->fresh());

        $this->assertSame(2, Conversation::count());
    }

    public function test_the_threshold_is_a_system_setting(): void
    {
        $system = $this->system(MessagingMode::Shared, ['switch_threshold_hours' => 12]);

        $this->assertSame(12, $this->messaging->switchThresholdHours($system));
        $this->assertSame(5, $this->messaging->switchThresholdHours($this->system(MessagingMode::Shared)));
    }

    public function test_changing_the_mode_from_the_settings_triggers_the_migration(): void
    {
        [$system, $kai, , $other, $conversation] = $this->sharedConversation();
        $this->write($conversation, $this->messaging->systemCorrespondent($system), $kai, 'De Kai', '2026-01-01 10:00');

        $this->actingAs($system)->put('/settings/messaging', [
            'mode' => 'personal',
            'switch_threshold_hours' => 5,
        ]);

        $this->assertSame(
            $this->conversationOf($kai, $other)->getKey(),
            Message::where('content', 'De Kai')->sole()->conversation_id
        );
    }

    /** @return array{0: System, 1: Alter, 2: Alter, 3: Correspondent, 4: Conversation} */
    protected function sharedConversation(): array
    {
        $system = $this->system(MessagingMode::Shared);
        $kai = Alter::factory()->for($system)->create();
        $nori = Alter::factory()->for($system)->create();
        $other = $this->messaging->alterCorrespondent(Alter::factory()->create());

        $conversation = $this->messaging->conversationBetween(
            $this->messaging->systemCorrespondent($system),
            $other,
        );

        return [$system, $kai, $nori, $other, $conversation->load('participants')];
    }

    /** @param array<string, mixed> $settings */
    protected function system(MessagingMode $mode, array $settings = []): System
    {
        return System::factory()->create([
            'display_name' => 'Constellation',
            'settings' => array_merge(['messaging_mode' => $mode->value], $settings),
        ]);
    }

    protected function write(
        Conversation $conversation,
        Correspondent $author,
        ?Alter $alter,
        string $content,
        string $at,
    ): Message {
        return Message::create([
            'conversation_id' => $conversation->getKey(),
            'author_correspondent_id' => $author->getKey(),
            'author_alter_id' => $alter?->getKey(),
            'content' => $content,
            'created_at' => Carbon::parse($at),
        ]);
    }

    protected function conversationOf(Alter $alter, Correspondent $other): Conversation
    {
        return $this->messaging->conversationBetween(
            $this->messaging->alterCorrespondent($alter),
            $other,
        );
    }
}
