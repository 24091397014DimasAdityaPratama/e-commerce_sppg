<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_landing_page_has_typed_search_before_account_actions_and_loading_indicator(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('role="search"', false)
            ->assertSeeInOrder(['placeholder="Cari produk..."', 'Buat Akun', 'Masuk'])
            ->assertSee('data-page-loading', false)
            ->assertSee('Loading, mohon tunggu...')
            ->assertSee('xl:grid-cols-5', false)
            ->assertDontSee('data-product-carousel', false)
            ->assertDontSee('data-carousel-next', false);
    }

    public function test_buyer_home_keeps_header_above_the_hero_content(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($buyer)
            ->get('/')
            ->assertOk()
            ->assertSee('sticky top-0', false)
            ->assertDontSee('absolute inset-x-0 top-0', false)
            ->assertSee('Bahan pangan pilihan, siap masuk keranjang.');
    }

    public function test_catalog_uses_shared_header_search_without_duplicate_search_form(): void
    {
        $this->get(route('catalog', ['category' => 1]))
            ->assertOk()
            ->assertSee('Cari beras, sayur, protein...')
            ->assertSee('name="category" value="1"', false)
            ->assertDontSee('Cari nama produk...');
    }
}
