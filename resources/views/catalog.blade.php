@extends('layouts.app')

@section('title', 'Katalog Produk - SPPG MART')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="rounded-2xl bg-gradient-to-r from-[#105e7d] to-[#258ebd] p-6 text-white sm:p-8">
        <p class="text-sm font-bold uppercase tracking-wider text-orange-100">Katalog SPPG MART</p>
        <h1 class="mt-2 text-3xl font-black sm:text-4xl">Belanja kebutuhan pangan</h1>
        <p class="mt-2 max-w-2xl text-sm text-orange-50 sm:text-base">Pilih produk segar dari pemasok dan tambahkan ke keranjang untuk mulai berbelanja.</p>
    </div>

    <div class="mt-7 flex gap-2 overflow-x-auto pb-2">
        <a href="{{ route('catalog', request()->only('search')) }}" class="shrink-0 rounded-full px-4 py-2 text-sm font-semibold {{ request('category') ? 'border border-slate-200 bg-white text-slate-600' : 'bg-orange-600 text-white' }}">Semua produk</a>
        @foreach ($categories as $category)
            <a href="{{ route('catalog', array_merge(request()->only('search'), ['category' => $category->id])) }}" class="shrink-0 rounded-full border px-4 py-2 text-sm font-semibold {{ request('category') == $category->id ? 'border-orange-600 bg-orange-50 text-orange-700' : 'border-slate-200 bg-white text-slate-600 hover:border-orange-300' }}">{{ $category->name }}</a>
        @endforeach
    </div>

    <div class="mt-7 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-orange-600">Pilihan untuk Anda</p>
            <h2 class="mt-1 text-2xl font-black text-slate-900">{{ request('search') ? 'Hasil pencarian' : 'Semua produk' }}</h2>
        </div>
        <p class="text-sm text-slate-500">{{ $products->total() }} produk</p>
    </div>

    <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4 xl:grid-cols-5">
        @forelse ($products as $product)
            <article class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-orange-200 hover:shadow-lg">
                <a href="{{ route('products.show', $product) }}" class="block">
                    <img src="{{ $product->image_url ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=700&q=80' }}" alt="{{ $product->name }}" class="aspect-square w-full bg-slate-100 object-cover">
                </a>
                <div class="p-3 sm:p-4">
                    <p class="text-xs font-medium text-slate-500">{{ $product->category->name ?? 'Produk' }}</p>
                    <a href="{{ route('products.show', $product) }}" class="mt-1 line-clamp-2 min-h-10 text-sm font-semibold text-slate-800 hover:text-orange-700">{{ $product->name }}</a>
                    <p class="mt-2 text-base font-black text-orange-700">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                    <p class="text-xs text-slate-500">per {{ (float) $product->unit_quantity }} {{ $product->unit }} · stok {{ $product->stock }}</p>
                    <p class="mt-2 truncate text-xs text-slate-500">{{ $product->supplier->supplierProfile->city ?? 'Indonesia' }}</p>
                    @auth
                        @if (auth()->user()->isBuyer())
                            <form method="POST" action="{{ route('cart.items.store', $product) }}" class="mt-3">
                                @csrf
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" @disabled($product->stock < 1) class="w-full rounded-lg bg-orange-600 px-3 py-2 text-xs font-bold text-white hover:bg-orange-700 disabled:cursor-not-allowed disabled:bg-slate-300">+ Keranjang</button>
                            </form>
                        @else
                            <a href="{{ route('products.show', $product) }}" class="mt-3 block rounded-lg border border-orange-200 px-3 py-2 text-center text-xs font-bold text-orange-700 hover:bg-orange-50">Lihat detail</a>
                        @endif
                    @else
                        <a href="{{ route('login') }}" class="mt-3 block rounded-lg bg-orange-600 px-3 py-2 text-center text-xs font-bold text-white hover:bg-orange-700">Masuk untuk beli</a>
                    @endauth
                </div>
            </article>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                <p class="text-lg font-bold text-slate-800">Produk tidak ditemukan</p>
                <p class="mt-1 text-sm text-slate-500">Coba kata kunci atau kategori lain.</p>
                <a href="{{ route('catalog') }}" class="mt-4 inline-block font-semibold text-orange-700">Lihat semua produk</a>
            </div>
        @endforelse
    </div>
    <div class="mt-8">{{ $products->links() }}</div>
</div>
@endsection
