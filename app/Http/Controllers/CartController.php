<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $this->authorizeBuyer($request);

        $cart = Cart::with(['items.product.category', 'items.product.supplier.supplierProfile'])
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->first();

        return view('cart.index', compact('cart'));
    }

    public function add(Request $request, Product $product)
    {
        $this->authorizeBuyer($request);
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($request, $product, $validated): void {
            $product = Product::query()
                ->with(['supplier.supplierProfile'])
                ->lockForUpdate()
                ->findOrFail($product->id);

            if (! $product->isAvailableInMarketplace() || $product->stock < 1) {
                throw ValidationException::withMessages([
                    'quantity' => 'Produk ini sedang tidak tersedia.',
                ]);
            }

            $cart = Cart::firstOrCreate([
                'user_id' => $request->user()->id,
                'status' => 'active',
            ]);
            $item = $cart->items()->where('product_id', $product->id)->first();
            $quantity = ($item?->quantity ?? 0) + $validated['quantity'];

            if ($quantity > $product->stock) {
                throw ValidationException::withMessages([
                    'quantity' => 'Jumlah melebihi stok yang tersedia.',
                ]);
            }

            $cart->items()->updateOrCreate(
                ['product_id' => $product->id],
                ['quantity' => $quantity, 'unit_price' => $product->price]
            );
        });

        return redirect()->route('cart.index')->with('success', 'Produk berhasil ditambahkan ke keranjang.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $this->authorizeBuyer($request);
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->activeCartFor($request, $cartItem);
        $product = $cartItem->product->loadMissing('supplier.supplierProfile');

        if (! $product->isAvailableInMarketplace() || $validated['quantity'] > $product->stock) {
            throw ValidationException::withMessages([
                'quantity' => 'Jumlah melebihi stok yang tersedia atau produk sudah tidak aktif.',
            ]);
        }

        $cartItem->update([
            'quantity' => $validated['quantity'],
            'unit_price' => $product->price,
        ]);

        return redirect()->route('cart.index')->with('success', 'Jumlah produk di keranjang diperbarui.');
    }

    public function remove(Request $request, CartItem $cartItem)
    {
        $this->authorizeBuyer($request);
        $this->activeCartFor($request, $cartItem);
        $cartItem->delete();

        return redirect()->route('cart.index')->with('success', 'Produk dihapus dari keranjang.');
    }

    public function checkout(Request $request)
    {
        $this->authorizeBuyer($request);
        $user = $request->user();
        DB::transaction(function () use ($user): void {
            $cart = Cart::query()
                ->where('user_id', $user->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                throw ValidationException::withMessages([
                    'cart' => 'Keranjang masih kosong.',
                ]);
            }

            $items = $cart->items()->orderBy('product_id')->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'Keranjang masih kosong.',
                ]);
            }

            $shippingAddress = $user->addresses()->where('is_primary', true)->first()
                ?? $user->addresses()->latest()->first();

            if (! $shippingAddress) {
                throw ValidationException::withMessages([
                    'address' => 'Tambahkan alamat pengiriman di halaman profil sebelum checkout.',
                ]);
            }

            $products = [];
            foreach ($items as $item) {
                $product = Product::query()
                    ->with(['supplier.supplierProfile'])
                    ->lockForUpdate()
                    ->findOrFail($item->product_id);

                if (! $product->isAvailableInMarketplace() || $product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'cart' => 'Stok atau ketersediaan produk berubah. Periksa kembali keranjang Anda.',
                    ]);
                }

                $products[$product->id] = $product;
            }

            $ordersBySupplier = [];
            foreach ($items as $item) {
                $product = $products[$item->product_id];
                $ordersBySupplier[$product->supplier_id][] = [$item, $product];
            }

            foreach ($ordersBySupplier as $supplierId => $supplierItems) {
                $subtotal = collect($supplierItems)->sum(
                    fn (array $entry) => $entry[1]->price * $entry[0]->quantity
                );
                $order = Order::create([
                    'buyer_id' => $user->id,
                    'supplier_id' => $supplierId,
                    'shipping_address_id' => $shippingAddress?->id,
                    'status' => 'pending',
                    'payment_status' => 'pending',
                    'subtotal' => $subtotal,
                    'shipping_fee' => 0,
                    'discount' => 0,
                    'total' => $subtotal,
                ]);
                foreach ($supplierItems as [$item, $product]) {
                    $before = $product->stock;
                    $product->decrement('stock', $item->quantity);
                    $itemSubtotal = $product->price * $item->quantity;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => $item->quantity,
                        'unit_price' => $product->price,
                        'subtotal' => $itemSubtotal,
                    ]);

                    StockMovement::create([
                        'supplier_id' => $product->supplier_id,
                        'product_id' => $product->id,
                        'order_id' => $order->id,
                        'type' => 'order',
                        'quantity' => -$item->quantity,
                        'stock_before' => $before,
                        'stock_after' => $before - $item->quantity,
                        'notes' => 'Stok dipesan melalui pesanan #'.$order->id,
                    ]);
                }
            }

            $cart->items()->delete();
            $cart->update(['status' => 'checked_out']);
        });

        return redirect()->route('transactions.index')
            ->with('success', 'Checkout berhasil. Pesanan dibuat untuk setiap pemasok.');
    }

    private function activeCartFor(Request $request, CartItem $cartItem): Cart
    {
        $cart = Cart::query()
            ->whereKey($cartItem->cart_id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'active')
            ->firstOrFail();

        return $cart;
    }

    private function authorizeBuyer(Request $request): void
    {
        abort_unless($request->user()?->isBuyer(), 403, 'Hanya pembeli yang dapat menggunakan keranjang.');
    }
}
