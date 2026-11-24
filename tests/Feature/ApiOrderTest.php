<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\SupplierProfile;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_create_a_stock_checked_order_with_an_owned_address(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $address = $this->createAddress($buyer);
        [$supplier, $product] = $this->createProduct('beras', 5);
        $token = app(JwtService::class)->createToken($buyer);

        $this->withToken($token)->postJson('/api/v1/orders', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2],
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ])->assertCreated()
            ->assertJsonPath('shipping_address.id', $address->id)
            ->assertJsonPath('items.0.quantity', 3)
            ->assertJsonMissingPath('buyer.email')
            ->assertJsonMissingPath('supplier.email');

        $this->assertSame(2, $product->fresh()->stock);
        $this->assertDatabaseHas('orders', [
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
            'shipping_address_id' => $address->id,
            'total' => 36000,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'quantity' => -3,
            'stock_before' => 5,
            'stock_after' => 2,
        ]);
    }

    public function test_api_order_rejects_an_address_owned_by_another_buyer(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $address = $this->createAddress($otherBuyer);
        [, $product] = $this->createProduct('beras', 5);
        $token = app(JwtService::class)->createToken($buyer);

        $this->withToken($token)->postJson('/api/v1/orders', [
            'shipping_address_id' => $address->id,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('shipping_address_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_api_order_requires_a_saved_shipping_address(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        [, $product] = $this->createProduct('beras', 5);
        $token = app(JwtService::class)->createToken($buyer);

        $this->withToken($token)->postJson('/api/v1/orders', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('shipping_address_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_api_order_rejects_products_from_multiple_suppliers(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $address = $this->createAddress($buyer);
        [, $firstProduct] = $this->createProduct('beras', 5);
        [, $secondProduct] = $this->createProduct('gula', 5);
        $token = app(JwtService::class)->createToken($buyer);

        $this->withToken($token)->postJson('/api/v1/orders', [
            'shipping_address_id' => $address->id,
            'items' => [
                ['product_id' => $firstProduct->id, 'quantity' => 1],
                ['product_id' => $secondProduct->id, 'quantity' => 1],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(5, $firstProduct->fresh()->stock);
        $this->assertSame(5, $secondProduct->fresh()->stock);
        $this->assertSame(0, StockMovement::count());
    }

    public function test_api_order_rejects_insufficient_stock_without_partial_changes(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $address = $this->createAddress($buyer);
        [, $product] = $this->createProduct('beras', 2);
        $token = app(JwtService::class)->createToken($buyer);

        $this->withToken($token)->postJson('/api/v1/orders', [
            'shipping_address_id' => $address->id,
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $product->fresh()->stock);
        $this->assertSame(0, StockMovement::count());
    }

    private function createAddress(User $user): Address
    {
        return $user->addresses()->create([
            'type' => 'delivery',
            'full_name' => 'Penanggung Jawab',
            'phone' => '081234567890',
            'province' => 'Jawa Barat',
            'city' => 'Kota Bandung',
            'district' => 'Coblong',
            'village' => 'Dago',
            'postal_code' => '40135',
            'street' => 'Jl. Merdeka No. 10',
            'is_primary' => true,
        ]);
    }

    private function createProduct(string $slug, int $stock): array
    {
        $category = Category::create([
            'name' => 'Bahan Pokok',
            'slug' => 'bahan-pokok-'.(Category::count() + 1),
            'is_active' => true,
        ]);

        $supplier = User::factory()->create(['role' => 'supplier']);
        SupplierProfile::create([
            'user_id' => $supplier->id,
            'business_name' => $supplier->name,
            'verification_status' => 'approved',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => ucfirst($slug),
            'slug' => $slug.'-'.$supplier->id,
            'unit' => 'kg',
            'price' => 12000,
            'stock' => $stock,
            'is_active' => true,
        ]);

        return [$supplier, $product];
    }
}
