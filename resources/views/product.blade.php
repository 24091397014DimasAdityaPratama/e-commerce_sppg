@extends('layouts.app')

@section('title', $product->name . ' - SPPG MART')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr]">
        <div>
            <img src="{{ $product->image_url ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e' }}" alt="{{ $product->name }}" class="h-[420px] w-full rounded-3xl object-cover shadow-xl">
        </div>
        <div class="rounded-3xl border border-slate-200 bg-white p-7 shadow-sm">
            <div class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">{{ $product->category->name ?? 'Produk' }}</div>
            <h1 class="mt-4 text-3xl font-black text-slate-900">{{ $product->name }}</h1>
            <div class="mt-4 text-3xl font-black text-emerald-700">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
            <p class="mt-3 text-sm text-slate-500">Per {{ (float) $product->unit_quantity }} {{ $product->unit }}</p>
            <p class="mt-5 text-slate-600">{{ $product->description }}</p>

            <div class="mt-6 space-y-3 rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">
                <div class="flex justify-between"><span>Stok tersedia</span><span class="font-semibold">{{ $product->stock }} produk</span></div>
                <div class="flex justify-between"><span>Pemasok</span><span class="font-semibold">{{ $product->supplier->name }}</span></div>
                <div class="flex justify-between"><span>Lokasi</span><span class="font-semibold">{{ $product->supplier->supplierProfile->city ?? 'Indonesia' }}</span></div>
            </div>

            @auth
                @if (auth()->user()->isBuyer())
                    <form method="POST" action="{{ route('chat.suppliers.start', $product->supplier) }}" class="mt-6">
                        @csrf
                        <button type="submit" class="w-full rounded-full border border-emerald-200 px-5 py-3 text-center font-semibold text-emerald-700 hover:bg-emerald-50">Chat dengan supplier</button>
                    </form>
                    <form method="POST" action="{{ route('cart.items.store', $product) }}" class="mt-8 space-y-3">
                        @csrf
                        <label for="quantity" class="block text-sm font-semibold text-slate-700">Jumlah produk</label>
                        <div class="flex gap-3">
                            <input type="number" id="quantity" name="quantity" min="1" max="{{ $product->stock }}" value="{{ old('quantity', 1) }}" required class="w-28 rounded-full border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                            <button type="submit" @disabled($product->stock < 1) class="flex-1 rounded-full bg-emerald-600 px-5 py-3 font-semibold text-white hover:bg-emerald-500 disabled:cursor-not-allowed disabled:bg-slate-400">
                                {{ $product->stock > 0 ? 'Tambah ke Keranjang' : 'Stok Habis' }}
                            </button>
                        </div>
                        @error('quantity')
                            <p class="text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </form>
                    <a href="{{ route('cart.index') }}" class="mt-3 block rounded-full border border-emerald-200 px-5 py-3 text-center font-semibold text-emerald-700 hover:bg-emerald-50">Lihat keranjang</a>
                @endif
            @else
                <a href="{{ route('login') }}" class="mt-8 block rounded-full bg-emerald-600 px-5 py-3 text-center font-semibold text-white hover:bg-emerald-500">Masuk untuk memesan</a>
            @endauth
            <a href="{{ route('home') }}" class="mt-3 block rounded-full border border-slate-200 px-5 py-3 text-center font-semibold text-slate-700 hover:bg-slate-50">Kembali</a>
        </div>
    </div>
</div>
@endsection
