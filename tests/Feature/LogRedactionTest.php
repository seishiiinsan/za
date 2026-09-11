<?php

namespace Tests\Feature;

use App\Logging\RedactSensitiveData;
use App\Logging\RedactSystemId;
use App\Models\Alter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Les logs ne doivent pas devenir la table de corrélation que le produit
 * s'interdit de publier : une exception de requête écrit le SQL avec ses
 * valeurs, `system_id` compris.
 */
class LogRedactionTest extends TestCase
{
    use RefreshDatabase;

    protected string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = storage_path('logs/testing-redaction.log');
        @unlink($this->path);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);

        parent::tearDown();
    }

    public function test_a_query_exception_never_writes_the_system_id(): void
    {
        $alter = Alter::factory()->create();

        try {
            DB::table('alters')->insert([
                'system_id' => $alter->system_id,
                'uuid' => 'x',
                'name' => 'Kai',
                'handle' => 'kai',
                'colonne_absente' => 1,
            ]);
        } catch (QueryException $exception) {
            $this->channel()->error('Echec', ['exception' => $exception]);
        }

        $contents = file_get_contents($this->path);

        $this->assertStringContainsString(RedactSystemId::PLACEHOLDER, $contents);
        $this->assertStringNotContainsString("values ({$alter->system_id},", $contents);
    }

    public function test_the_session_owner_is_redacted_too(): void
    {
        // `sessions.user_id` porte l'identifiant du système authentifié.
        $this->channel()->info('Session écrite', ['user_id' => 77]);
        $this->channel()->info('update "sessions" set "user_id" = 77 where "id" = "abc"');

        $contents = file_get_contents($this->path);

        $this->assertStringNotContainsString('77', $contents);
        $this->assertSame(2, substr_count($contents, RedactSystemId::PLACEHOLDER));
    }

    public function test_a_nested_context_is_scrubbed(): void
    {
        $this->channel()->warning('Contexte', [
            'payload' => ['alter' => ['system_id' => 91, 'name' => 'Kai']],
        ]);

        $contents = file_get_contents($this->path);

        $this->assertStringNotContainsString('91', $contents);
        $this->assertStringContainsString('Kai', $contents);
    }

    public function test_every_writing_channel_taps_the_redaction(): void
    {
        foreach (['single', 'daily', 'stderr', 'syslog', 'errorlog'] as $channel) {
            $this->assertContains(
                RedactSensitiveData::class,
                config("logging.channels.{$channel}.tap", []),
                "Le canal {$channel} n'applique pas le masquage."
            );
        }
    }

    /**
     * Un canal déclaré en configuration : `Log::build()` ignore les taps, ils
     * ne sont lus que sur un canal nommé — comme en production.
     */
    protected function channel()
    {
        config()->set('logging.channels.testing_redaction', [
            'driver' => 'single',
            'path' => $this->path,
            'tap' => [RedactSensitiveData::class],
        ]);

        return Log::channel('testing_redaction');
    }
}
