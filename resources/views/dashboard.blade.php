@extends('layouts.app')

@section('title', 'Dashboard - SPPG MART')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#167ba5]">Dashboard</p>
            <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Selamat datang, {{ $user->name }}</h1>
        </div>

        @if ($user->isSupplier())
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('supplier.products.create') }}" class="rounded-xl bg-[#167ba5] px-4 py-2.5 text-sm font-bold text-white transition hover:bg-[#105f82]">+ Tambah Produk</a>
            </div>
        @elseif ($user->isBuyer())
            <a href="{{ route('transactions.index') }}" class="rounded-xl bg-[#167ba5] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#105f82]">Transaksi ({{ $summary['transactions'] ?? 0 }})</a>
        @else
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.suppliers.index') }}" class="rounded-xl bg-[#167ba5] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#105f82]">Verifikasi pemasok ({{ $summary['pendingSuppliers'] ?? 0 }})</a>
                <a href="{{ route('admin.products.index') }}" class="rounded-xl border border-sky-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#126586]">Moderasi produk</a>
                <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-sky-200 bg-white px-4 py-2.5 text-sm font-semibold text-[#126586]">Kelola akun</a>
            </div>
        @endif
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if ($user->isSupplier())
        @if ($user->supplierProfile?->verification_status !== 'approved')
            <div role="status" class="mb-5 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950">
                <p class="font-bold">
                    @if ($user->supplierProfile?->verification_status === 'rejected')
                        Verifikasi pemasok ditolak.
                    @else
                        Toko belum tampil untuk buyer karena verifikasi NIB masih menunggu persetujuan admin.
                    @endif
                </p>
                @if ($user->supplierProfile?->verification_status === 'rejected' && $user->supplierProfile?->notes)
                    <p class="mt-1">{{ $user->supplierProfile->notes }}</p>
                @endif
                <a href="{{ route('supplier.payment-settings.edit') }}" class="mt-2 inline-flex font-bold text-[#126586] underline">Periksa profil usaha dan dokumen NIB</a>
            </div>
        @endif

        @include('supplier.partials.navigation', ['active' => 'overview'])

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('transactions.index') }}" class="rounded-2xl border border-sky-100 bg-white p-4 shadow-sm transition hover:border-sky-200 hover:shadow-md">
                <p class="text-xs font-semibold text-slate-500">Penjualan terbayar · 30 hari</p>
                <p class="mt-2 break-words text-2xl font-black text-[#126586]">Rp {{ number_format($summary['sales30Days'] ?? 0, 0, ',', '.') }}</p>
                <p class="mt-1 text-xs text-slate-500">Dari pembayaran terkonfirmasi</p>
            </a>
            <a href="{{ route('transactions.index') }}" class="rounded-2xl border border-sky-100 bg-white p-4 shadow-sm transition hover:border-sky-200 hover:shadow-md">
                <p class="text-xs font-semibold text-slate-500">Pesanan perlu diproses</p>
                <p class="mt-2 text-2xl font-black text-[#126586]">{{ $summary['pendingOrders'] ?? 0 }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $summary['processingOrders'] ?? 0 }} sedang disiapkan</p>
            </a>
            <a href="{{ route('supplier.inventory') }}" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 shadow-sm transition hover:border-amber-300 hover:shadow-md">
                <p class="text-xs font-semibold text-amber-800">Produk stok menipis</p>
                <p class="mt-2 text-2xl font-black text-amber-900">{{ $summary['lowStock'] ?? 0 }}</p>
                <p class="mt-1 text-xs text-amber-800">Periksa dan tambah persediaan</p>
            </a>
            <a href="{{ route('supplier.inventory') }}" class="rounded-2xl border border-sky-100 bg-white p-4 shadow-sm transition hover:border-sky-200 hover:shadow-md">
                <p class="text-xs font-semibold text-slate-500">Produk aktif</p>
                <p class="mt-2 text-2xl font-black text-[#126586]">{{ $summary['activeProducts'] ?? 0 }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ $summary['stockTotal'] ?? 0 }} unit stok tersedia</p>
            </a>
        </div>

        <div class="mt-8 grid gap-6 xl:grid-cols-[1.4fr_0.9fr]">
            <section class="min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Aktivitas toko</p>
                        <h2 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">Pesanan terbaru</h2>
                    </div>
                    <a href="{{ route('transactions.index') }}" class="text-sm font-semibold text-emerald-700">Semua pesanan →</a>
                </div>
                @forelse ($recentOrders as $order)
                    <a href="{{ route('transactions.show', $order) }}" class="flex flex-col gap-3 border-t border-slate-100 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-900">Pesanan #{{ $order->id }} · {{ $order->buyer->name }}</p>
                            <p class="mt-1 truncate text-sm text-slate-500">{{ $order->items->sum('quantity') }} item · {{ $order->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div class="flex items-center justify-between gap-4 sm:block sm:text-right">
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-700">{{ ucfirst($order->status) }}</span>
                            <p class="font-bold text-slate-900 sm:mt-2">Rp {{ number_format($order->total, 0, ',', '.') }}</p>
                        </div>
                    </a>
                @empty
                    <p class="border-t border-slate-100 py-6 text-sm text-slate-500">Belum ada pesanan masuk.</p>
                @endforelse
            </section>

            <section class="min-w-0 rounded-3xl border border-amber-200 bg-amber-50 p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-amber-800">Peringatan persediaan</p>
                        <h2 class="mt-1 text-xl font-black text-slate-900">Stok menipis</h2>
                    </div>
                    <a href="{{ route('supplier.inventory') }}" class="text-sm font-semibold text-amber-900">Kelola →</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($lowStockProducts as $product)
                        <div class="flex items-center justify-between gap-3 rounded-2xl bg-white p-3">
                            <div class="min-w-0">
                                <p class="truncate font-semibold text-slate-900">{{ $product->name }}</p>
                                <p class="text-xs text-slate-500">Batas peringatan {{ $product->low_stock_threshold }}</p>
                            </div>
                            <span class="shrink-0 rounded-full bg-red-100 px-3 py-1 text-sm font-black text-red-700">{{ $product->stock }} tersisa</span>
                        </div>
                    @empty
                        <p class="rounded-2xl bg-white p-4 text-sm text-slate-600">Semua produk aktif berada di atas batas stok minimum.</p>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="mt-8 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Katalog toko</p>
                    <h2 class="mt-1 text-xl font-black text-slate-900 sm:text-2xl">Produk terbaru</h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('supplier.inventory') }}" class="rounded-full border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Semua produk →</a>
                </div>
            </div>
            @forelse ($products as $product)
                <div class="grid gap-3 border-t border-slate-100 py-3 sm:grid-cols-[minmax(0,1fr)_auto_auto] sm:items-center">
                    <div class="min-w-0">
                        <p class="truncate font-bold text-slate-900">{{ $product->name }}</p>
                        <p class="text-sm text-slate-500">{{ $product->category->name ?? 'Tanpa kategori' }} · {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</p>
                    </div>
                    <p class="shrink-0 text-sm font-semibold text-slate-700">{{ $product->stock }} stok · Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                    <div class="flex items-center gap-2 sm:justify-end">
                        <a href="{{ route('supplier.products.edit', $product) }}" class="rounded-full border border-sky-200 px-3 py-1.5 text-xs font-semibold text-sky-800 transition hover:bg-sky-50">Edit</a>
                        <form method="POST" action="{{ route('supplier.products.destroy', $product) }}" onsubmit="return confirm('Yakin ingin menghapus produk ini? Produk yang memiliki riwayat tidak dapat dihapus.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 transition hover:bg-red-50">Hapus</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="border-t border-slate-100 py-5 text-sm text-slate-500">Belum ada produk.</p>
            @endforelse
        </section>
    @elseif ($user->isBuyer())
        <div class="grid gap-6 md:grid-cols-3">
            <div class="rounded-3xl bg-emerald-600 p-6 text-white shadow-lg">
                <div class="text-sm uppercase tracking-[0.2em] text-emerald-100">Role</div>
                <div class="mt-4 text-3xl font-black">SPPG</div>
            </div>
            <div class="rounded-3xl bg-slate-900 p-6 text-white">
                <div class="text-sm uppercase tracking-[0.2em] text-slate-300">Produk tersedia</div>
                <div class="mt-4 text-3xl font-black">{{ $summary['products'] }}</div>
            </div>
            <div class="rounded-3xl bg-sky-100 p-6 text-sky-950">
                <div class="text-sm uppercase tracking-[0.2em] text-slate-700">Marketplace</div>
                <div class="mt-4 text-3xl font-black">SPPG</div>
            </div>
        </div>

        <div class="mt-12 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-black text-slate-900">Produk terbaru untuk dibeli</h2>
            <a href="{{ route('transactions.index') }}" class="mt-2 inline-block text-sm font-semibold text-emerald-700">Lihat semua transaksi →</a>
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @foreach ($products as $product)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-slate-900">{{ $product->name }}</h3>
                            <span class="text-sm font-semibold text-emerald-600">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">{{ $product->category->name ?? 'Umum' }} • Stok {{ $product->stock }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="grid gap-6 md:grid-cols-4">
            <div class="rounded-3xl bg-emerald-600 p-6 text-white shadow-lg">
                <div class="text-sm uppercase tracking-[0.2em] text-emerald-100">Role</div>
                <div class="mt-4 text-3xl font-black">{{ ucfirst($user->role) }}</div>
            </div>
            <div class="rounded-3xl bg-slate-900 p-6 text-white">
                <div class="text-sm uppercase tracking-[0.2em] text-slate-300">Produk aktif</div>
                <div class="mt-4 text-3xl font-black">{{ $summary['products'] }}</div>
            </div>
            <div class="rounded-3xl bg-sky-100 p-6 text-sky-950">
                <div class="text-sm uppercase tracking-[0.2em] text-slate-700">Pengguna</div>
                <div class="mt-4 text-3xl font-black">{{ $summary['users'] }}</div>
            </div>
            <a href="{{ route('admin.suppliers.index') }}" class="rounded-3xl border border-amber-200 bg-amber-50 p-6 text-amber-950 transition hover:border-amber-300">
                <div class="text-sm uppercase tracking-[0.2em] text-amber-800">Pemasok menunggu</div>
                <div class="mt-4 text-3xl font-black">{{ $summary['pendingSuppliers'] ?? 0 }}</div>
                <div class="mt-2 text-sm font-semibold">Buka verifikasi →</div>
            </a>
        </div>

        <div class="mt-12 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-2xl font-black text-slate-900">Produk terbaru</h2>
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                @foreach ($products as $product)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-slate-900">{{ $product->name }}</h3>
                            <span class="text-sm font-semibold text-emerald-600">Rp {{ number_format($product->price, 0, ',', '.') }}</span>
                        </div>
                        <p class="mt-2 text-sm text-slate-500">Supplier: {{ $product->supplier->name ?? 'Tidak diketahui' }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
