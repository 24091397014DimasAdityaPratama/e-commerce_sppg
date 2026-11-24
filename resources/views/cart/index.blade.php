@extends('layouts.app')

@section('title', 'Keranjang Belanja - SPPG MART')

@section('content')
@php
    $items = $cart?->items ?? collect();
    $groups = $items->groupBy(fn ($item) => $item->product->supplier_id);
    $total = $items->sum(fn ($item) => $item->product->price * $item->quantity);
@endphp
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-orange-600">Belanja Anda</p>
            <h1 class="mt-1 text-3xl font-black text-slate-900">Keranjang Saya</h1>
        </div>
        <a href="{{ route('catalog') }}" class="text-sm font-semibold text-orange-700 hover:text-orange-800">← Lanjut belanja</a>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if ($items->isEmpty())
        <section class="rounded-2xl border border-slate-200 bg-white px-6 py-16 text-center shadow-sm">
            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-orange-50 text-3xl">🛒</span>
            <h2 class="mt-4 text-xl font-black text-slate-900">Keranjang masih kosong</h2>
            <p class="mt-2 text-sm text-slate-500">Produk yang kamu pilih akan muncul di sini.</p>
            <a href="{{ route('catalog') }}" class="mt-5 inline-flex rounded-lg bg-orange-600 px-5 py-3 text-sm font-bold text-white hover:bg-orange-700">Mulai belanja</a>
        </section>
    @else
        <div class="grid gap-5 lg:grid-cols-[1fr_320px]">
            <div class="space-y-4">
                <div class="hidden rounded-xl bg-white px-5 py-4 text-xs font-semibold uppercase tracking-wide text-slate-500 shadow-sm md:grid md:grid-cols-[minmax(0,1fr)_110px_150px_120px] md:gap-4">
                    <span>Produk</span><span class="text-right">Harga satuan</span><span class="text-center">Kuantitas</span><span class="text-right">Subtotal</span>
                </div>
                @foreach ($groups as $supplierItems)
                    @php($supplier = $supplierItems->first()->product->supplier)
                    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-4 sm:px-5">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-orange-100 text-sm">🏪</span>
                            <div>
                                <p class="text-xs text-slate-500">Pemasok</p>
                                <p class="text-sm font-bold text-slate-900">{{ $supplier->supplierProfile->business_name ?? $supplier->name }}</p>
                            </div>
                            @if ($supplier->supplierProfile?->city)
                                <span class="ml-auto text-xs text-slate-500">{{ $supplier->supplierProfile->city }}</span>
                            @endif
                        </div>
                        <div class="divide-y divide-slate-100">
                            @foreach ($supplierItems as $item)
                                <div class="grid gap-4 p-4 md:grid-cols-[minmax(0,1fr)_110px_150px_120px] md:items-center md:px-5">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <a href="{{ route('products.show', $item->product) }}" class="shrink-0">
                                            <img src="{{ $item->product->image_url ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=200&q=80' }}" alt="{{ $item->product->name }}" class="h-20 w-20 rounded-lg bg-slate-100 object-cover">
                                        </a>
                                        <div class="min-w-0">
                                            <a href="{{ route('products.show', $item->product) }}" class="line-clamp-2 text-sm font-semibold text-slate-800 hover:text-orange-700">{{ $item->product->name }}</a>
                                            <p class="mt-1 text-xs text-slate-500">{{ $item->product->category->name ?? 'Produk' }} · {{ (float) $item->product->unit_quantity }} {{ $item->product->unit }}</p>
                                            @if (! $item->product->is_active || $item->quantity > $item->product->stock)
                                                <p class="mt-1 text-xs font-semibold text-red-600">Periksa stok/ketersediaan produk</p>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-700 md:text-right">Rp {{ number_format($item->product->price, 0, ',', '.') }}</p>
                                    <form method="POST" action="{{ route('cart.items.update', $item) }}" class="flex items-center md:justify-center">
                                        @csrf
                                        @method('PATCH')
                                        <label class="sr-only" for="quantity-{{ $item->id }}">Kuantitas {{ $item->product->name }}</label>
                                        <input id="quantity-{{ $item->id }}" type="number" name="quantity" min="1" max="{{ max(1, $item->product->stock) }}" value="{{ $item->quantity }}" class="w-20 rounded-l-lg border border-slate-300 px-2 py-2 text-center text-sm focus:border-orange-500 focus:outline-none">
                                        <button type="submit" class="rounded-r-lg border border-l-0 border-slate-300 px-3 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Ubah</button>
                                    </form>
                                    <div class="flex items-center justify-between gap-3 md:block md:text-right">
                                        <p class="font-bold text-orange-700">Rp {{ number_format($item->product->price * $item->quantity, 0, ',', '.') }}</p>
                                        <form method="POST" action="{{ route('cart.items.destroy', $item) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-slate-500 underline hover:text-red-600">Hapus</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="border-t border-slate-100 bg-slate-50 px-4 py-3 text-right text-sm text-slate-600 sm:px-5">
                            Total toko <span class="ml-2 font-bold text-slate-900">Rp {{ number_format($supplierItems->sum(fn ($item) => $item->product->price * $item->quantity), 0, ',', '.') }}</span>
                        </div>
                    </section>
                @endforeach
            </div>

            <aside class="h-fit rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-28">
                <h2 class="text-lg font-black text-slate-900">Ringkasan belanja</h2>
                <div class="mt-4 space-y-3 border-b border-slate-100 pb-4 text-sm">
                    <div class="flex justify-between text-slate-600"><span>{{ $items->sum('quantity') }} produk</span><span>Rp {{ number_format($total, 0, ',', '.') }}</span></div>
                    <div class="flex justify-between text-slate-600"><span>Ongkos kirim</span><span>Dihitung pemasok</span></div>
                </div>
                <div class="flex justify-between py-4 font-bold text-slate-900"><span>Total sementara</span><span class="text-orange-700">Rp {{ number_format($total, 0, ',', '.') }}</span></div>
                <form method="POST" action="{{ route('cart.checkout') }}">
                    @csrf
                    <button type="submit" class="w-full rounded-lg bg-orange-600 px-4 py-3 text-sm font-bold text-white hover:bg-orange-700">Checkout</button>
                </form>
                <p class="mt-3 text-center text-xs leading-relaxed text-slate-500">Produk dari pemasok berbeda akan dibuat menjadi pesanan terpisah.</p>
            </aside>
        </div>
    @endif
</div>
@endsection
