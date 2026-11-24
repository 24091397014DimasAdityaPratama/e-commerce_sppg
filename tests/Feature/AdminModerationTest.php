<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_hides_products_with_purchase_history_instead_of_deleting_order_records(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
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
            'stock' => 2,
            'is_active' => true,
        ]);
        $order = Order::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'subtotal' => 10000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 10000,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Wortel');

        $this->actingAs($admin)
            ->delete(route('admin.products.destroy', $product))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => 0]);
        $this->assertDatabaseHas('order_items', ['id' => $item->id, 'product_id' => $product->id]);
    }

    public function test_admin_can_block_and_restore_an_account_and_blocked_sessions_are_logged_out(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee($buyer->email);

        $this->actingAs($admin)
            ->patch(route('admin.users.status.update', $buyer), ['status' => 'blocked'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $buyer->id,
            'status' => 'blocked',
            'api_token_version' => 1,
        ]);

        $this->actingAs($buyer->refresh())
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->actingAs($admin)
            ->patch(route('admin.users.status.update', $buyer), ['status' => 'active'])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'id' => $buyer->id,
            'status' => 'active',
            'api_token_version' => 2,
        ]);
    }

    public function test_admin_cannot_block_their_own_account(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.users.status.update', $admin), ['status' => 'blocked'])
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'active']);
    }
}
