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

class ProductReviewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_can_review_a_product_after_delivery_only_once(): void
    {
        [$buyer, $product, $order] = $this->createDeliveredProductOrder();

        $this->actingAs($buyer)
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Kirim ulasan');

        $this->actingAs($buyer)
            ->post(route('products.reviews.store', $product), [
                'rating' => 5,
                'comment' => 'Kualitas baik',
            ])
            ->assertRedirect(route('products.show', $product));

        $this->assertDatabaseHas('reviews', [
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'rating' => 5,
            'comment' => 'Kualitas baik',
        ]);

        $this->actingAs($buyer)
            ->post(route('products.reviews.store', $product), ['rating' => 4])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_buyer_cannot_review_before_a_delivered_order(): void
    {
        [$buyer, $product, $order] = $this->createDeliveredProductOrder();
        $order->update(['delivery_status' => 'shipped', 'status' => 'processing']);

        $this->actingAs($buyer)
            ->post(route('products.reviews.store', $product), ['rating' => 5])
            ->assertSessionHasErrors('rating');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_rating_must_be_between_one_and_five(): void
    {
        [$buyer, $product] = $this->createDeliveredProductOrder();

        $this->actingAs($buyer)
            ->post(route('products.reviews.store', $product), ['rating' => 6])
            ->assertSessionHasErrors('rating');
    }

    private function createDeliveredProductOrder(): array
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
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
            'stock' => 3,
            'is_active' => true,
        ]);
        $order = Order::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
            'status' => 'completed',
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'subtotal' => 10000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 10000,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
        ]);

        return [$buyer, $product, $order];
    }
}
