<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MidtransQrisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => 'test-server-key',
            'services.midtrans.is_production' => false,
            'services.midtrans.base_url' => 'https://api.sandbox.midtrans.com',
        ]);
    }

    public function test_buyer_can_create_exact_amount_dynamic_qris_and_load_its_image(): void
    {
        Http::fake([
            'api.sandbox.midtrans.com/v2/charge' => Http::response([
                'transaction_id' => 'midtrans-transaction-123',
                'actions' => [
                    [
                        'name' => 'generate-qr-code',
                        'url' => 'https://api.sandbox.midtrans.com/v2/qris/example/qr-code',
                    ],
                ],
            ], 201),
            'api.sandbox.midtrans.com/v2/qris/example/qr-code' => Http::response(
                'png-image-content',
                200,
                ['Content-Type' => 'image/png']
            ),
        ]);

        [$buyer, $order] = $this->createOrder();

        $this->actingAs($buyer)
            ->post(route('transactions.qris.create', $order))
            ->assertRedirect(route('transactions.show', $order));

        $order->refresh();
        $this->assertSame('qris', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('midtrans-transaction-123', $order->gateway_transaction_id);
        $this->assertSame('https://api.sandbox.midtrans.com/v2/qris/example/qr-code', $order->qris_action_url);

        Http::assertSent(function ($request): bool {
            return str_ends_with($request->url(), '/v2/charge')
                && $request['payment_type'] === 'qris'
                && $request['qris']['acquirer'] === 'gopay'
                && $request['transaction_details']['gross_amount'] === 25000;
        });

        $this->get(route('transactions.qris.image', $order))
            ->assertOk()
            ->assertHeader('content-type', 'image/png')
            ->assertSee('png-image-content');
    }

    public function test_valid_midtrans_notification_confirms_payment_using_verified_status(): void
    {
        Http::fake([
            'api.sandbox.midtrans.com/v2/*/status' => Http::response([
                'order_id' => 'SPPG-1-ABC12345',
                'gross_amount' => '25000.00',
                'payment_type' => 'qris',
                'transaction_status' => 'settlement',
            ]),
        ]);

        [, $order] = $this->createOrder();
        $order->update([
            'payment_method' => 'qris',
            'payment_status' => 'pending',
            'gateway_order_id' => 'SPPG-1-ABC12345',
            'qris_action_url' => 'https://api.sandbox.midtrans.com/v2/qris/example/qr-code',
        ]);

        $notification = [
            'order_id' => $order->gateway_order_id,
            'status_code' => '200',
            'gross_amount' => '25000.00',
        ];
        $notification['signature_key'] = hash(
            'sha512',
            $notification['order_id'].$notification['status_code'].$notification['gross_amount'].'test-server-key'
        );

        $this->postJson('/api/v1/payments/midtrans/notification', $notification)
            ->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertNotNull($order->fresh()->payment_verified_at);
    }

    public function test_invalid_midtrans_signature_does_not_change_payment_status(): void
    {
        Http::fake();

        [, $order] = $this->createOrder();
        $order->update([
            'payment_method' => 'qris',
            'gateway_order_id' => 'SPPG-1-ABC12345',
        ]);

        $this->postJson('/api/v1/payments/midtrans/notification', [
            'order_id' => $order->gateway_order_id,
            'status_code' => '200',
            'gross_amount' => '25000.00',
            'signature_key' => 'invalid',
        ])->assertForbidden();

        $this->assertSame('pending', $order->fresh()->payment_status);
        Http::assertNothingSent();
    }

    private function createOrder(): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $supplier = User::factory()->create(['role' => 'supplier']);
        $category = \App\Models\Category::create([
            'name' => 'Beras',
            'slug' => 'beras-test-'.uniqid(),
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Beras Premium',
            'slug' => 'beras-premium-'.uniqid(),
            'unit' => 'kg',
            'unit_quantity' => 1,
            'price' => 25000,
            'stock' => 10,
            'is_active' => true,
        ]);
        $order = Order::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 25000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 25000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 25000,
            'subtotal' => 25000,
        ]);

        return [$buyer, $order];
    }
}
