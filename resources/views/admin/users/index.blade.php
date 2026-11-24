@extends('layouts.app')

@section('title', 'Kelola Akun - SPPG MART')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#167ba5]">Administrator</p>
            <h1 class="mt-1 text-3xl font-black text-slate-900">Kelola akun</h1>
            <p class="mt-2 text-sm text-slate-600">Blokir atau aktifkan kembali akun pembeli dan pemasok. Akun administrator tidak dapat diblokir dari halaman ini.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="text-sm font-semibold text-[#126586] hover:underline">Moderasi produk →</a>
    </div>

    @if (session('success'))
        <div role="status" class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap gap-2">
        @foreach (['all' => 'Semua', 'active' => 'Aktif', 'blocked' => 'Diblokir'] as $value => $label)
            <a href="{{ route('admin.users.index', ['status' => $value]) }}" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $status === $value ? 'border-sky-700 bg-sky-700 text-white' : 'border-slate-200 bg-white text-slate-700' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($users as $user)
            <article class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="font-bold text-slate-900">{{ $user->name }}</h2>
                        <span class="rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-sky-800">{{ $user->role === 'buyer' ? 'Pembeli' : 'Pemasok' }}</span>
                        <span class="rounded-full px-3 py-1 text-xs font-bold {{ $user->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">{{ $user->status === 'active' ? 'Aktif' : 'Diblokir' }}</span>
                    </div>
                    <p class="mt-1 break-all text-sm text-slate-600">{{ $user->email }}</p>
                    @if ($user->isSupplier())
                        <p class="mt-1 text-xs text-slate-500">Toko: {{ $user->supplierProfile?->business_name ?? 'Profil belum lengkap' }} · Verifikasi {{ $user->supplierProfile?->verification_status ?? 'belum ada' }}</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.users.status.update', $user) }}" onsubmit="return confirm('Yakin ingin {{ $user->status === 'active' ? 'memblokir' : 'mengaktifkan kembali' }} akun ini?');">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $user->status === 'active' ? 'blocked' : 'active' }}">
                    <button class="rounded-full px-4 py-2 text-sm font-semibold {{ $user->status === 'active' ? 'border border-red-200 text-red-700 hover:bg-red-50' : 'bg-emerald-600 text-white hover:bg-emerald-700' }}">{{ $user->status === 'active' ? 'Blokir akun' : 'Aktifkan akun' }}</button>
                </form>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-600">Tidak ada akun pada filter ini.</div>
        @endforelse
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
</div>
@endsection
