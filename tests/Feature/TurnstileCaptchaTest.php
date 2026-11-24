<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileCaptchaTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_turnstile_when_keys_are_configured_and_token_is_verified(): void
    {
        config([
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.secret_key' => 'test-secret-key',
        ]);
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true]),
        ]);
        $user = User::factory()->create(['password' => 'password123']);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('test-site-key')
            ->assertDontSee('Kode CAPTCHA');

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
            'cf-turnstile-response' => 'valid-token',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticatedAs($user);
        Http::assertSent(fn ($request) => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['secret'] === 'test-secret-key'
            && $request['response'] === 'valid-token');
    }

    public function test_login_rejects_an_invalid_turnstile_token(): void
    {
        config([
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.secret_key' => 'test-secret-key',
        ]);
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => false]),
        ]);
        $user = User::factory()->create(['password' => 'password123']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
            'cf-turnstile-response' => 'invalid-token',
        ])->assertSessionHasErrors('cf-turnstile-response');

        $this->assertGuest();
    }
}
