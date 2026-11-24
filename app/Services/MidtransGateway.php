<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MidtransGateway
{
    public function isConfigured(): bool
    {
        return filled(config('services.midtrans.server_key'));
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function createQrisCharge(string $orderId, int $amount, string $customerName, string $customerEmail): array
    {
        $serverKey = $this->serverKey();
        $response = Http::withBasicAuth($serverKey, '')
            ->acceptJson()
            ->timeout(20)
            ->post(config('services.midtrans.base_url').'/v2/charge', [
                'payment_type' => 'qris',
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $amount,
                ],
                'qris' => [
                    'acquirer' => 'gopay',
                ],
                'customer_details' => [
                    'first_name' => $customerName,
                    'email' => $customerEmail,
                ],
            ]);

        $response->throw();
        $payload = $response->json();
        $qrActionUrl = collect($payload['actions'] ?? [])
            ->firstWhere('name', 'generate-qr-code')['url'] ?? null;

        if (! is_string($qrActionUrl) || ! $this->isTrustedGatewayUrl($qrActionUrl)) {
            throw new RuntimeException('Midtrans tidak mengembalikan QRIS yang valid.');
        }

        return [
            'transaction_id' => $payload['transaction_id'] ?? null,
            'qr_action_url' => $qrActionUrl,
        ];
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function fetchQrisImage(string $url): string
    {
        if (! $this->isTrustedGatewayUrl($url)) {
            throw new RuntimeException('URL QRIS tidak berasal dari endpoint Midtrans.');
        }

        $response = Http::withBasicAuth($this->serverKey(), '')
            ->timeout(20)
            ->get($url);

        $response->throw();

        if (! str_starts_with((string) $response->header('Content-Type'), 'image/')) {
            throw new RuntimeException('Midtrans tidak mengembalikan gambar QRIS.');
        }

        return $response->body();
    }

    /**
     * @throws ConnectionException
     * @throws RequestException
     */
    public function fetchTransactionStatus(string $orderId): array
    {
        $response = Http::withBasicAuth($this->serverKey(), '')
            ->acceptJson()
            ->timeout(20)
            ->get(config('services.midtrans.base_url').'/v2/'.rawurlencode($orderId).'/status');

        $response->throw();

        return $response->json();
    }

    private function serverKey(): string
    {
        $serverKey = config('services.midtrans.server_key');

        if (! is_string($serverKey) || $serverKey === '') {
            throw new RuntimeException('MIDTRANS_SERVER_KEY belum dikonfigurasi.');
        }

        return $serverKey;
    }

    private function isTrustedGatewayUrl(string $url): bool
    {
        $urlHost = parse_url($url, PHP_URL_HOST);
        $gatewayHost = parse_url((string) config('services.midtrans.base_url'), PHP_URL_HOST);

        return parse_url($url, PHP_URL_SCHEME) === 'https'
            && is_string($urlHost)
            && $urlHost === $gatewayHost;
    }
}
