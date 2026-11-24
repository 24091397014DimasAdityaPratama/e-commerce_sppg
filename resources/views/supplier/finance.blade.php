@extends('layouts.app')

@section('title', 'Arus Kas Toko - SPPG MART')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
    <div class="mb-7">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">Pusat Penjual</p>
        <h1 class="mt-2 text-3xl font-black text-slate-900 sm:text-4xl">Arus Kas & Keuangan</h1>
        <p class="mt-2 text-slate-600">Pemasukan dihitung dari pembayaran yang sudah dikonfirmasi; catat biaya operasional agar arus kas lebih lengkap.</p>
    </div>
    @include('supplier.partials.navigation', ['active' => 'finance'])

    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <form method="GET" action="{{ route('supplier.finance') }}" class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
        <div class="grid flex-1 gap-3 sm:grid-cols-2">
            <div>
                <label for="from" class="mb-1 block text-xs font-semibold text-slate-600">Dari tanggal</label>
                <input type="date" name="from" id="from" value="{{ $from }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="to" class="mb-1 block text-xs font-semibold text-slate-600">Sampai tanggal</label>
                <input type="date" name="to" id="to" value="{{ $to }}" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <button class="rounded-full bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Terapkan</button>
            <a href="{{ route('supplier.finance.export', ['from' => $from, 'to' => $to]) }}" class="rounded-full border border-emerald-200 px-5 py-2.5 text-center text-sm font-semibold text-emerald-700">Ekspor CSV</a>
        </div>
    </form>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-3xl bg-emerald-700 p-5 text-white sm:p-6">
            <p class="text-sm text-emerald-100">Pemasukan terbayar</p>
            <p class="mt-3 break-words text-2xl font-black">Rp {{ number_format($revenue, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-3xl bg-red-700 p-5 text-white sm:p-6">
            <p class="text-sm text-red-100">Pengeluaran tercatat</p>
            <p class="mt-3 break-words text-2xl font-black">Rp {{ number_format($expenseTotal, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-3xl bg-slate-900 p-5 text-white sm:p-6">
            <p class="text-sm text-slate-300">Arus kas bersih</p>
            <p class="mt-3 break-words text-2xl font-black">Rp {{ number_format($netCashflow, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-3xl bg-amber-400 p-5 text-slate-950 sm:p-6">
            <p class="text-sm text-amber-950">Pesanan belum dibayar</p>
            <p class="mt-3 break-words text-2xl font-black">Rp {{ number_format($unpaidTotal, 0, ',', '.') }}</p>
        </div>
    </div>
    <p class="mt-3 text-xs text-slate-500">Periode laporan: {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }} – {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}. Arus kas bersih belum dikurangi biaya/komisi platform jika ada.</p>

    <div class="mt-8 grid items-start gap-6 xl:grid-cols-[1.15fr_0.85fr]">
        <section class="min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-xl font-black text-slate-900">Pembayaran diterima</h2>
            <div class="mt-4 space-y-3">
                @forelse ($orders as $order)
                    <a href="{{ route('transactions.show', $order) }}" class="flex flex-col gap-2 border-t border-slate-100 pt-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900">Pesanan #{{ $order->id }} · {{ $order->buyer->name }}</p>
                            <p class="text-xs text-slate-500">{{ $order->payment_verified_at?->format('d M Y, H:i') }} · {{ ucfirst($order->payment_method ?? 'transfer') }}</p>
                        </div>
                        <p class="shrink-0 font-bold text-emerald-700">+ Rp {{ number_format($order->total, 0, ',', '.') }}</p>
                    </a>
                @empty
                    <p class="border-t border-slate-100 pt-4 text-sm text-slate-500">Belum ada pembayaran terkonfirmasi pada periode ini.</p>
                @endforelse
            </div>
            <div class="mt-5">{{ $orders->links() }}</div>
        </section>

        <section class="min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-xl font-black text-slate-900">Catat pengeluaran</h2>
            <form method="POST" action="{{ route('supplier.expenses.store') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                <div class="sm:col-span-2">
                    <label for="title" class="mb-1 block text-xs font-semibold text-slate-600">Nama biaya</label>
                    <input type="text" name="title" id="title" required maxlength="255" value="{{ old('title') }}" placeholder="Contoh: bensin pengiriman" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="category" class="mb-1 block text-xs font-semibold text-slate-600">Kategori</label>
                    <select name="category" id="category" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm">
                        <option value="operasional">Operasional</option>
                        <option value="transportasi">Transportasi</option>
                        <option value="bahan_baku">Bahan baku</option>
                        <option value="kemasan">Kemasan</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label for="amount" class="mb-1 block text-xs font-semibold text-slate-600">Nominal (Rp)</label>
                    <input type="number" name="amount" id="amount" required min="1" step="1" value="{{ old('amount') }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                    @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="expense_date" class="mb-1 block text-xs font-semibold text-slate-600">Tanggal</label>
                    <input type="date" name="expense_date" id="expense_date" required max="{{ now()->toDateString() }}" value="{{ old('expense_date', now()->toDateString()) }}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label for="notes" class="mb-1 block text-xs font-semibold text-slate-600">Catatan (opsional)</label>
                    <textarea name="notes" id="notes" rows="2" maxlength="1000" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">{{ old('notes') }}</textarea>
                </div>
                <button class="rounded-full bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white sm:col-span-2">Simpan Pengeluaran</button>
            </form>

            <h3 class="mt-8 border-t border-slate-100 pt-5 font-bold text-slate-900">Riwayat pengeluaran</h3>
            <div class="mt-3 space-y-3">
                @forelse ($expenses as $expense)
                    <div class="flex items-start justify-between gap-3 border-t border-slate-100 pt-3">
                        <div class="min-w-0">
                            <p class="break-words font-semibold text-slate-900">{{ $expense->title }}</p>
                            <p class="text-xs text-slate-500">{{ ucfirst(str_replace('_', ' ', $expense->category)) }} · {{ $expense->expense_date->format('d M Y') }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="font-bold text-red-700">- Rp {{ number_format($expense->amount, 0, ',', '.') }}</p>
                            <form method="POST" action="{{ route('supplier.expenses.destroy', $expense) }}" onsubmit="return confirm('Hapus catatan pengeluaran ini?')">
                                @csrf
                                @method('DELETE')
                                <button class="mt-1 text-xs font-semibold text-slate-500 underline">Hapus</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="border-t border-slate-100 pt-3 text-sm text-slate-500">Belum ada pengeluaran yang dicatat.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $expenses->links() }}</div>
        </section>
    </div>
</div>
@endsection
