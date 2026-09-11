<?php

namespace Tests\Feature;

use App\Models\System;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_system_can_register(): void
    {
        $response = $this->post('/register', [
            'email' => 'kai@za.test',
            'password' => 'motdepasse-solide',
            'password_confirmation' => 'motdepasse-solide',
        ]);

        $response->assertRedirect('/alters/create');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('systems', ['email' => 'kai@za.test']);
    }

    public function test_a_system_can_log_in_and_out(): void
    {
        $system = System::factory()->create(['password' => Hash::make('secret-solide')]);

        $this->post('/login', ['email' => $system->email, 'password' => 'secret-solide'])
            ->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($system);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $system = System::factory()->create(['password' => Hash::make('secret-solide')]);

        $this->from('/login')
            ->post('/login', ['email' => $system->email, 'password' => 'faux'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_a_system_can_reset_its_password(): void
    {
        Notification::fake();
        $system = System::factory()->create();

        $this->post('/forgot-password', ['email' => $system->email]);

        Notification::assertSentTo($system, ResetPassword::class, function (ResetPassword $notification) use ($system) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $system->email,
                'password' => 'nouveau-mot-de-passe',
                'password_confirmation' => 'nouveau-mot-de-passe',
            ])->assertRedirect('/login');

            return true;
        });

        $this->assertTrue(Hash::check('nouveau-mot-de-passe', $system->fresh()->password));
    }

    public function test_guests_cannot_reach_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }
}
