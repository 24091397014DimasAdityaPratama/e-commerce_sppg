<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_create_and_view_an_order_and_supplier_can_process_it(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
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
        $supplier = User::factory()->create(['role' => 'supplier']);
        SupplierProfile::create([
            'user_id' => $supplier->id,
            'business_name' => $supplier->name,
            'verification_status' => 'approved',
        ]);
        $category = Category::create([
            'name' => 'Sayur',
            'slug' => 'sayur',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Wortel',
            'slug' => 'wortel',
            'unit' => 'kg',
            'unit_quantity' => 1,
            'price' => 10000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $this->actingAs($buyer)
            ->post(route('products.orders.store', $product), ['quantity' => 2])
            ->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame('pending', $order->status);
        $this->assertSame($address->id, $order->shipping_address_id);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_movements', [
            'order_id' => $order->id,
            'stock_before' => 5,
            'stock_after' => 3,
        ]);

        $this->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Pesanan #'.$order->id)
            ->assertSee('Wortel');

        $this->actingAs($supplier)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('Pesanan #'.$order->id);

        $order->update(['payment_status' => 'paid']);

        $this->patch(route('transactions.status.update', $order), ['status' => 'processing'])
            ->assertRedirect(route('transactions.show', $order));

        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_buyer_cannot_view_another_buyers_order(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $supplier = User::factory()->create(['role' => 'supplier']);
        $order = Order::create([
            'buyer_id' => $otherBuyer->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 100,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 100,
        ]);

        $this->actingAs($buyer)
            ->get(route('transactions.show', $order))
            ->assertForbidden();
    }

    public function test_supplier_cannot_mark_an_order_completed_before_delivery(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $supplier = User::factory()->create(['role' => 'supplier']);
        $order = Order::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'subtotal' => 100,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 100,
        ]);

        $this->actingAs($supplier)
            ->patch(route('transactions.status.update', $order), ['status' => 'completed'])
            ->assertSessionHasErrors('status');

        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_cancelling_order_restores_product_stock(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $supplier = User::factory()->create(['role' => 'supplier']);
        $category = Category::create([
            'name' => 'Sayur',
            'slug' => 'sayur',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Wortel',
            'slug' => 'wortel',
            'unit' => 'kg',
            'unit_quantity' => 1,
            'price' => 10000,
            'stock' => 3,
            'is_active' => true,
        ]);
        $order = Order::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 20000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 20000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10000,
            'subtotal' => 20000,
        ]);

        $this->actingAs($supplier)
            ->patch(route('transactions.status.update', $order), ['status' => 'cancelled'])
            ->assertRedirect(route('transactions.show', $order));

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status);
    }
}
