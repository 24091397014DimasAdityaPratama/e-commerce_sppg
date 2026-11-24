@extends('layouts.app')

@section('title', 'SPPG MART | Marketplace Bahan Baku Pangan')

@section('content')
@php($heroProduct = $featuredProducts->first() ?? $latestProducts->first())
<div class="bg-[#f4f9fc]">
    <section class="landing-hero pt-6 sm:pt-8 lg:min-h-[660px] lg:pt-28">
        <div class="relative z-10 mx-auto grid max-w-7xl items-center gap-7 px-4 pb-10 sm:px-6 sm:pb-12 lg:min-h-[520px] lg:grid-cols-[1.05fr_0.95fr] lg:gap-8 lg:px-8 lg:pb-16">
            <div class="max-w-2xl text-white">
                <div class="mb-4 inline-flex max-w-full items-center gap-2 rounded-full border border-white/30 bg-white/10 px-3 py-1.5 text-xs font-bold text-sky-50 shadow-sm backdrop-blur sm:mb-6 sm:px-4 sm:py-2 sm:text-sm">
                    <span class="h-2 w-2 rounded-full bg-sky-200"></span>
                    Marketplace pengadaan pangan SPPG
                </div>
                <h1 class="max-w-3xl text-3xl font-black leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                    Semua kebutuhan pangan, <span class="text-sky-100">dalam satu platform.</span>
                </h1>
                <p class="mt-4 max-w-xl text-sm leading-relaxed text-sky-50 sm:mt-6 sm:text-lg">
                    Temukan bahan baku berkualitas, bandingkan pemasok, dan kelola pesanan SPPG dengan lebih mudah.
                </p>
                <div class="mt-6 flex flex-wrap gap-3 sm:mt-8">
                    <a href="{{ route('catalog') }}" class="rounded-xl bg-white px-6 py-3.5 text-sm font-extrabold text-[#126586] shadow-lg shadow-sky-950/15 transition hover:bg-sky-50">
                        Jelajahi Katalog <span class="ml-2" aria-hidden="true">→</span>
                    </a>
                    @guest
                        <a href="{{ route('register') }}" class="rounded-xl border border-white/40 bg-white/10 px-6 py-3.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20">Buat Akun</a>
                    @else
                        <a href="{{ auth()->user()->isBuyer() ? route('transactions.index') : route('dashboard') }}" class="rounded-xl border border-white/40 bg-white/10 px-6 py-3.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/20">
                            {{ auth()->user()->isBuyer() ? 'Pesanan Saya' : 'Dashboard' }}
                        </a>
                    @endguest
                </div>

            </div>

            <div class="relative mx-auto w-full max-w-xl pb-5 lg:pb-0">
                <div class="absolute -left-7 top-8 h-24 w-24 rounded-full bg-sky-200/20 blur-2xl sm:h-36 sm:w-36"></div>
                <div class="absolute -right-4 bottom-10 h-32 w-32 rounded-full bg-cyan-100/20 blur-2xl sm:h-48 sm:w-48"></div>
                <div class="relative aspect-[1.08/1] overflow-hidden rounded-[2rem] border border-white/25 bg-white/10 p-3 shadow-2xl shadow-sky-950/25 backdrop-blur-sm sm:rounded-[2.5rem] sm:p-4">
                    <div class="relative h-full overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-sky-100 to-cyan-50 sm:rounded-[2rem]">
                        @if ($heroProduct?->image_url)
                            <img src="{{ $heroProduct->image_url }}" alt="{{ $heroProduct->name }}" class="h-full w-full object-cover">
                            <div class="absolute inset-0 bg-gradient-to-t from-[#092f43]/80 via-transparent to-[#0c5f83]/10"></div>
                        @else
                            <div class="flex h-full items-center justify-center bg-gradient-to-br from-sky-100 to-cyan-50">
                                <span class="text-8xl drop-shadow-sm sm:text-9xl">🌾🥬</span>
                            </div>
                        @endif
                        <div class="absolute left-4 top-4 rounded-full border border-white/60 bg-white/90 px-3 py-1.5 text-xs font-bold text-[#126586] shadow-sm backdrop-blur sm:left-6 sm:top-6 sm:px-4 sm:py-2">
                            Pilihan pemasok terpercaya
                        </div>
                        @if ($heroProduct)
                            <div class="absolute inset-x-4 bottom-4 rounded-2xl border border-white/70 bg-white/95 p-4 shadow-xl backdrop-blur sm:inset-x-6 sm:bottom-6 sm:p-5">
                                <div class="flex items-end justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs font-bold uppercase tracking-wider text-[#167ba5]">Produk unggulan</p>
                                        <h2 class="mt-1 truncate text-base font-extrabold text-slate-900 sm:text-lg">{{ $heroProduct->name }}</h2>
                                        <p class="mt-1 truncate text-xs text-slate-500">{{ $heroProduct->supplier->supplierProfile->business_name ?? $heroProduct->supplier->name ?? 'Pemasok SPPG MART' }}</p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-base font-black text-[#126586] sm:text-lg">Rp {{ number_format($heroProduct->price, 0, ',', '.') }}</p>
                                        <p class="text-[11px] text-slate-500">per {{ (float) $heroProduct->unit_quantity }} {{ $heroProduct->unit }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="absolute -right-1 top-1/4 hidden rounded-2xl border border-white/70 bg-white/95 px-4 py-3 shadow-xl sm:block lg:-right-5">
                    <p class="text-xs font-semibold text-slate-500">Belanja lebih praktis</p>
                    <p class="mt-1 text-sm font-extrabold text-[#126586]">Cari · Pilih · Pesan</p>
                </div>
            </div>
        </div>
    </section>

    <section class="relative z-20 mx-auto -mt-7 max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-2xl border border-sky-100 bg-white shadow-xl shadow-sky-900/10">
            <div class="border-b border-slate-100 px-5 py-4 sm:px-7">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#167ba5]">Mulai dari kategori</p>
                <h2 class="mt-1 text-xl font-black text-slate-900">Cari kebutuhan SPPG</h2>
            </div>
            <div class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 sm:gap-4 sm:p-5 lg:grid-cols-6">
                @php($categoryIcons = ['🌾', '🥬', '🫘', '🍗', '🧂', '🥛'])
                @foreach ($categories as $index => $category)
                    <a href="{{ route('catalog', ['category' => $category->id]) }}" class="rounded-xl border border-slate-100 bg-[#f8fbfd] p-3 text-center transition hover:border-sky-200 hover:bg-sky-50 sm:p-4">
                        <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-sky-100 text-xl sm:h-12 sm:w-12 sm:text-2xl">{{ $categoryIcons[$index % count($categoryIcons)] }}</span>
                        <span class="mt-2 block truncate text-xs font-bold text-slate-700 sm:mt-3 sm:text-sm">{{ $category->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-10 pt-16 sm:px-6 lg:px-8 lg:pt-20">
        <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#167ba5]">Pilihan mitra</p>
                <h2 class="mt-2 text-2xl font-black text-slate-900 sm:text-3xl">Produk unggulan</h2>
                <p class="mt-1 text-sm text-slate-500">Bahan pangan pilihan dari pemasok terpercaya.</p>
            </div>
            <a href="{{ route('catalog') }}" class="rounded-xl border border-sky-200 bg-white px-4 py-2.5 text-sm font-bold text-[#126586] transition hover:bg-sky-50">Lihat katalog →</a>
        </div>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 sm:gap-4 lg:grid-cols-4 xl:grid-cols-5">
            @forelse ($featuredProducts as $product)
                <article class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-sky-200 hover:shadow-md">
                    <a href="{{ route('products.show', $product) }}" class="block overflow-hidden bg-slate-100">
                        <img src="{{ $product->image_url ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=700&q=80' }}" alt="{{ $product->name }}" class="aspect-square w-full object-cover">
                    </a>
                    <div class="p-3 sm:p-4">
                        <p class="text-[11px] font-semibold text-[#167ba5]">{{ $product->category->name ?? 'Produk' }}</p>
                        <a href="{{ route('products.show', $product) }}" class="mt-1 block line-clamp-2 min-h-10 text-sm font-bold text-slate-800 hover:text-[#126586]">{{ $product->name }}</a>
                        <p class="mt-2 text-base font-black text-[#126586]">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                        <p class="text-xs text-slate-500">per {{ (float) $product->unit_quantity }} {{ $product->unit }}</p>
                    </div>
                </article>
            @empty
                <p class="col-span-full rounded-2xl border border-dashed border-sky-200 bg-white p-8 text-center text-sm text-slate-500">Produk unggulan akan segera tersedia.</p>
            @endforelse
        </div>
    </section>

    <section id="keunggulan" class="bg-[#0d3f59] py-16 text-white sm:py-20">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="mx-auto mb-10 max-w-2xl text-center">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-sky-200">Belanja aman dan praktis</p>
                <h2 class="mt-2 text-3xl font-black">Pengadaan pangan dalam satu platform</h2>
            </div>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5"><div class="text-2xl">✓</div><h3 class="mt-3 font-bold">Pemasok terverifikasi</h3><p class="mt-1 text-sm leading-relaxed text-sky-100">Temukan mitra pemasok untuk kebutuhan operasional SPPG.</p></div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5"><div class="text-2xl">▦</div><h3 class="mt-3 font-bold">Stok dan harga jelas</h3><p class="mt-1 text-sm leading-relaxed text-sky-100">Periksa informasi produk sebelum menambahkan ke keranjang.</p></div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5"><div class="text-2xl">◉</div><h3 class="mt-3 font-bold">Komunikasi langsung</h3><p class="mt-1 text-sm leading-relaxed text-sky-100">Hubungi pemasok melalui chat untuk menanyakan produk dan pesanan.</p></div>
                <div class="rounded-2xl border border-white/10 bg-white/5 p-5"><div class="text-2xl">↗</div><h3 class="mt-3 font-bold">Pantau pesanan</h3><p class="mt-1 text-sm leading-relaxed text-sky-100">Lihat perkembangan transaksi dari akun Anda.</p></div>
            </div>
        </div>
    </section>

    <section id="faq" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-[0.7fr_1.3fr]">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-[#167ba5]">Bantuan</p>
                <h2 class="mt-2 text-3xl font-black text-slate-900">Pertanyaan umum</h2>
                <p class="mt-3 text-sm leading-relaxed text-slate-600">Butuh informasi lebih lanjut? Tim kami siap membantu.</p>
            </div>
            <div class="space-y-3">
                <details class="group rounded-xl border border-slate-200 bg-white p-5 open:border-sky-200 open:bg-sky-50/50">
                    <summary class="cursor-pointer list-none pr-6 font-bold text-slate-900 marker:hidden">Apakah katalog bisa dilihat tanpa login?<span class="float-right text-[#167ba5] transition group-open:rotate-45">+</span></summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Ya. Katalog dan detail produk dapat dilihat tanpa login. Login diperlukan untuk menggunakan fitur pembelian dan chat.</p>
                </details>
                <details class="group rounded-xl border border-slate-200 bg-white p-5 open:border-sky-200 open:bg-sky-50/50">
                    <summary class="cursor-pointer list-none pr-6 font-bold text-slate-900 marker:hidden">Bagaimana cara menghubungi pemasok?<span class="float-right text-[#167ba5] transition group-open:rotate-45">+</span></summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Masuk sebagai buyer, buka halaman produk atau toko pemasok, lalu pilih tombol Chat.</p>
                </details>
                <details class="group rounded-xl border border-slate-200 bg-white p-5 open:border-sky-200 open:bg-sky-50/50">
                    <summary class="cursor-pointer list-none pr-6 font-bold text-slate-900 marker:hidden">Bagaimana status pemasok diverifikasi?<span class="float-right text-[#167ba5] transition group-open:rotate-45">+</span></summary>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">Pemasok melengkapi profil usaha dan diverifikasi oleh admin platform sebelum berjualan.</p>
                </details>
            </div>
        </div>
    </section>
</div>
@endsection
