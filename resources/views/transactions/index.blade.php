@extends('layouts.app')

@section('title', 'Transaksi - SPPG MART')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="mb-10">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">{{ $user->isBuyer() ? 'Pembelian' : 'Pesanan Masuk' }}</p>
        <h1 class="mt-3 text-4xl font-black text-slate-900">Transaksi</h1>
        <p class="mt-3 text-slate-600">{{ $user->isBuyer() ? 'Pantau pesanan yang Anda buat.' : 'Kelola pesanan yang masuk dari pembeli.' }}</p>
    </div>

    @if ($user->isSupplier())
        @include('supplier.partials.navigation', ['active' => 'orders'])
    @endif

    @if ($orders->isEmpty())
        <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center shadow-sm">
            <h2 class="text-xl font-bold text-slate-900">Belum ada transaksi</h2>
            <p class="mt-2 text-slate-600">{{ $user->isBuyer() ? 'Pesanan yang dibuat dari katalog akan muncul di sini.' : 'Pesanan dari pembeli akan muncul di sini.' }}</p>
            @if ($user->isBuyer())
                <a href="{{ route('catalog') }}" class="mt-6 inline-flex rounded-full bg-emerald-600 px-5 py-3 font-semibold text-white hover:bg-emerald-700">Lihat Katalog</a>
            @endif
        </div>
    @else
        <div class="space-y-4">
            @foreach ($orders as $order)
                <a href="{{ route('transactions.show', $order) }}" class="block rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 hover:shadow-md sm:p-6">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-lg font-bold text-slate-900">Pesanan #{{ $order->id }}</h2>
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">{{ ucfirst($order->status) }}</span>
                            </div>
                            <p class="mt-2 text-sm text-slate-600">
                                {{ $user->isBuyer() ? 'Pemasok: '.$order->supplier->name : 'Pembeli: '.$order->buyer->name }}
                                · {{ $order->items->sum('quantity') }} produk
                            </p>
                            <p class="mt-1 text-xs text-slate-500">{{ $order->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div class="text-left sm:text-right">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
                            <p class="mt-1 text-xl font-black text-emerald-700">Rp {{ number_format($order->total, 0, ',', '.') }}</p>
                            <p class="mt-1 text-xs text-slate-500">Pembayaran: {{ ucfirst($order->payment_status) }}</p>
                        </div>
                    </div>
                    <div class="mt-4 border-t border-slate-100 pt-4 text-sm text-slate-600">
                        {{ $order->items->take(3)->map(fn ($item) => $item->product->name.' × '.$item->quantity)->join(', ') }}
                        @if ($order->items->count() > 3)
                            dan {{ $order->items->count() - 3 }} produk lainnya
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <div class="mt-8">{{ $orders->links() }}</div>
    @endif
</div>
@endsection
