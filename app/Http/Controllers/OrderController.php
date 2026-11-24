<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()->with([
            'buyer:id,name',
            'supplier:id,name',
            'shippingAddress',
            'items.product:id,name,slug,unit',
        ]);

        if ($request->user()->role === 'buyer') {
            $query->where('buyer_id', $request->user()->id);
        } elseif ($request->user()->role === 'supplier') {
            $query->where('supplier_id', $request->user()->id);
        } elseif (! $request->user()->isAdmin()) {
            abort(403);
        }

        return response()->json(
            $query->orderByDesc('created_at')->get()->map(fn (Order $order) => $this->apiOrder($order))
        );
    }

    public function store(StoreOrderRequest $request)
    {
        $buyer = $request->user();
        $data = $request->validated();
        $quantities = collect($data['items'])
            ->groupBy('product_id')
            ->map(fn ($items) => $items->sum('quantity'));
        $shippingAddressId = $data['shipping_address_id']
            ?? $buyer->addresses()->where('is_primary', true)->value('id')
            ?? $buyer->addresses()->latest()->value('id');

        if (! $shippingAddressId) {
            throw ValidationException::withMessages([
                'shipping_address_id' => 'Tambahkan alamat pengiriman di profil sebelum membuat pesanan.',
            ]);
        }

        $order = DB::transaction(function () use ($buyer, $data, $quantities, $shippingAddressId): Order {
            $products = Product::query()
                ->with(['supplier.supplierProfile'])
                ->whereIn('id', $quantities->keys())
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($products->count() !== $quantities->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu produk tidak lagi tersedia.',
                ]);
            }

            $supplierIds = $products->pluck('supplier_id')->unique();

            if ($supplierIds->count() !== 1) {
                throw ValidationException::withMessages([
                    'items' => 'Produk dari pemasok berbeda harus dipesan secara terpisah.',
                ]);
            }

            $subtotal = 0;

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);

                if (! $product->isAvailableInMarketplace() || $quantity > $product->stock) {
                    throw ValidationException::withMessages([
                        'items' => 'Stok atau ketersediaan produk berubah. Periksa kembali pesanan.',
                    ]);
                }

                $subtotal += $product->price * $quantity;
            }

            $order = Order::create([
                'buyer_id' => $buyer->id,
                'supplier_id' => $supplierIds->first(),
                'shipping_address_id' => $shippingAddressId,
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_fee' => 0,
                'discount' => 0,
                'total' => $subtotal,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($quantities as $productId => $quantity) {
                $product = $products->get($productId);
                $stockBefore = $product->stock;
                $lineSubtotal = $product->price * $quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $lineSubtotal,
                ]);

                $product->decrement('stock', $quantity);
                StockMovement::create([
                    'supplier_id' => $product->supplier_id,
                    'product_id' => $product->id,
                    'order_id' => $order->id,
                    'type' => 'order',
                    'quantity' => -$quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockBefore - $quantity,
                    'notes' => 'Stok dipesan melalui pesanan #'.$order->id,
                ]);
            }

            return $order;
        });

        $order->load([
            'buyer:id,name',
            'supplier:id,name',
            'shippingAddress',
            'items.product:id,name,slug,unit',
        ]);

        return response()->json($this->apiOrder($order), 201);
    }

    private function apiOrder(Order $order): array
    {
        return [
            'id' => $order->id,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'delivery_status' => $order->delivery_status,
            'subtotal' => $order->subtotal,
            'shipping_fee' => $order->shipping_fee,
            'discount' => $order->discount,
            'total' => $order->total,
            'notes' => $order->notes,
            'created_at' => $order->created_at,
            'buyer' => $order->buyer?->only(['id', 'name']),
            'supplier' => $order->supplier?->only(['id', 'name']),
            'shipping_address' => $order->shippingAddress?->only([
                'id', 'full_name', 'phone', 'province', 'city', 'district', 'village', 'postal_code', 'street',
            ]),
            'items' => $order->items->map(fn (OrderItem $item) => [
                'product' => $item->product?->only(['id', 'name', 'slug', 'unit']),
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ])->values(),
        ];
    }
}
