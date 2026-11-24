@extends('layouts.app')

@section('title', 'Belanja Kebutuhan SPPG - SPPG MART')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
    <section class="overflow-hidden rounded-2xl bg-gradient-to-r from-[#105e7d] via-[#167ba5] to-[#258ebd] text-white">
        <div class="grid items-center gap-6 px-6 py-8 sm:px-10 sm:py-10 lg:grid-cols-[1.4fr_0.6fr]">
            <div>
                <p class="text-sm font-bold uppercase tracking-[0.18em] text-sky-100">Belanja mudah untuk operasional SPPG</p>
                <h1 class="mt-3 max-w-2xl text-3xl font-black leading-tight sm:text-5xl">Bahan pangan pilihan, siap masuk keranjang.</h1>
                <p class="mt-3 max-w-xl text-sm text-emerald-50 sm:text-base">Cari produk, bandingkan pemasok, dan pantau pesanan dalam satu tempat.</p>
                <a href="{{ route('catalog') }}" class="mt-6 inline-flex rounded-lg bg-orange-500 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-orange-600">Mulai belanja <span class="ml-2">→</span></a>
            </div>
            <div class="hidden rounded-2xl border border-white/20 bg-white/10 p-6 lg:block">
                <p class="text-sm font-semibold text-emerald-100">Mitra pengadaan pangan</p>
                <p class="mt-2 text-3xl font-black">{{ $categories->count() }} kategori</p>
                <p class="mt-1 text-sm text-emerald-100">Produk segar dari pemasok terdaftar</p>
                <a href="{{ route('transactions.index') }}" class="mt-5 inline-block text-sm font-bold text-white underline decoration-sky-200 underline-offset-4">Lihat pesanan saya</a>
            </div>
        </div>
    </section>

    <section class="mt-8">
        <div class="mb-4 flex items-end justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-orange-600">Jelajahi kebutuhan</p>
                <h2 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">Kategori produk</h2>
            </div>
            <a href="{{ route('catalog') }}" class="text-sm font-semibold text-orange-700 hover:text-orange-800">Semua kategori →</a>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @php($categoryIcons = ['🥬', '🌾', '🍅', '🥚', '🧂', '🥛'])
            @foreach ($categories as $index => $category)
                <a href="{{ route('catalog', ['category' => $category->id]) }}" class="rounded-xl border border-slate-200 bg-white p-4 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-orange-300 hover:shadow-md">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-orange-50 text-2xl">{{ $categoryIcons[$index % count($categoryIcons)] }}</span>
                    <span class="mt-3 block text-sm font-semibold text-slate-700">{{ $category->name }}</span>
                </a>
            @endforeach
        </div>
    </section>

    <section class="mt-9">
        <div class="mb-4 flex items-end justify-between border-b border-slate-200 pb-3">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-orange-600">Pilihan mitra</p>
                <h2 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">Produk unggulan</h2>
            </div>
            <a href="{{ route('catalog') }}" class="text-sm font-semibold text-orange-700 hover:text-orange-800">Lihat semua →</a>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-5 lg:grid-cols-4 xl:grid-cols-6">
            @forelse ($featuredProducts as $product)
                <article class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
                    <a href="{{ route('products.show', $product) }}">
                        <img src="{{ $product->image_url ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=700&q=80' }}" alt="{{ $product->name }}" class="aspect-square w-full bg-slate-100 object-cover">
                    </a>
                    <div class="p-3">
                        <span class="rounded bg-orange-100 px-2 py-1 text-[10px] font-bold uppercase text-orange-700">Unggulan</span>
                        <a href="{{ route('products.show', $product) }}" class="mt-2 block line-clamp-2 min-h-10 text-sm font-semibold text-slate-800 hover:text-orange-700">{{ $product->name }}</a>
                        <x-product-rating :product="$product" />
                        <p class="mt-2 text-base font-black text-orange-700">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                        <p class="text-xs text-slate-500">per {{ (float) $product->unit_quantity }} {{ $product->unit }}</p>
                        @if ($product->supplier?->supplierProfile?->city)
                            <p class="mt-1 truncate text-xs text-slate-500">{{ $product->supplier->supplierProfile->city }}</p>
                        @endif
                        <form method="POST" action="{{ route('cart.items.store', $product) }}" class="mt-3">
                            @csrf
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" @disabled($product->stock < 1) class="w-full rounded-lg bg-orange-600 px-3 py-2 text-xs font-bold text-white hover:bg-orange-700 disabled:cursor-not-allowed disabled:bg-slate-300">+ Keranjang</button>
                        </form>
                    </div>
                </article>
            @empty
                <p class="col-span-full rounded-xl bg-white p-8 text-center text-sm text-slate-500">Belum ada produk unggulan.</p>
            @endforelse
        </div>
    </section>

    <section class="mt-9 mb-8 rounded-2xl bg-orange-50 p-5 sm:p-7">
        <div class="mb-4 flex items-end justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-orange-600">Baru tersedia</p>
                <h2 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">Produk terbaru</h2>
            </div>
            <a href="{{ route('catalog') }}" class="text-sm font-semibold text-orange-700 hover:text-orange-800">Jelajahi katalog →</a>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($latestProducts as $product)
                <a href="{{ route('products.show', $product) }}" class="flex items-center gap-4 rounded-xl border border-orange-100 bg-white p-3 transition hover:shadow-md">
                    <img src="{{ $product->image_url ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=200&q=80' }}" alt="{{ $product->name }}" class="h-16 w-16 rounded-lg bg-slate-100 object-cover">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-slate-800">{{ $product->name }}</p>
                        <x-product-rating :product="$product" />
                        <p class="mt-1 text-xs text-slate-500">{{ $product->category->name ?? 'Produk' }}</p>
                        <p class="mt-1 text-sm font-black text-orange-700">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                    </div>
                </a>
            @empty
                <p class="col-span-full text-sm text-slate-500">Belum ada produk terbaru.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
