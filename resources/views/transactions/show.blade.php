@extends('layouts.app')

@section('title', 'Pesanan #'.$order->id.' - SPPG MART')

@section('content')
<div class="mx-auto max-w-5xl px-4 py-16 sm:px-6 lg:px-8">
    @php
        $supplierProfile = $order->supplier->supplierProfile;
        $bankAvailable = $supplierProfile?->bank_name && $supplierProfile?->bank_account_name && $supplierProfile?->bank_account_number;
    @endphp
    @if ($user->isSupplier())
        @include('supplier.partials.navigation', ['active' => 'orders'])
    @endif
    <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-emerald-700 hover:text-emerald-800">← Kembali ke transaksi</a>

    @if (session('success'))
        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="mt-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">Detail transaksi</p>
                <h1 class="mt-2 text-3xl font-black text-slate-900">Pesanan #{{ $order->id }}</h1>
                <p class="mt-2 text-sm text-slate-500">Dibuat {{ $order->created_at->format('d M Y, H:i') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-sm font-bold text-emerald-800">Status: {{ ucfirst($order->status) }}</span>
                <span class="rounded-full bg-amber-100 px-3 py-1.5 text-sm font-bold text-amber-800">Pembayaran: {{ ucfirst($order->payment_status) }}</span>
            </div>
        </div>

        <div class="mt-8 grid gap-4 rounded-2xl bg-slate-50 p-5 sm:grid-cols-2">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Pembeli</p>
                <p class="mt-1 font-semibold text-slate-900">{{ $order->buyer->name }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">Pemasok</p>
                <p class="mt-1 font-semibold text-slate-900">{{ $order->supplier->name }}</p>
            </div>
        </div>

        <form method="POST" action="{{ route('chat.orders.start', $order) }}" class="mt-5">
            @csrf
            <button type="submit" class="rounded-full border border-emerald-200 bg-white px-5 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">
                Chat dengan {{ $user->isBuyer() ? 'supplier' : 'buyer' }}
            </button>
        </form>

        @if ($user->isBuyer())
            @php($deliveryState = $order->delivery_status ?? 'not_started')
            <section class="mt-8 rounded-2xl border border-sky-200 bg-sky-50/60 p-5">
                <h2 class="text-lg font-black text-slate-900">Pelacakan distribusi</h2>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    @foreach ([
                        ['label' => 'Pesanan diproses', 'complete' => in_array($order->status, ['processing', 'completed'], true), 'time' => $order->payment_verified_at],
                        ['label' => 'Dalam pengiriman', 'complete' => in_array($deliveryState, ['shipped', 'delivered'], true), 'time' => $order->shipped_at],
                        ['label' => 'Barang diterima', 'complete' => $deliveryState === 'delivered', 'time' => $order->delivered_at],
                    ] as $step)
                        <div class="rounded-xl border bg-white p-3 {{ $step['complete'] ? 'border-emerald-200' : 'border-slate-200' }}">
                            <p class="text-sm font-bold {{ $step['complete'] ? 'text-emerald-800' : 'text-slate-500' }}">{{ $step['complete'] ? '✓ ' : '○ ' }}{{ $step['label'] }}</p>
                            @if ($step['time'])
                                <p class="mt-1 text-xs text-slate-500">{{ $step['time']->format('d M Y, H:i') }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if ($order->courier_name || $order->tracking_number)
                    <div class="mt-4 rounded-xl bg-white p-4 text-sm">
                        @if ($order->courier_name)
                            <p><span class="text-slate-500">Kurir:</span> <strong>{{ $order->courier_name }}</strong></p>
                        @endif
                        @if ($order->tracking_number)
                            <p class="mt-1"><span class="text-slate-500">Nomor resi:</span> <strong class="select-all">{{ $order->tracking_number }}</strong></p>
                        @endif
                    </div>
                @elseif ($deliveryState === 'not_started')
                    <p class="mt-3 text-sm text-slate-600">Informasi kurir dan nomor resi akan muncul setelah pemasok mengirim pesanan.</p>
                @endif
                @if ($deliveryState === 'delivered' && $order->delivery_proof_path)
                    <a href="{{ route('transactions.delivery-proof', $order) }}" class="mt-3 inline-flex text-sm font-semibold text-sky-800 underline">Unduh bukti serah terima</a>
                @endif
            </section>
        @endif

        <div class="mt-8 overflow-x-auto">
            <table class="w-full min-w-[560px] text-left">
                <thead>
                    <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500">
                        <th class="pb-3 pr-4">Produk</th>
                        <th class="pb-3 px-4 text-right">Harga</th>
                        <th class="pb-3 px-4 text-right">Jumlah</th>
                        <th class="pb-3 pl-4 text-right">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr class="border-b border-slate-100">
                            <td class="py-4 pr-4 font-semibold text-slate-900">
                                <a href="{{ route('products.show', $item->product) }}" class="hover:text-emerald-700 hover:underline">{{ $item->product->name }}</a>
                                @if ($user->isBuyer() && $order->status === 'completed' && $order->delivery_status === 'delivered' && ! in_array($item->product_id, $reviewedProductIds, true))
                                    <form method="POST" action="{{ route('products.reviews.store', $item->product) }}" class="mt-2 space-y-2">
                                        @csrf
                                        <label for="rating-{{ $item->product_id }}" class="sr-only">Rating untuk {{ $item->product->name }}</label>
                                        <select id="rating-{{ $item->product_id }}" name="rating" required class="rounded-lg border-slate-300 py-1 text-xs">
                                            <option value="">Pilih bintang</option>
                                            @for ($rating = 5; $rating >= 1; $rating--)
                                                <option value="{{ $rating }}">{{ $rating }} bintang</option>
                                            @endfor
                                        </select>
                                        <input type="text" name="comment" maxlength="2000" placeholder="Ulasan singkat (opsional)" class="w-full rounded-lg border-slate-300 py-1 text-xs">
                                        <button class="rounded-full bg-amber-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-amber-600">Kirim ulasan</button>
                                    </form>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-right text-slate-600">Rp {{ number_format($item->unit_price, 0, ',', '.') }}</td>
                            <td class="px-4 py-4 text-right text-slate-600">{{ $item->quantity }}</td>
                            <td class="py-4 pl-4 text-right font-semibold text-slate-900">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="ml-auto mt-6 max-w-sm space-y-2 border-t border-slate-200 pt-5 text-sm">
            <div class="flex justify-between text-slate-600"><span>Subtotal</span><span>Rp {{ number_format($order->subtotal, 0, ',', '.') }}</span></div>
            <div class="flex justify-between text-slate-600"><span>Ongkir</span><span>Rp {{ number_format($order->shipping_fee, 0, ',', '.') }}</span></div>
            <div class="flex justify-between border-t border-slate-200 pt-3 text-lg font-black text-slate-900"><span>Total</span><span>Rp {{ number_format($order->total, 0, ',', '.') }}</span></div>
        </div>

        <div class="mt-8 flex flex-wrap gap-3 border-t border-slate-200 pt-6">
            <a href="{{ route('transactions.invoice', $order) }}" class="rounded-full border border-emerald-200 px-5 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Unduh Nota Pembelian</a>
            @if ($order->payment_status === 'paid')
                <a href="{{ route('transactions.receipt', $order) }}" class="rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Unduh Kwitansi Pembayaran</a>
            @endif
        </div>

        @if ($order->payment_proof_path)
            <div class="mt-6 rounded-2xl border border-slate-200 p-5">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-slate-900">Bukti pembayaran</h2>
                        <p class="mt-1 text-sm text-slate-600">Metode: {{ $order->payment_method === 'qris' ? 'QRIS' : 'Transfer Bank' }} · dikirim {{ $order->payment_submitted_at?->format('d M Y, H:i') }}</p>
                    </div>
                    <a href="{{ route('transactions.payment-proof', $order) }}" class="text-sm font-semibold text-emerald-700 hover:underline">Unduh bukti</a>
                </div>
                @if ($order->payment_rejection_reason)
                    <p class="mt-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">Alasan penolakan: {{ $order->payment_rejection_reason }}</p>
                @endif
            </div>
        @endif

        @if ($user->isBuyer() && in_array($order->payment_status, ['pending', 'rejected', 'expired'], true) && $order->status !== 'cancelled')
            <div class="mt-8 rounded-3xl border border-emerald-200 bg-emerald-50/50 p-5 sm:p-6">
                <h2 class="text-xl font-black text-slate-900">Bayar Pesanan</h2>
                <p class="mt-2 text-sm text-slate-600">Transfer bank diverifikasi pemasok secara manual. QRIS dinamis akan terkonfirmasi otomatis setelah pembayaran diterima Midtrans.</p>

                @error('payment_method')<p class="mt-4 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>@enderror

                @if ($bankAvailable && ! ($order->payment_method === 'qris' && $order->payment_status === 'pending'))
                    <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-4">
                        <h3 class="font-bold text-slate-900">Transfer Bank</h3>
                        <p class="mt-2 text-sm text-slate-600">{{ $supplierProfile->bank_name }} · <strong>{{ $supplierProfile->bank_account_number }}</strong> · a.n. {{ $supplierProfile->bank_account_name }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800">Transfer tepat Rp {{ number_format($order->total, 0, ',', '.') }}</p>
                        <form method="POST" action="{{ route('transactions.payment.submit', $order) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                            @csrf
                            <input type="hidden" name="payment_method" value="bank_transfer">
                            <div>
                                <label for="payment_proof" class="mb-2 block text-sm font-semibold text-slate-700">Upload bukti transfer</label>
                                <input type="file" name="payment_proof" id="payment_proof" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf" required class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:font-semibold file:text-emerald-700">
                                <p class="mt-2 text-xs text-slate-500">JPG, PNG, WEBP, atau PDF; maksimal 5 MB.</p>
                                @error('payment_proof')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <button class="rounded-full border border-emerald-200 px-5 py-2.5 text-sm font-semibold text-emerald-700 hover:bg-emerald-50">Kirim Bukti Transfer</button>
                        </form>
                    </div>
                @endif

                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                    <h3 class="font-bold text-slate-900">QRIS Dinamis</h3>
                    @if ($order->payment_method === 'qris' && $order->payment_status === 'pending' && $order->qris_action_url)
                        <p class="mt-2 text-sm text-slate-600">Scan QRIS berikut dan bayar nominal persis yang tertera:</p>
                        <p class="mt-2 text-2xl font-black text-emerald-700">Rp {{ number_format($order->total, 0, ',', '.') }}</p>
                        <img src="{{ route('transactions.qris.image', $order) }}" alt="QRIS dinamis untuk pesanan {{ $order->id }}" class="mt-4 h-64 w-64 rounded-xl border border-slate-200 bg-white object-contain p-2">
                        <p class="mt-3 text-sm font-semibold text-amber-700">Menunggu pembayaran dari Midtrans...</p>
                        <script>window.setTimeout(() => window.location.reload(), 15000);</script>
                    @elseif ($midtransEnabled)
                        <p class="mt-2 text-sm text-slate-600">QR dibuat khusus untuk pesanan ini, dengan nominal Rp {{ number_format($order->total, 0, ',', '.') }}.</p>
                        <form method="POST" action="{{ route('transactions.qris.create', $order) }}" class="mt-4">
                            @csrf
                            <button class="rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Buat QRIS & Bayar</button>
                        </form>
                    @else
                        <p class="mt-2 text-sm text-amber-800">QRIS dinamis belum aktif. Admin perlu mengisi MIDTRANS_SERVER_KEY di .env dan mengatur URL notifikasi Midtrans.</p>
                    @endif
                </div>
            </div>
        @endif

        @if ($user->isSupplier() && $order->payment_status === 'submitted')
            <div class="mt-8 rounded-3xl border border-amber-200 bg-amber-50/50 p-5 sm:p-6">
                <h2 class="text-xl font-black text-slate-900">Verifikasi Pembayaran</h2>
                <p class="mt-2 text-sm text-slate-600">Periksa bukti pembayaran sebelum mengonfirmasi. Konfirmasi ini dilakukan manual.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <form method="POST" action="{{ route('transactions.payment.verify', $order) }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="payment_status" value="paid">
                        <button class="rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Konfirmasi Pembayaran</button>
                    </form>
                    <form method="POST" action="{{ route('transactions.payment.verify', $order) }}" class="flex flex-wrap gap-2">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="payment_status" value="rejected">
                        <input type="text" name="payment_rejection_reason" required maxlength="500" placeholder="Alasan menolak bukti" class="rounded-full border border-slate-300 px-4 py-2 text-sm">
                        <button class="rounded-full border border-red-200 px-5 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Tolak Bukti</button>
                    </form>
                </div>
            </div>
        @endif

        @if ($user->isSupplier() && in_array($order->status, ['pending', 'processing'], true))
            <div class="mt-8 border-t border-slate-200 pt-6">
                <h2 class="font-bold text-slate-900">Perbarui status pesanan</h2>
                <div class="mt-3 flex flex-wrap gap-3">
                    @if ($order->status === 'pending' && $order->payment_status === 'paid')
                        <form method="POST" action="{{ route('transactions.status.update', $order) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="processing">
                            <button class="rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">Terima Pesanan</button>
                        </form>
                    @endif

                    @if ($order->status === 'processing')
                        <form method="POST" action="{{ route('transactions.status.update', $order) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="completed">
                            <button class="rounded-full bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">Tandai Selesai</button>
                        </form>
                    @endif

                    @if ($order->payment_status !== 'paid')
                        <form method="POST" action="{{ route('transactions.status.update', $order) }}" onsubmit="return confirm('Batalkan pesanan ini? Stok produk akan dikembalikan.')">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="cancelled">
                            <button class="rounded-full border border-red-200 px-5 py-2.5 text-sm font-semibold text-red-700 hover:bg-red-50">Batalkan</button>
                        </form>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
