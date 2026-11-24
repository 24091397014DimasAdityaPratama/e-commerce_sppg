@extends('layouts.app')

@section('title', 'Produk & Stok - SPPG MART')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
    <div class="mb-7">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">Pusat Penjual</p>
        <h1 class="mt-2 text-3xl font-black text-slate-900 sm:text-4xl">Produk & Manajemen Stok</h1>
        <p class="mt-2 text-slate-600">Catat stok masuk, stok keluar, dan penyesuaian. Setiap perubahan tercatat di riwayat persediaan.</p>
    </div>
    @include('supplier.partials.navigation', ['active' => 'inventory'])

    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ $errors->first() }}</div>
    @endif

    <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" action="{{ route('supplier.inventory') }}" class="flex w-full gap-2 sm:max-w-md">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari nama produk..." class="min-w-0 flex-1 rounded-full border border-slate-300 px-4 py-2.5 text-sm focus:border-emerald-500 focus:outline-none">
            <button class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Cari</button>
        </form>
        <a href="{{ route('supplier.products.create') }}" class="rounded-full bg-emerald-600 px-5 py-2.5 text-center text-sm font-semibold text-white hover:bg-emerald-700">+ Tambah Produk</a>
    </div>

    <section class="space-y-4">
        @forelse ($products as $product)
            <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-900">{{ $product->name }}</h2>
                            <span class="rounded-full px-3 py-1 text-xs font-bold {{ $product->stock <= $product->low_stock_threshold ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $product->stock <= $product->low_stock_threshold ? 'Stok menipis' : 'Stok aman' }}</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-500">{{ $product->category->name ?? 'Tanpa kategori' }} · Isi {{ (float) $product->unit_quantity }} {{ $product->unit }} · Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                        <p class="mt-2 text-sm text-slate-700">Stok sekarang: <strong>{{ $product->stock }}</strong> · Batas peringatan: {{ $product->low_stock_threshold }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <a href="{{ route('supplier.products.edit', $product) }}" class="rounded-full border border-sky-200 px-3 py-1.5 text-xs font-semibold text-sky-800 transition hover:bg-sky-50">Edit produk</a>
                            <form method="POST" action="{{ route('supplier.products.destroy', $product) }}" onsubmit="return confirm('Yakin ingin menghapus produk ini? Produk yang memiliki riwayat tidak dapat dihapus.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">Hapus produk</button>
                            </form>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('supplier.inventory.update', $product) }}" class="grid w-full gap-3 rounded-2xl bg-slate-50 p-3 sm:grid-cols-2 lg:max-w-3xl lg:grid-cols-4">
                        @csrf
                        <select name="movement_type" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm" required>
                            <option value="incoming">Stok masuk</option>
                            <option value="outgoing">Stok keluar / rusak</option>
                            <option value="adjustment">Set stok akhir</option>
                        </select>
                        <input type="number" name="quantity" min="1" placeholder="Jumlah masuk/keluar" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <input type="number" name="new_stock" min="0" placeholder="Stok akhir (penyesuaian)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <input type="number" name="low_stock_threshold" min="0" value="{{ $product->low_stock_threshold }}" aria-label="Batas peringatan stok" placeholder="Batas stok menipis" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <input type="text" name="notes" maxlength="255" placeholder="Catatan, mis. barang rusak" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <button class="rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white sm:col-span-2 lg:col-span-4">Simpan Perubahan Stok</button>
                    </form>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-slate-200 bg-white p-10 text-center text-slate-600">Produk tidak ditemukan. Tambahkan produk untuk mulai mengatur stok.</div>
        @endforelse
    </section>

    <div class="mt-6">{{ $products->links() }}</div>

    <section class="mt-10 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <div class="mb-5">
            <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Audit Persediaan</p>
            <h2 class="mt-1 text-2xl font-black text-slate-900">Riwayat stok terbaru</h2>
        </div>
        <div class="space-y-3">
            @forelse ($movements as $movement)
                <div class="flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900">{{ $movement->product->name }} <span class="font-normal text-slate-500">· {{ $movement->notes }}</span></p>
                        <p class="text-xs text-slate-500">{{ $movement->created_at->format('d M Y, H:i') }} · {{ str_replace('_', ' ', ucfirst($movement->type)) }}</p>
                    </div>
                    <p class="shrink-0 font-bold {{ $movement->quantity >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }} <span class="font-normal text-slate-500">({{ $movement->stock_before }} → {{ $movement->stock_after }})</span></p>
                </div>
            @empty
                <p class="border-t border-slate-100 pt-4 text-sm text-slate-500">Belum ada perubahan stok. Pesanan dan perubahan stok berikutnya akan tercatat di sini.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
