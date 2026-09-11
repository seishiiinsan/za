<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Models\Alter;
use App\Models\System;
use App\Support\TwoFactor;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('kai@za.test|127.0.0.1');
    }

    public function test_registering_sends_a_verification_link(): void
    {
        Notification::fake();

        $this->post('/register', [
            'email' => 'kai@za.test',
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
        ]);

        Notification::assertSentTo(System::sole(), VerifyEmail::class);
        $this->assertNull(System::sole()->email_verified_at);
    }

    public function test_an_unverified_system_reaches_nothing_but_verification_and_security(): void
    {
        $system = System::factory()->unverified()->create();

        $this->actingAs($system)->get('/dashboard')->assertRedirect('/verify-email');
        $this->actingAs($system)->get('/alters')->assertRedirect('/verify-email');

        // Protéger son compte reste possible avant vérification.
        $this->actingAs($system)->get('/verify-email')->assertOk();
        $this->actingAs($system)->get('/settings/security')->assertOk();
    }

    public function test_a_signed_link_verifies_the_address(): void
    {
        $system = System::factory()->unverified()->create();
        // Ne fausser que cet événement : les hooks de modèle passent par le
        // même dispatcher, et un `fake()` global les couperait.
        Event::fake([Verified::class]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $system->getKey(),
            'hash' => sha1($system->email),
        ]);

        $this->actingAs($system)->get($url)->assertRedirect('/dashboard');

        $this->assertNotNull($system->fresh()->email_verified_at);
        Event::assertDispatched(Verified::class);
    }

    public function test_an_unsigned_verification_link_is_refused(): void
    {
        $system = System::factory()->unverified()->create();

        $this->actingAs($system)
            ->get("/verify-email/{$system->getKey()}/".sha1($system->email))
            ->assertForbidden();

        $this->assertNull($system->fresh()->email_verified_at);
    }

    public function test_login_is_rate_limited_by_email_and_ip(): void
    {
        System::factory()->create(['email' => 'kai@za.test', 'password' => Hash::make('secret-solide')]);

        for ($attempt = 0; $attempt < AuthenticatedSessionController::MAX_ATTEMPTS; $attempt++) {
            $this->post('/login', ['email' => 'kai@za.test', 'password' => 'faux'])
                ->assertSessionHasErrors('email');
        }

        // Le bon mot de passe ne passe plus : la limite protège le compte, pas la requête.
        $this->post('/login', ['email' => 'kai@za.test', 'password' => 'secret-solide'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        // Un client JSON reçoit le code de statut correspondant.
        $this->postJson('/login', ['email' => 'kai@za.test', 'password' => 'secret-solide'])
            ->assertStatus(429);
    }

    public function test_a_successful_login_clears_the_counter(): void
    {
        System::factory()->create(['email' => 'kai@za.test', 'password' => Hash::make('secret-solide')]);

        $this->post('/login', ['email' => 'kai@za.test', 'password' => 'faux']);
        $this->post('/login', ['email' => 'kai@za.test', 'password' => 'secret-solide'])->assertRedirect();

        $this->assertSame(0, RateLimiter::attempts('kai@za.test|127.0.0.1'));
    }

    public function test_two_factor_is_only_active_once_confirmed(): void
    {
        $system = System::factory()->create();

        $this->actingAs($system)->post('/settings/security/two-factor')->assertRedirect();
        $system->refresh();

        $this->assertNotNull($system->two_factor_secret);
        $this->assertFalse($system->hasTwoFactorEnabled());

        $this->actingAs($system)
            ->post('/settings/security/two-factor/confirm', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $code = app(TwoFactor::class)->codeAt($system->two_factor_secret, intdiv(time(), TwoFactor::PERIOD));
        $this->actingAs($system)->post('/settings/security/two-factor/confirm', ['code' => $code]);

        $this->assertTrue($system->fresh()->hasTwoFactorEnabled());
    }

    public function test_a_password_alone_no_longer_opens_the_session(): void
    {
        $system = $this->systemWithTwoFactor();

        $this->post('/login', ['email' => 'kai@za.test', 'password' => 'secret-solide'])
            ->assertRedirect('/two-factor-challenge');

        $this->assertGuest();

        $code = app(TwoFactor::class)->codeAt($system->two_factor_secret, intdiv(time(), TwoFactor::PERIOD));
        $this->post('/two-factor-challenge', ['code' => $code])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($system);
    }

    public function test_a_recovery_code_works_once(): void
    {
        $system = $this->systemWithTwoFactor();
        $recovery = $system->two_factor_recovery_codes[0];

        $this->post('/login', ['email' => 'kai@za.test', 'password' => 'secret-solide']);
        $this->post('/two-factor-challenge', ['recovery_code' => $recovery])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($system);
        $this->assertNotContains($recovery, $system->fresh()->two_factor_recovery_codes);

        Auth::logout();
        $this->post('/login', ['email' => 'kai@za.test', 'password' => 'secret-solide']);
        $this->post('/two-factor-challenge', ['recovery_code' => $recovery])->assertSessionHasErrors('code');
    }

    public function test_the_challenge_cannot_be_reached_without_a_password(): void
    {
        $this->systemWithTwoFactor();

        $this->get('/two-factor-challenge')->assertRedirect('/login');
        $this->post('/two-factor-challenge', ['code' => '000000'])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_disabling_two_factor_requires_the_password(): void
    {
        $system = $this->systemWithTwoFactor();

        $this->actingAs($system)
            ->delete('/settings/security/two-factor', ['password' => 'faux'])
            ->assertSessionHasErrors('password');

        $this->assertTrue($system->fresh()->hasTwoFactorEnabled());

        $this->actingAs($system)->delete('/settings/security/two-factor', ['password' => 'secret-solide']);

        $this->assertFalse($system->fresh()->hasTwoFactorEnabled());
    }

    public function test_the_secret_and_recovery_codes_are_encrypted_at_rest(): void
    {
        $system = $this->systemWithTwoFactor();

        $row = \DB::table('systems')->where('id', $system->getKey())->first();

        $this->assertNotSame($system->two_factor_secret, $row->two_factor_secret);
        $this->assertStringNotContainsString($system->two_factor_recovery_codes[0], $row->two_factor_recovery_codes);
    }

    protected function systemWithTwoFactor(): System
    {
        $totp = app(TwoFactor::class);

        $system = System::factory()->create([
            'email' => 'kai@za.test',
            'password' => Hash::make('secret-solide'),
            'two_factor_secret' => $totp->generateSecret(),
            'two_factor_recovery_codes' => $totp->generateRecoveryCodes(),
            'two_factor_confirmed_at' => now(),
        ]);

        Alter::factory()->for($system)->create();

        return $system;
    }
}
