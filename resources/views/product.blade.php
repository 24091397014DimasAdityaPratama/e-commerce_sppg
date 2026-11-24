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
            <x-product-rating :product="$product" />
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

    <section class="mt-10 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Ulasan pembeli</p>
                <h2 class="mt-1 text-2xl font-black text-slate-900">Rating & review</h2>
                <x-product-rating :product="$product" />
            </div>
        </div>

        @if (session('success'))
            <p role="status" class="mt-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</p>
        @endif
        @if ($errors->has('rating') || $errors->has('comment'))
            <p role="alert" class="mt-5 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first('rating') ?: $errors->first('comment') }}</p>
        @endif

        @if ($canReview)
            <form method="POST" action="{{ route('products.reviews.store', $product) }}" class="mt-6 max-w-2xl space-y-4 rounded-2xl bg-slate-50 p-5">
                @csrf
                <div>
                    <label for="rating" class="mb-2 block text-sm font-semibold text-slate-700">Rating (1–5 bintang)</label>
                    <select id="rating" name="rating" required class="w-full rounded-xl border-slate-300">
                        <option value="">Pilih rating</option>
                        @for ($rating = 5; $rating >= 1; $rating--)
                            <option value="{{ $rating }}" @selected(old('rating') == $rating)>{{ $rating }} bintang</option>
                        @endfor
                    </select>
                </div>
                <div>
                    <label for="comment" class="mb-2 block text-sm font-semibold text-slate-700">Ulasan (opsional)</label>
                    <textarea id="comment" name="comment" rows="3" maxlength="2000" class="w-full rounded-xl border-slate-300" placeholder="Ceritakan kualitas produk yang diterima">{{ old('comment') }}</textarea>
                </div>
                <button class="rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Kirim ulasan</button>
            </form>
        @elseif ($existingReview)
            <p class="mt-5 rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-600">Anda sudah mengulas produk ini dengan rating {{ $existingReview->rating }}/5.</p>
        @elseif (auth()->check() && auth()->user()->isBuyer())
            <p class="mt-5 text-sm text-slate-500">Ulasan tersedia setelah pesanan produk ini selesai dikirim.</p>
        @endif

        <div class="mt-6 space-y-4">
            @forelse ($product->visibleReviews as $review)
                <article class="border-t border-slate-100 pt-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-semibold text-slate-900">{{ $review->user->name }}</p>
                        <p class="text-sm font-semibold text-amber-600">{{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }} <span class="text-slate-600">{{ $review->rating }}/5</span></p>
                    </div>
                    @if ($review->comment)
                        <p class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ $review->comment }}</p>
                    @endif
                    <p class="mt-2 text-xs text-slate-400">{{ $review->created_at->format('d M Y') }} · Pembelian terverifikasi</p>
                </article>
            @empty
                <p class="border-t border-slate-100 pt-4 text-sm text-slate-500">Belum ada ulasan untuk produk ini.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
