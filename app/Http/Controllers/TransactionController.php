<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\MidtransGateway;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($user->isBuyer() || $user->isSupplier(), 403);

        $orders = Order::with(['buyer', 'supplier', 'items.product'])
            ->where($user->isBuyer() ? 'buyer_id' : 'supplier_id', $user->id)
            ->latest()
            ->paginate(10);

        return view('transactions.index', compact('orders', 'user'));
    }

    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless(
            ($user->isBuyer() && $order->buyer_id === $user->id)
            || ($user->isSupplier() && $order->supplier_id === $user->id),
            403
        );

        $order->load(['buyer', 'supplier.supplierProfile', 'items.product']);
        $midtransEnabled = app(MidtransGateway::class)->isConfigured();

        return view('transactions.show', compact('order', 'user', 'midtransEnabled'));
    }

    public function invoice(Request $request, Order $order)
    {
        $this->authorizeParticipant($request, $order);
        $order->load(['buyer', 'supplier.supplierProfile', 'items.product']);
        $storeLogoDataUri = $this->storeLogoDataUri($order);

        return Pdf::loadView('transactions.document', [
            'order' => $order,
            'documentTitle' => 'Nota Pembelian',
            'isReceipt' => false,
            'storeLogoDataUri' => $storeLogoDataUri,
        ])->setPaper('a4')->download('nota-pembelian-'.$order->id.'.pdf');
    }

    public function receipt(Request $request, Order $order)
    {
        $this->authorizeParticipant($request, $order);
        abort_unless($order->payment_status === 'paid' && $order->payment_verified_at, 404);
        $order->load(['buyer', 'supplier.supplierProfile', 'items.product']);
        $storeLogoDataUri = $this->storeLogoDataUri($order);

        return Pdf::loadView('transactions.document', [
            'order' => $order,
            'documentTitle' => 'Kwitansi Pembayaran',
            'isReceipt' => true,
            'storeLogoDataUri' => $storeLogoDataUri,
        ])->setPaper('a4')->download('kwitansi-pembayaran-'.$order->id.'.pdf');
    }

    public function paymentProof(Request $request, Order $order)
    {
        $this->authorizeParticipant($request, $order);
        abort_unless($order->payment_proof_path, 404);
        abort_unless(Storage::disk('local')->exists($order->payment_proof_path), 404);

        return Storage::disk('local')->download(
            $order->payment_proof_path,
            'bukti-pembayaran-'.$order->id.'.'.pathinfo($order->payment_proof_path, PATHINFO_EXTENSION)
        );
    }

    public function createQris(Request $request, Order $order, MidtransGateway $midtrans)
    {
        abort_unless($request->user()->isBuyer() && $order->buyer_id === $request->user()->id, 403);
        abort_if($order->status === 'cancelled', 422, 'Pesanan yang dibatalkan tidak dapat dibayar.');

        if (! $midtrans->isConfigured()) {
            throw ValidationException::withMessages([
                'payment_method' => 'Pembayaran QRIS belum dikonfigurasi. Admin perlu mengisi MIDTRANS_SERVER_KEY di file .env.',
            ]);
        }

        if ($order->payment_method === 'qris' && $order->payment_status === 'pending' && $order->qris_action_url) {
            return redirect()->route('transactions.show', $order);
        }

        if (! in_array($order->payment_status, ['pending', 'rejected', 'expired'], true)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Status pembayaran pesanan ini tidak dapat membuat QRIS baru.',
            ]);
        }

        $gatewayOrderId = 'SPPG-'.$order->id.'-'.Str::upper(Str::random(8));

        DB::transaction(function () use ($order, $gatewayOrderId): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status === 'cancelled'
                || ! in_array($lockedOrder->payment_status, ['pending', 'rejected', 'expired'], true)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'Pesanan tidak tersedia untuk pembayaran QRIS.',
                ]);
            }

            $lockedOrder->update([
                'payment_status' => 'creating',
                'payment_method' => 'qris',
                'gateway_order_id' => $gatewayOrderId,
                'gateway_transaction_id' => null,
                'qris_action_url' => null,
                'payment_proof_path' => null,
                'payment_submitted_at' => null,
                'payment_verified_at' => null,
                'payment_rejection_reason' => null,
            ]);
        });

        try {
            $charge = $midtrans->createQrisCharge(
                $gatewayOrderId,
                (int) round((float) $order->total),
                $request->user()->name,
                $request->user()->email
            );
        } catch (ConnectionException|RequestException|RuntimeException $exception) {
            Order::whereKey($order->id)
                ->where('payment_status', 'creating')
                ->update([
                    'payment_status' => 'pending',
                    'payment_method' => null,
                    'gateway_order_id' => null,
                    'gateway_transaction_id' => null,
                    'qris_action_url' => null,
                ]);

            Log::error('Failed to create Midtrans QRIS payment.', [
                'order_id' => $order->id,
                'exception' => $exception::class,
            ]);

            throw ValidationException::withMessages([
                'payment_method' => 'QRIS gagal dibuat. Silakan coba lagi atau gunakan transfer bank.',
            ]);
        }

        Order::whereKey($order->id)
            ->where('payment_status', 'creating')
            ->where('gateway_order_id', $gatewayOrderId)
            ->update([
                'payment_status' => 'pending',
                'gateway_transaction_id' => $charge['transaction_id'],
                'qris_action_url' => $charge['qr_action_url'],
            ]);

        return redirect()->route('transactions.show', $order)->with('success', 'QRIS dinamis berhasil dibuat. Bayar sesuai nominal yang tertera.');
    }

    public function qrisImage(Request $request, Order $order, MidtransGateway $midtrans)
    {
        $this->authorizeParticipant($request, $order);
        abort_unless(
            $order->payment_method === 'qris'
            && $order->payment_status === 'pending'
            && $order->qris_action_url,
            404
        );

        $image = $midtrans->fetchQrisImage($order->qris_action_url);

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function store(Request $request, Product $product)
    {
        abort_unless($request->user()->isBuyer(), 403, 'Hanya pembeli yang dapat membuat pesanan.');

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $order = DB::transaction(function () use ($request, $product, $validated) {
            $product = Product::query()
                ->with(['supplier.supplierProfile'])
                ->lockForUpdate()
                ->findOrFail($product->id);

            if (! $product->isAvailableInMarketplace()) {
                throw ValidationException::withMessages([
                    'quantity' => 'Produk ini sedang tidak tersedia.',
                ]);
            }

            if ($product->stock < $validated['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok produk tidak mencukupi.',
                ]);
            }

            $shippingAddress = $request->user()->addresses()->where('is_primary', true)->first()
                ?? $request->user()->addresses()->latest()->first();

            if (! $shippingAddress) {
                throw ValidationException::withMessages([
                    'address' => 'Tambahkan alamat pengiriman di halaman profil sebelum memesan.',
                ]);
            }

            $subtotal = $product->price * $validated['quantity'];
            $order = Order::create([
                'buyer_id' => $request->user()->id,
                'supplier_id' => $product->supplier_id,
                'shipping_address_id' => $shippingAddress?->id,
                'status' => 'pending',
                'payment_status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_fee' => 0,
                'discount' => 0,
                'total' => $subtotal,
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
                'unit_price' => $product->price,
                'subtotal' => $subtotal,
            ]);

            $stockBefore = $product->stock;
            $product->decrement('stock', $validated['quantity']);
            StockMovement::create([
                'supplier_id' => $product->supplier_id,
                'product_id' => $product->id,
                'order_id' => $order->id,
                'type' => 'order',
                'quantity' => -$validated['quantity'],
                'stock_before' => $stockBefore,
                'stock_after' => $stockBefore - $validated['quantity'],
                'notes' => 'Stok dipesan melalui pesanan #'.$order->id,
            ]);

            return $order;
        });

        return redirect()->route('transactions.show', $order)->with('success', 'Pesanan berhasil dibuat. Menunggu konfirmasi pemasok.');
    }

    public function updateStatus(Request $request, Order $order)
    {
        abort_unless($request->user()->isSupplier() && $order->supplier_id === $request->user()->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'in:processing,cancelled'],
        ]);

        DB::transaction(function () use ($order, $validated) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $allowedTransitions = [
                'pending' => ['processing', 'cancelled'],
                'processing' => ['cancelled'],
            ];

            if (! in_array($validated['status'], $allowedTransitions[$lockedOrder->status] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status pesanan tidak dapat diubah ke pilihan tersebut.',
                ]);
            }

            if ($validated['status'] === 'processing' && $lockedOrder->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'status' => 'Konfirmasi pembayaran sebelum memproses pesanan.',
                ]);
            }

            if ($validated['status'] === 'cancelled' && $lockedOrder->payment_status === 'paid') {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan yang sudah dibayar tidak dapat dibatalkan dari halaman ini. Hubungi pembeli untuk proses pengembalian dana.',
                ]);
            }

            if ($validated['status'] === 'cancelled') {
                if (in_array($lockedOrder->delivery_status, ['shipped', 'delivered'], true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Pesanan yang sudah dikirim tidak dapat dibatalkan.',
                    ]);
                }

                foreach ($lockedOrder->items as $item) {
                    $product = Product::query()->lockForUpdate()->findOrFail($item->product_id);
                    $before = $product->stock;
                    $product->increment('stock', $item->quantity);
                    StockMovement::create([
                        'supplier_id' => $lockedOrder->supplier_id,
                        'product_id' => $product->id,
                        'order_id' => $lockedOrder->id,
                        'type' => 'cancel_restore',
                        'quantity' => $item->quantity,
                        'stock_before' => $before,
                        'stock_after' => $before + $item->quantity,
                        'notes' => 'Stok dikembalikan dari pembatalan pesanan #'.$lockedOrder->id,
                    ]);
                }
            }

            $lockedOrder->update(['status' => $validated['status']]);
        });

        return redirect()->route('transactions.show', $order)->with('success', 'Status pesanan berhasil diperbarui.');
    }

    public function submitPayment(Request $request, Order $order)
    {
        abort_unless($request->user()->isBuyer() && $order->buyer_id === $request->user()->id, 403);
        abort_if($order->status === 'cancelled', 422, 'Pesanan yang dibatalkan tidak dapat dibayar.');
        abort_unless(in_array($order->payment_status, ['pending', 'rejected', 'expired'], true), 422, 'Pembayaran sudah dikirim atau dikonfirmasi.');

        $validated = $request->validate([
            'payment_method' => ['required', 'in:bank_transfer'],
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);

        $order->load('supplier.supplierProfile');
        $profile = $order->supplier->supplierProfile;
        $method = $validated['payment_method'];

        if ($method === 'bank_transfer' && (! $profile?->bank_name || ! $profile?->bank_account_name || ! $profile?->bank_account_number)) {
            throw ValidationException::withMessages([
                'payment_method' => 'Informasi rekening bank toko belum lengkap.',
            ]);
        }

        $proofPath = $request->file('payment_proof')->store('payment-proofs', 'local');

        if ($proofPath === false) {
            throw new RuntimeException('Bukti pembayaran gagal disimpan.');
        }

        if ($order->payment_proof_path) {
            Storage::disk('local')->delete($order->payment_proof_path);
        }

        $order->update([
            'payment_method' => $method,
            'payment_proof_path' => $proofPath,
            'payment_status' => 'submitted',
            'payment_submitted_at' => now(),
            'payment_verified_at' => null,
            'payment_rejection_reason' => null,
        ]);

        return redirect()->route('transactions.show', $order)->with('success', 'Bukti pembayaran berhasil dikirim dan menunggu konfirmasi pemasok.');
    }

    public function verifyPayment(Request $request, Order $order)
    {
        abort_unless($request->user()->isSupplier() && $order->supplier_id === $request->user()->id, 403);

        $validated = $request->validate([
            'payment_status' => ['required', 'in:paid,rejected'],
            'payment_rejection_reason' => ['required_if:payment_status,rejected', 'nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($order, $validated) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status === 'cancelled' || $lockedOrder->payment_status !== 'submitted') {
                throw ValidationException::withMessages([
                    'payment_status' => 'Tidak ada pembayaran yang dapat diverifikasi untuk pesanan ini.',
                ]);
            }

            if ($lockedOrder->payment_method !== 'bank_transfer') {
                throw ValidationException::withMessages([
                    'payment_status' => 'Pembayaran QRIS diverifikasi otomatis oleh Midtrans.',
                ]);
            }

            $lockedOrder->update([
                'payment_status' => $validated['payment_status'],
                'payment_verified_at' => $validated['payment_status'] === 'paid' ? now() : null,
                'payment_rejection_reason' => $validated['payment_status'] === 'rejected'
                    ? $validated['payment_rejection_reason']
                    : null,
            ]);
        });

        return redirect()->route('transactions.show', $order)->with(
            'success',
            $validated['payment_status'] === 'paid'
                ? 'Pembayaran telah dikonfirmasi.'
                : 'Pembayaran ditolak. Pembeli dapat mengirim ulang bukti pembayaran.'
        );
    }

    private function authorizeParticipant(Request $request, Order $order): void
    {
        $user = $request->user();
        abort_unless(
            ($user->isBuyer() && $order->buyer_id === $user->id)
            || ($user->isSupplier() && $order->supplier_id === $user->id),
            403
        );
    }

    private function storeLogoDataUri(Order $order): ?string
    {
        $logoPath = $order->supplier->supplierProfile?->business_logo_path;

        if (! $logoPath || ! Storage::disk('public')->exists($logoPath)) {
            return null;
        }

        $mimeType = Storage::disk('public')->mimeType($logoPath);

        if (! in_array($mimeType, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('Format logo toko tidak didukung untuk nota PDF.');
        }

        return 'data:'.$mimeType.';base64,'.base64_encode(Storage::disk('public')->get($logoPath));
    }
}
