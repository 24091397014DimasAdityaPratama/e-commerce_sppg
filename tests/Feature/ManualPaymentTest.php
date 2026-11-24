<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_submit_transfer_proof_supplier_can_confirm_and_both_can_download_receipt(): void
    {
        Storage::fake('local');

        [$buyer, $supplier, $order] = $this->createOrderWithPaymentDetails();

        $this->actingAs($buyer)
            ->post(route('transactions.payment.submit', $order), [
                'payment_method' => 'bank_transfer',
                'payment_proof' => UploadedFile::fake()->image('transfer.jpg'),
            ])
            ->assertRedirect(route('transactions.show', $order));

        $order->refresh();
        $this->assertSame('submitted', $order->payment_status);
        $this->assertSame('bank_transfer', $order->payment_method);
        Storage::disk('local')->assertExists($order->payment_proof_path);

        $this->actingAs($supplier)
            ->patch(route('transactions.payment.verify', $order), [
                'payment_status' => 'paid',
            ])
            ->assertRedirect(route('transactions.show', $order));

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->payment_verified_at);

        $this->actingAs($buyer)
            ->get(route('transactions.receipt', $order))
            ->assertDownload('kwitansi-pembayaran-'.$order->id.'.pdf')
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($buyer)
            ->get(route('transactions.invoice', $order))
            ->assertDownload('nota-pembelian-'.$order->id.'.pdf');
    }

    public function test_supplier_can_configure_bank_details_and_store_logo(): void
    {
        Storage::fake('public');

        $supplier = User::factory()->create(['role' => 'supplier']);
        $buyer = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($supplier)
            ->put(route('supplier.payment-settings.update'), [
                'business_name' => 'Toko Pangan',
                'bank_name' => 'Bank Contoh',
                'bank_account_name' => 'Toko Pangan',
                'bank_account_number' => '1234567890',
                'business_logo' => UploadedFile::fake()->image('logo.png'),
            ])
            ->assertRedirect(route('supplier.payment-settings.edit'));

        $profile = SupplierProfile::where('user_id', $supplier->id)->firstOrFail();
        Storage::disk('public')->assertExists($profile->business_logo_path);
        $this->assertSame('Bank Contoh', $profile->bank_name);
        $this->assertSame('1234567890', $profile->bank_account_number);
    }

    public function test_unpaid_order_has_no_payment_receipt(): void
    {
        [$buyer, , $order] = $this->createOrderWithPaymentDetails();

        $this->actingAs($buyer)
            ->get(route('transactions.receipt', $order))
            ->assertNotFound();
    }

    private function createOrderWithPaymentDetails(): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $supplier = User::factory()->create(['role' => 'supplier']);
        SupplierProfile::create([
            'user_id' => $supplier->id,
            'business_name' => 'Toko Pangan',
            'verification_status' => 'approved',
            'bank_name' => 'Bank Contoh',
            'bank_account_name' => 'Toko Pangan',
            'bank_account_number' => '1234567890',
        ]);

        $product = $this->createProduct($supplier);
        $order = Order::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 15000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 15000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 15000,
            'subtotal' => 15000,
        ]);

        return [$buyer, $supplier, $order];
    }

    private function createProduct(User $supplier): \App\Models\Product
    {
        $category = \App\Models\Category::create([
            'name' => 'Pangan',
            'slug' => 'pangan-'.uniqid(),
            'is_active' => true,
        ]);

        return \App\Models\Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Beras',
            'slug' => 'beras-'.uniqid(),
            'unit' => 'kg',
            'unit_quantity' => 1,
            'price' => 15000,
            'stock' => 5,
            'is_active' => true,
        ]);
    }
}
