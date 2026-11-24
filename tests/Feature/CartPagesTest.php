<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_add_update_and_remove_a_product_from_the_cart(): void
    {
        [$buyer, $supplier, $category] = $this->createBuyerAndCatalog();
        $product = $this->createProduct($supplier, $category, 'Beras', 5);

        $this->actingAs($buyer)
            ->post(route('cart.items.store', $product), ['quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $item = Cart::where('user_id', $buyer->id)->firstOrFail()->items()->firstOrFail();
        $this->assertSame(2, $item->quantity);
        $this->get(route('cart.index'))->assertOk()->assertSee('Beras')->assertSee('Rp 20.000');

        $this->patch(route('cart.items.update', $item), ['quantity' => 3])
            ->assertRedirect(route('cart.index'));
        $this->assertSame(3, $item->fresh()->quantity);

        $this->delete(route('cart.items.destroy', $item))
            ->assertRedirect(route('cart.index'));
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_creates_separate_orders_for_each_supplier_and_updates_stock(): void
    {
        [$buyer, $supplier, $category] = $this->createBuyerAndCatalog();
        $address = $buyer->addresses()->create([
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
        $secondSupplier = User::factory()->create(['role' => 'supplier']);
        $this->approveSupplier($secondSupplier);
        $firstProduct = $this->createProduct($supplier, $category, 'Beras', 5, 10000);
        $secondProduct = $this->createProduct($secondSupplier, $category, 'Tomat', 8, 5000);
        $cart = Cart::create(['user_id' => $buyer->id, 'status' => 'active']);
        $cart->items()->createMany([
            ['product_id' => $firstProduct->id, 'quantity' => 2, 'unit_price' => 10000],
            ['product_id' => $secondProduct->id, 'quantity' => 3, 'unit_price' => 5000],
        ]);

        $this->actingAs($buyer)
            ->post(route('cart.checkout'))
            ->assertRedirect(route('transactions.index'));

        $this->assertDatabaseCount('orders', 2);
        $this->assertSame(
            [$address->id, $address->id],
            Order::orderBy('id')->pluck('shipping_address_id')->all()
        );
        $this->assertDatabaseCount('order_items', 2);
        $this->assertSame(3, $firstProduct->fresh()->stock);
        $this->assertSame(5, $secondProduct->fresh()->stock);
        $this->assertSame('checked_out', $cart->fresh()->status);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_buyer_cannot_change_another_buyers_cart_item(): void
    {
        [$buyer, $supplier, $category] = $this->createBuyerAndCatalog();
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $product = $this->createProduct($supplier, $category, 'Beras', 5);
        $cart = Cart::create(['user_id' => $otherBuyer->id, 'status' => 'active']);
        $item = $cart->items()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => $product->price,
        ]);

        $this->actingAs($buyer)
            ->patch(route('cart.items.update', $item), ['quantity' => 2])
            ->assertNotFound();
    }

    private function createBuyerAndCatalog(): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $supplier = User::factory()->create(['role' => 'supplier']);
        $this->approveSupplier($supplier);
        $category = Category::create([
            'name' => 'Sayur',
            'slug' => 'sayur',
            'is_active' => true,
        ]);

        return [$buyer, $supplier, $category];
    }

    private function approveSupplier(User $supplier): void
    {
        SupplierProfile::create([
            'user_id' => $supplier->id,
            'business_name' => $supplier->name,
            'verification_status' => 'approved',
        ]);
    }

    private function createProduct(User $supplier, Category $category, string $name, int $stock, int $price = 10000): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => $name,
            'slug' => strtolower($name),
            'unit' => 'kg',
            'unit_quantity' => 1,
            'price' => $price,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }
}
