<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\SupplierExpense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class SupplierCenterController extends Controller
{
    public function inventory(Request $request)
    {
        $supplier = $this->supplier($request);
        $products = $supplier->products()
            ->with('category')
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('stock')
            ->paginate(15)
            ->withQueryString();
        $movements = StockMovement::with('product')
            ->where('supplier_id', $supplier->id)
            ->latest()
            ->take(20)
            ->get();

        return view('supplier.inventory', compact('products', 'movements'));
    }

    public function updateStock(Request $request, Product $product)
    {
        $supplier = $this->supplier($request);
        abort_unless($product->supplier_id === $supplier->id, 404);

        $validated = $request->validate([
            'movement_type' => ['required', 'in:incoming,outgoing,adjustment'],
            'quantity' => ['required_if:movement_type,incoming,outgoing', 'nullable', 'integer', 'min:1'],
            'new_stock' => ['required_if:movement_type,adjustment', 'nullable', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($product, $supplier, $validated): void {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $before = $lockedProduct->stock;

            $after = match ($validated['movement_type']) {
                'incoming' => $before + (int) $validated['quantity'],
                'outgoing' => $before - (int) $validated['quantity'],
                'adjustment' => (int) $validated['new_stock'],
            };

            if ($after < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Stok keluar melebihi stok yang tersedia.',
                ]);
            }

            $lockedProduct->update([
                'stock' => $after,
                'low_stock_threshold' => $validated['low_stock_threshold'] ?? $lockedProduct->low_stock_threshold,
            ]);
            StockMovement::create([
                'supplier_id' => $supplier->id,
                'product_id' => $lockedProduct->id,
                'type' => $validated['movement_type'],
                'quantity' => $after - $before,
                'stock_before' => $before,
                'stock_after' => $after,
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        return redirect()->route('supplier.inventory')->with('success', 'Stok dan riwayat persediaan berhasil diperbarui.');
    }

    public function finance(Request $request)
    {
        $supplier = $this->supplier($request);
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now())->endOfDay();

        $paidOrders = $supplier->supplierOrders()
            ->where('payment_status', 'paid')
            ->whereBetween('payment_verified_at', [$from, $to]);
        $revenue = (clone $paidOrders)->sum('total');
        $orders = (clone $paidOrders)->with('buyer')->latest('payment_verified_at')->paginate(10)->withQueryString();
        $expenses = SupplierExpense::where('supplier_id', $supplier->id)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->latest('expense_date')
            ->paginate(10, ['*'], 'expense_page')
            ->withQueryString();
        $expenseTotal = SupplierExpense::where('supplier_id', $supplier->id)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->sum('amount');
        $unpaidTotal = $supplier->supplierOrders()->whereIn('payment_status', ['pending', 'submitted'])->sum('total');

        return view('supplier.finance', [
            'orders' => $orders,
            'expenses' => $expenses,
            'revenue' => $revenue,
            'expenseTotal' => $expenseTotal,
            'netCashflow' => $revenue - $expenseTotal,
            'unpaidTotal' => $unpaidTotal,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function storeExpense(Request $request)
    {
        $supplier = $this->supplier($request);
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:operasional,transportasi,bahan_baku,kemasan,lainnya'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        SupplierExpense::create([
            ...$validated,
            'supplier_id' => $supplier->id,
        ]);

        return redirect()->route('supplier.finance')->with('success', 'Pengeluaran berhasil dicatat.');
    }

    public function deleteExpense(Request $request, SupplierExpense $expense)
    {
        $supplier = $this->supplier($request);
        abort_unless($expense->supplier_id === $supplier->id, 404);
        $expense->delete();

        return redirect()->route('supplier.finance')->with('success', 'Catatan pengeluaran berhasil dihapus.');
    }

    public function distribution(Request $request)
    {
        $supplier = $this->supplier($request);
        $orders = $supplier->supplierOrders()
            ->with(['buyer', 'items.product', 'shippingAddress'])
            ->whereIn('status', ['processing', 'completed'])
            ->latest()
            ->paginate(15);

        return view('supplier.distribution', compact('orders'));
    }

    public function updateDelivery(Request $request, Order $order)
    {
        $supplier = $this->supplier($request);
        abort_unless($order->supplier_id === $supplier->id, 404);

        $validated = $request->validate([
            'delivery_action' => ['required', 'in:ship,deliver'],
            'courier_name' => ['required_if:delivery_action,ship', 'nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'delivery_proof' => ['required_if:delivery_action,deliver', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $proofPath = null;

        if ($request->hasFile('delivery_proof')) {
            $proofPath = $request->file('delivery_proof')->store('delivery-proofs', 'local');

            if ($proofPath === false) {
                throw new \RuntimeException('Bukti pengiriman gagal disimpan.');
            }
        }

        try {
            $previousProofPath = DB::transaction(function () use ($order, $validated, $proofPath): ?string {
                $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

                if ($lockedOrder->payment_status !== 'paid' || $lockedOrder->status !== 'processing') {
                    throw ValidationException::withMessages([
                        'delivery_action' => 'Pesanan harus sudah dibayar dan diproses sebelum dikirim.',
                    ]);
                }

                if ($validated['delivery_action'] === 'ship' && $lockedOrder->delivery_status !== 'not_started') {
                    throw ValidationException::withMessages([
                        'delivery_action' => 'Pesanan hanya dapat ditandai dikirim satu kali.',
                    ]);
                }

                if ($validated['delivery_action'] === 'deliver' && $lockedOrder->delivery_status !== 'shipped') {
                    throw ValidationException::withMessages([
                        'delivery_action' => 'Pesanan harus berstatus sedang dikirim sebelum diselesaikan.',
                    ]);
                }

                $previousProofPath = $lockedOrder->delivery_proof_path;
                $lockedOrder->update([
                    'delivery_status' => $validated['delivery_action'] === 'ship' ? 'shipped' : 'delivered',
                    'courier_name' => $validated['courier_name'] ?? $lockedOrder->courier_name,
                    'tracking_number' => $validated['tracking_number'] ?? $lockedOrder->tracking_number,
                    'delivery_proof_path' => $proofPath ?? $lockedOrder->delivery_proof_path,
                    'status' => $validated['delivery_action'] === 'deliver' ? 'completed' : $lockedOrder->status,
                ]);

                return $previousProofPath;
            });
        } catch (Throwable $exception) {
            if ($proofPath) {
                Storage::disk('local')->delete($proofPath);
            }

            throw $exception;
        }

        if ($proofPath && $previousProofPath && $previousProofPath !== $proofPath) {
            Storage::disk('local')->delete($previousProofPath);
        }

        return redirect()->route('supplier.distribution')->with('success', 'Informasi distribusi berhasil diperbarui.');
    }

    public function downloadDeliveryProof(Request $request, Order $order)
    {
        $supplier = $this->supplier($request);
        abort_unless($order->supplier_id === $supplier->id && $order->delivery_proof_path, 404);
        abort_unless(Storage::disk('local')->exists($order->delivery_proof_path), 404);

        return Storage::disk('local')->download(
            $order->delivery_proof_path,
            'bukti-pengiriman-'.$order->id.'.'.pathinfo($order->delivery_proof_path, PATHINFO_EXTENSION)
        );
    }

    public function exportFinance(Request $request)
    {
        $supplier = $this->supplier($request);
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth())->startOfDay();
        $to = Carbon::parse($validated['to'] ?? now())->endOfDay();
        $orders = $supplier->supplierOrders()
            ->where('payment_status', 'paid')
            ->whereBetween('payment_verified_at', [$from, $to])
            ->with('buyer')
            ->orderBy('payment_verified_at')
            ->get();
        $expenses = SupplierExpense::where('supplier_id', $supplier->id)
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('expense_date')
            ->get();

        return response()->streamDownload(function () use ($orders, $expenses): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['Jenis', 'Tanggal', 'Referensi', 'Keterangan', 'Masuk', 'Keluar']);

            foreach ($orders as $order) {
                fputcsv($stream, [
                    'Penjualan',
                    $order->payment_verified_at?->format('Y-m-d H:i'),
                    'Pesanan #'.$order->id,
                    $order->buyer->name,
                    $order->total,
                    0,
                ]);
            }

            foreach ($expenses as $expense) {
                fputcsv($stream, [
                    'Pengeluaran',
                    $expense->expense_date->format('Y-m-d'),
                    'Biaya #'.$expense->id,
                    $expense->title,
                    0,
                    $expense->amount,
                ]);
            }

            fclose($stream);
        }, 'arus-kas-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function supplier(Request $request)
    {
        abort_unless($request->user()?->isSupplier(), 403);

        return $request->user();
    }
}
