<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_is_redirected_to_storefront_after_login(): void
    {
        $buyer = User::factory()->create([
            'email' => 'buyer@example.test',
            'password' => 'password123',
            'role' => 'buyer',
        ]);

        $this->get(route('supplier.payment-settings.edit'))
            ->assertRedirect(route('login'));

        $this->get(route('login'));
        $this->post(route('login'), [
            'email' => $buyer->email,
            'password' => 'password123',
            'captcha_answer' => session('web_captcha_answer'),
        ])->assertRedirect(route('home'));

        $this->get(route('home'))->assertOk()->assertSee('Kategori produk');
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('supplier.payment-settings.edit'))->assertForbidden();
    }

    public function test_supplier_can_open_supplier_payment_settings(): void
    {
        $supplier = User::factory()->create([
            'email' => 'supplier@example.test',
            'password' => 'password123',
            'role' => 'supplier',
        ]);

        $this->get(route('login'));
        $this->post(route('login'), [
            'email' => $supplier->email,
            'password' => 'password123',
            'captcha_answer' => session('web_captcha_answer'),
        ])->assertRedirect(route('dashboard'));

        $this->get(route('supplier.payment-settings.edit'))->assertOk();
    }

    public function test_suspended_account_cannot_log_in(): void
    {
        $user = User::factory()->create([
            'email' => 'suspended@example.test',
            'password' => 'password123',
            'status' => 'suspended',
        ]);

        $this->get(route('login'));
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
            'captcha_answer' => session('web_captcha_answer'),
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
