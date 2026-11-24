<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MidtransNotificationController extends Controller
{
    public function handle(Request $request, MidtransGateway $midtrans)
    {
        $validated = $request->validate([
            'order_id' => ['required', 'string', 'max:255'],
            'status_code' => ['required', 'string'],
            'gross_amount' => ['required', 'numeric', 'min:0'],
            'signature_key' => ['required', 'string'],
        ]);

        $serverKey = config('services.midtrans.server_key');

        if (! is_string($serverKey) || $serverKey === '') {
            Log::error('Midtrans notification received without configured server key.');

            return response()->json(['message' => 'Payment gateway is not configured.'], 503);
        }

        $expectedSignature = hash(
            'sha512',
            $validated['order_id'].$validated['status_code'].$validated['gross_amount'].$serverKey
        );

        if (! hash_equals($expectedSignature, $validated['signature_key'])) {
            Log::warning('Invalid Midtrans notification signature received.', [
                'gateway_order_id' => $validated['order_id'],
            ]);

            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $order = Order::where('gateway_order_id', $validated['order_id'])->first();

        if (! $order || $order->payment_method !== 'qris') {
            return response()->json(['message' => 'Order not found.'], 404);
        }

        try {
            $status = $midtrans->fetchTransactionStatus($order->gateway_order_id);
        } catch (RuntimeException $exception) {
            Log::error('Unable to verify Midtrans transaction notification.', [
                'order_id' => $order->id,
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Unable to verify transaction.'], 503);
        }

        $expectedAmount = number_format((float) $order->total, 2, '.', '');
        $reportedAmount = number_format((float) ($status['gross_amount'] ?? -1), 2, '.', '');

        if (($status['order_id'] ?? null) !== $order->gateway_order_id
            || $reportedAmount !== $expectedAmount
            || ($status['payment_type'] ?? null) !== 'qris') {
            Log::warning('Midtrans status does not match local order.', [
                'order_id' => $order->id,
                'gateway_order_id' => $validated['order_id'],
            ]);

            return response()->json(['message' => 'Transaction details mismatch.'], 422);
        }

        DB::transaction(function () use ($order, $status): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->payment_status === 'paid' || $lockedOrder->status === 'cancelled') {
                return;
            }

            $transactionStatus = $status['transaction_status'] ?? null;
            $paymentStatus = match ($transactionStatus) {
                'settlement' => 'paid',
                'expire' => 'expired',
                'cancel', 'deny' => 'rejected',
                default => null,
            };

            if ($paymentStatus === null) {
                return;
            }

            $lockedOrder->update([
                'payment_status' => $paymentStatus,
                'payment_verified_at' => $paymentStatus === 'paid' ? now() : null,
                'payment_rejection_reason' => $paymentStatus === 'rejected'
                    ? 'Transaksi QRIS ditolak atau dibatalkan oleh penyedia pembayaran.'
                    : null,
            ]);
        });

        return response()->json(['message' => 'Notification processed.']);
    }
}
