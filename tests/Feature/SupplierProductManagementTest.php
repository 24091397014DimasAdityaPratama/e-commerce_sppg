<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_edit_product_details_without_changing_stock(): void
    {
        [$supplier, $category, $product] = $this->createSupplierProduct();
        $newCategory = Category::create([
            'name' => 'Sayur',
            'slug' => 'sayur',
            'is_active' => true,
        ]);

        $this->actingAs($supplier)
            ->get(route('supplier.products.edit', $product))
            ->assertOk()
            ->assertSee('Edit Produk')
            ->assertSee('Beras Lama');

        $this->put(route('supplier.products.update', $product), [
            'category_id' => $newCategory->id,
            'name' => 'Beras Baru',
            'description' => 'Beras pilihan.',
            'price' => 18000,
            'low_stock_threshold' => 8,
            'unit' => 'kg',
            'unit_quantity' => 5,
            'is_active' => 1,
        ])->assertRedirect(route('dashboard'));

        $product->refresh();
        $this->assertSame('Beras Baru', $product->name);
        $this->assertSame('beras-baru', $product->slug);
        $this->assertSame(18000.0, (float) $product->price);
        $this->assertSame(12, $product->stock);
        $this->assertSame($newCategory->id, $product->category_id);
    }

    public function test_supplier_cannot_edit_or_delete_another_suppliers_product(): void
    {
        [$owner, $category, $product] = $this->createSupplierProduct();
        $otherSupplier = User::factory()->create(['role' => 'supplier']);

        $this->actingAs($otherSupplier)
            ->get(route('supplier.products.edit', $product))
            ->assertNotFound();

        $this->delete(route('supplier.products.destroy', $product))
            ->assertNotFound();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'supplier_id' => $owner->id]);
    }

    public function test_supplier_can_delete_product_without_history(): void
    {
        [$supplier, $category, $product] = $this->createSupplierProduct();

        $this->actingAs($supplier)
            ->delete(route('supplier.products.destroy', $product))
            ->assertRedirect()
            ->assertSessionHas('success', 'Produk berhasil dihapus.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_supplier_cannot_delete_product_with_order_history(): void
    {
        [$supplier, $category, $product] = $this->createSupplierProduct();
        $buyer = User::factory()->create(['role' => 'buyer']);
        $order = Order::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 12000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 12000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 12000,
            'subtotal' => 12000,
        ]);

        $this->actingAs($supplier)
            ->delete(route('supplier.products.destroy', $product))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
        $this->assertDatabaseHas('order_items', ['product_id' => $product->id]);
    }

    private function createSupplierProduct(): array
    {
        $supplier = User::factory()->create(['role' => 'supplier']);
        $category = Category::create([
            'name' => 'Beras & Serealia',
            'slug' => 'beras-serealia',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Beras Lama',
            'slug' => 'beras-lama',
            'description' => 'Beras lokal.',
            'unit' => 'kg',
            'unit_quantity' => 5,
            'price' => 12000,
            'stock' => 12,
            'low_stock_threshold' => 5,
            'is_active' => true,
        ]);

        return [$supplier, $category, $product];
    }
}
