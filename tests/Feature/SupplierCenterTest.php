<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\SupplierExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_supplier_is_told_that_their_store_is_not_public(): void
    {
        $supplier = User::factory()->create(['role' => 'supplier']);

        $this->actingAs($supplier)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Toko belum tampil untuk buyer karena verifikasi NIB masih menunggu persetujuan admin.')
            ->assertSee(route('supplier.payment-settings.edit'));
    }

    public function test_supplier_can_update_stock_and_configure_low_stock_threshold(): void
    {
        $supplier = User::factory()->create(['role' => 'supplier']);
        $product = $this->createProduct($supplier, ['stock' => 6]);

        $this->actingAs($supplier)
            ->get(route('supplier.inventory'))
            ->assertOk()
            ->assertSee($product->name);

        $this->post(route('supplier.inventory.update', $product), [
            'movement_type' => 'incoming',
            'quantity' => 4,
            'low_stock_threshold' => 12,
            'notes' => 'Stok baru',
        ])->assertRedirect(route('supplier.inventory'));

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(12, $product->fresh()->low_stock_threshold);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'type' => 'incoming',
            'quantity' => 4,
            'stock_before' => 6,
            'stock_after' => 10,
        ]);
    }

    public function test_supplier_can_record_and_export_operating_expenses(): void
    {
        $supplier = User::factory()->create(['role' => 'supplier']);

        $this->actingAs($supplier)
            ->get(route('supplier.finance'))
            ->assertOk()
            ->assertSee('Arus Kas & Keuangan', false);

        $this->post(route('supplier.expenses.store'), [
            'title' => 'Bensin pengiriman',
            'category' => 'transportasi',
            'amount' => 25000,
            'expense_date' => now()->toDateString(),
        ])->assertRedirect(route('supplier.finance'));

        $expense = SupplierExpense::firstOrFail();
        $this->assertSame('25000.00', $expense->amount);

        $this->get(route('supplier.finance.export'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->delete(route('supplier.expenses.destroy', $expense))
            ->assertRedirect(route('supplier.finance'));

        $this->assertDatabaseMissing('supplier_expenses', ['id' => $expense->id]);
    }

    public function test_supplier_can_ship_an_order_and_upload_delivery_proof(): void
    {
        Storage::fake('local');
        $supplier = User::factory()->create(['role' => 'supplier']);
        $buyer = User::factory()->create(['role' => 'buyer']);
        $order = Order::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_verified_at' => now(),
            'subtotal' => 30000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 30000,
        ]);

        $this->actingAs($supplier)
            ->get(route('supplier.distribution'))
            ->assertOk()
            ->assertSee('Pesanan #'.$order->id);

        $this->patch(route('supplier.distribution.update', $order), [
            'delivery_action' => 'ship',
            'courier_name' => 'Kurir toko',
            'tracking_number' => 'RESI123',
        ])->assertRedirect(route('supplier.distribution'));

        $this->assertSame('shipped', $order->fresh()->delivery_status);

        $this->patch(route('supplier.distribution.update', $order), [
            'delivery_action' => 'deliver',
            'delivery_proof' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect(route('supplier.distribution'));

        $order->refresh();
        $this->assertSame('delivered', $order->delivery_status);
        $this->assertSame('completed', $order->status);
        Storage::disk('local')->assertExists($order->delivery_proof_path);
        $this->actingAs($supplier)
            ->get(route('supplier.distribution.proof', $order))
            ->assertOk();
    }

    public function test_supplier_cannot_adjust_another_suppliers_stock(): void
    {
        $supplier = User::factory()->create(['role' => 'supplier']);
        $otherSupplier = User::factory()->create(['role' => 'supplier']);
        $product = $this->createProduct($otherSupplier);

        $this->actingAs($supplier)
            ->post(route('supplier.inventory.update', $product), [
                'movement_type' => 'incoming',
                'quantity' => 2,
            ])
            ->assertNotFound();

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(0, StockMovement::count());
    }

    private function createProduct(User $supplier, array $attributes = []): Product
    {
        $category = Category::create([
            'name' => 'Sayur',
            'slug' => 'sayur-'.$supplier->id,
            'is_active' => true,
        ]);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Wortel Segar',
            'slug' => 'wortel-segar-'.$supplier->id,
            'unit' => 'kg',
            'unit_quantity' => 1,
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ], $attributes));
    }
}
