<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $buyer = $request->user();
        abort_unless($buyer->isBuyer(), 403);

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $eligibleOrder = Order::query()
            ->where('buyer_id', $buyer->id)
            ->where('status', 'completed')
            ->where('delivery_status', 'delivered')
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->latest()
            ->first();

        if (! $eligibleOrder) {
            throw ValidationException::withMessages([
                'rating' => 'Ulasan hanya dapat diberikan setelah produk diterima.',
            ]);
        }

        if (Review::where('user_id', $buyer->id)->where('product_id', $product->id)->exists()) {
            throw ValidationException::withMessages([
                'rating' => 'Anda sudah memberikan ulasan untuk produk ini.',
            ]);
        }

        Review::create([
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
        ]);

        if ($product->isAvailableInMarketplace()) {
            return redirect()->route('products.show', $product)
                ->with('success', 'Terima kasih, ulasan Anda berhasil dikirim.');
        }

        return redirect()->route('transactions.show', $eligibleOrder)
            ->with('success', 'Terima kasih, ulasan Anda berhasil dikirim.');
    }
}
