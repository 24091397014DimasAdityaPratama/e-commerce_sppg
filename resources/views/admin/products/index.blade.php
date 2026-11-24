@extends('layouts.app')

@section('title', 'Moderasi Produk - SPPG MART')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#167ba5]">Administrator</p>
            <h1 class="mt-1 text-3xl font-black text-slate-900">Moderasi produk</h1>
            <p class="mt-2 text-sm text-slate-600">Sembunyikan produk yang tidak memenuhi kualitas. Produk dengan riwayat transaksi tetap disimpan untuk menjaga catatan pembelian.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-[#126586] hover:underline">Kembali ke dashboard</a>
    </div>

    @if (session('success'))
        <div role="status" class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach (['all' => 'Semua', 'active' => 'Tampil', 'hidden' => 'Disembunyikan'] as $value => $label)
            <a href="{{ route('admin.products.index', ['visibility' => $value]) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $visibility === $value ? 'border-sky-700 bg-sky-700 text-white' : 'border-slate-200 bg-white text-slate-700' }}">{{ $label }}</a>
        @endforeach
        <a href="{{ route('admin.users.index') }}" class="ml-auto rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">Kelola akun →</a>
    </div>

    <div class="space-y-4">
        @forelse ($products as $product)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <img src="{{ $product->image_url ?? 'https://images.unsplash.com/photo-1542838132-92c53300491e' }}" alt="" class="h-24 w-24 rounded-2xl bg-slate-100 object-cover">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-black text-slate-900">{{ $product->name }}</h2>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $product->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $product->is_active ? 'Tampil' : 'Disembunyikan' }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-600">{{ $product->category->name ?? 'Tanpa kategori' }} · Pemasok: {{ $product->supplier->name ?? 'Tidak diketahui' }}</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">Rp {{ number_format($product->price, 0, ',', '.') }} · Stok {{ $product->stock }}</p>
                        <x-product-rating :product="$product" />
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:justify-end">
                        <form method="POST" action="{{ route('admin.products.visibility.update', $product) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_active" value="{{ $product->is_active ? 0 : 1 }}">
                            <button class="rounded-full border border-sky-200 px-4 py-2 text-sm font-semibold text-sky-800 hover:bg-sky-50">{{ $product->is_active ? 'Sembunyikan' : 'Tampilkan' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('Hapus produk ini? Jika ada riwayat transaksi, produk hanya akan disembunyikan.');">
                            @csrf
                            @method('DELETE')
                            <button class="rounded-full border border-red-200 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                        </form>
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-600">Belum ada produk pada filter ini.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</div>
@endsection
