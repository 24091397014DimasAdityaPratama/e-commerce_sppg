<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_login_shows_a_password_error_popup_and_recovery_link(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);

        $this->get(route('login'));
        $this->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
                'captcha_answer' => session('web_captcha_answer'),
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('login_failed', true)
            ->assertSessionHasErrors('email');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-login-error', false)
            ->assertSee('Email atau kata sandi salah. Silakan periksa kembali lalu coba lagi.')
            ->assertSee('Lupa kata sandi?')
            ->assertSee('Kode CAPTCHA');
    }

    public function test_login_rejects_an_incorrect_captcha_answer(): void
    {
        $user = User::factory()->create(['password' => 'correct-password']);
        $this->get(route('login'));

        $this->from(route('login'))
            ->post(route('login'), [
                'email' => $user->email,
                'password' => 'correct-password',
                'captcha_answer' => session('web_captcha_answer') + 1,
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('captcha_answer');

        $this->assertGuest();
    }

    public function test_password_reset_link_can_be_requested_and_used(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $apiToken = app(JwtService::class)->createToken($user);

        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa kata sandi?');

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        $token = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
            $token = $notification->token;

            return true;
        });

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Atur kata sandi baru');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'a-new-password',
            'password_confirmation' => 'a-new-password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        $this->assertTrue(Hash::check('a-new-password', $user->fresh()->password));
        $this->withToken($apiToken)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }
}
