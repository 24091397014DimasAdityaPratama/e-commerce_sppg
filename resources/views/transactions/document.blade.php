<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle }} #{{ $order->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 12px; line-height: 1.5; }
        .header { border-bottom: 3px solid #059669; padding-bottom: 16px; margin-bottom: 22px; }
        .brand-table { width: 100%; }
        .brand-table td { vertical-align: middle; }
        .brand-logo { max-width: 90px; max-height: 70px; }
        .brand { color: #047857; font-size: 20px; font-weight: bold; }
        .title { color: #0f172a; font-size: 18px; font-weight: bold; margin-top: 16px; }
        .muted { color: #64748b; }
        .details { width: 100%; margin: 18px 0; }
        .details td { width: 50%; vertical-align: top; padding: 5px 0; }
        table.items { border-collapse: collapse; width: 100%; margin-top: 20px; }
        .items th { background: #ecfdf5; color: #065f46; text-align: left; }
        .items th, .items td { border-bottom: 1px solid #e2e8f0; padding: 9px 7px; }
        .right { text-align: right !important; }
        .totals { width: 45%; margin: 18px 0 0 auto; border-collapse: collapse; }
        .totals td { padding: 5px; }
        .grand-total { border-top: 2px solid #0f172a; font-size: 14px; font-weight: bold; }
        .notice { margin-top: 28px; padding: 12px; background: #ecfdf5; color: #065f46; }
        .footer { margin-top: 45px; text-align: center; color: #64748b; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <table class="brand-table">
            <tr>
                <td style="width: 105px">
                    @if ($storeLogoDataUri)
                        <img src="{{ $storeLogoDataUri }}" alt="Logo toko" class="brand-logo">
                    @endif
                </td>
                <td>
                    <div class="brand">{{ $order->supplier->supplierProfile->business_name ?? $order->supplier->name }}</div>
                    @if ($order->supplier->supplierProfile?->address)
                        <div class="muted">{{ $order->supplier->supplierProfile->address }}</div>
                    @endif
                    @if ($order->supplier->supplierProfile?->city || $order->supplier->supplierProfile?->province)
                        <div class="muted">{{ collect([$order->supplier->supplierProfile->city, $order->supplier->supplierProfile->province])->filter()->join(', ') }}</div>
                    @endif
                </td>
            </tr>
        </table>
        <div class="title">{{ $documentTitle }}</div>
        <div>Nomor: {{ str_pad((string) $order->id, 8, '0', STR_PAD_LEFT) }}</div>
    </div>

    <table class="details">
        <tr>
            <td>
                <strong>Pembeli</strong><br>
                {{ $order->buyer->name }}<br>
                <span class="muted">{{ $order->buyer->email }}</span>
            </td>
            <td>
                <strong>Toko / Pemasok</strong><br>
                {{ $order->supplier->supplierProfile->business_name ?? $order->supplier->name }}<br>
                <span class="muted">{{ $order->supplier->name }}</span>
            </td>
        </tr>
        <tr>
            <td><strong>Tanggal pesanan:</strong> {{ $order->created_at->format('d-m-Y H:i') }}</td>
            <td><strong>Status pesanan:</strong> {{ ucfirst($order->status) }}</td>
        </tr>
        @if ($isReceipt)
            <tr>
                <td><strong>Metode pembayaran:</strong> {{ $order->payment_method === 'qris' ? 'QRIS' : 'Transfer Bank' }}</td>
                <td><strong>Dikonfirmasi:</strong> {{ $order->payment_verified_at->format('d-m-Y H:i') }}</td>
            </tr>
        @endif
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>Produk</th>
                <th class="right">Harga</th>
                <th class="right">Jumlah</th>
                <th class="right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($order->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td class="right">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                    <td class="right">{{ $item->quantity }}</td>
                    <td class="right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="right">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td></tr>
        <tr><td>Ongkos kirim</td><td class="right">Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</td></tr>
        <tr><td>Diskon</td><td class="right">Rp {{ number_format($order->discount, 0, ',', '.') }}</td></tr>
        <tr class="grand-total"><td>Total</td><td class="right">Rp {{ number_format($order->total, 0, ',', '.') }}</td></tr>
    </table>

    @if ($isReceipt)
        <div class="notice">Pembayaran telah diterima dan dikonfirmasi oleh pemasok.</div>
    @else
        <div class="notice">Nota pembelian ini adalah ringkasan pesanan, bukan bukti bahwa pembayaran telah diterima.</div>
    @endif

    <div class="footer">Dokumen diterbitkan oleh {{ $order->supplier->supplierProfile->business_name ?? $order->supplier->name }} pada {{ now()->format('d-m-Y H:i') }}.</div>
</body>
</html>
