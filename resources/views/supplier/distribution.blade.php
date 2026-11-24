@extends('layouts.app')

@section('title', 'Distribusi Pesanan - SPPG MART')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
    <div class="mb-7">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">Pusat Penjual</p>
        <h1 class="mt-2 text-3xl font-black text-slate-900 sm:text-4xl">Pengiriman & Distribusi</h1>
        <p class="mt-2 text-slate-600">Catat kurir dan nomor resi, lalu tandai pesanan selesai setelah barang diterima.</p>
    </div>
    @include('supplier.partials.navigation', ['active' => 'distribution'])

    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="space-y-5">
        @forelse ($orders as $order)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('transactions.show', $order) }}" class="text-lg font-black text-slate-900 hover:text-emerald-700">Pesanan #{{ $order->id }}</a>
                            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">{{ ucfirst($order->delivery_status ?? 'not_started') }}</span>
                            <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-800">Pembayaran {{ ucfirst($order->payment_status) }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-600">Pembeli: <strong>{{ $order->buyer->name }}</strong> · {{ $order->created_at->format('d M Y, H:i') }}</p>
                        <p class="mt-1 text-sm text-slate-600">{{ $order->items->map(fn ($item) => $item->product->name.' × '.$item->quantity)->join(', ') }}</p>
                        @if ($order->shippingAddress)
                            <p class="mt-2 text-sm text-slate-600">Tujuan: {{ $order->shippingAddress->full_name }}, {{ $order->shippingAddress->street }}, {{ $order->shippingAddress->district }}, {{ $order->shippingAddress->city }}, {{ $order->shippingAddress->province }} {{ $order->shippingAddress->postal_code }}</p>
                            <p class="text-sm text-slate-600">Kontak: {{ $order->shippingAddress->phone }}</p>
                        @else
                            <p class="mt-2 text-sm text-amber-700">Alamat tujuan belum diisi pada pesanan ini.</p>
                        @endif
                        <p class="mt-2 text-lg font-black text-emerald-700">Rp {{ number_format($order->total, 0, ',', '.') }}</p>
                        @if ($order->courier_name || $order->tracking_number)
                            <p class="mt-2 text-sm text-slate-600">{{ $order->courier_name }} {{ $order->tracking_number ? '· Resi: '.$order->tracking_number : '' }}</p>
                        @endif
                        @if ($order->delivery_proof_path)
                            <a href="{{ route('supplier.distribution.proof', $order) }}" class="mt-2 inline-block text-sm font-semibold text-emerald-700 underline">Unduh bukti pengiriman</a>
                        @endif
                    </div>

                    @if ($order->payment_status === 'paid' && $order->status === 'processing')
                        <div class="w-full rounded-2xl bg-slate-50 p-4 lg:max-w-md">
                            @if (($order->delivery_status ?? 'not_started') !== 'shipped')
                                <h2 class="font-bold text-slate-900">Catat pengiriman</h2>
                                <form method="POST" action="{{ route('supplier.distribution.update', $order) }}" class="mt-3 grid gap-3 sm:grid-cols-2" enctype="multipart/form-data">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="delivery_action" value="ship">
                                    <input type="text" name="courier_name" maxlength="100" required placeholder="Kurir / pengirim" value="{{ old('courier_name', $order->courier_name) }}" class="min-w-0 rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                    <input type="text" name="tracking_number" maxlength="100" placeholder="Nomor resi (opsional)" value="{{ old('tracking_number', $order->tracking_number) }}" class="min-w-0 rounded-xl border border-slate-300 px-3 py-2 text-sm">
                                    <button class="rounded-full bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white sm:col-span-2">Tandai Sedang Dikirim</button>
                                </form>
                            @else
                                <h2 class="font-bold text-slate-900">Konfirmasi barang diterima</h2>
                                <form method="POST" action="{{ route('supplier.distribution.update', $order) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="delivery_action" value="deliver">
                                    <div>
                                        <label for="delivery_proof_{{ $order->id }}" class="mb-1 block text-xs font-semibold text-slate-600">Bukti serah terima (wajib)</label>
                                        <input type="file" id="delivery_proof_{{ $order->id }}" name="delivery_proof" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs">
                                    </div>
                                    <button class="rounded-full bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white">Tandai Diterima & Selesai</button>
                                </form>
                            @endif
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center">
                <h2 class="text-xl font-bold text-slate-900">Belum ada pesanan untuk distribusi</h2>
                <p class="mt-2 text-sm text-slate-600">Pesanan yang dibayar dan sedang disiapkan akan muncul di sini.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</div>
@endsection
