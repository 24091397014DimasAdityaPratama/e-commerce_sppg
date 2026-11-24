@extends('layouts.app')

@section('title', 'Sesi Berakhir - SPPG MART')

@section('content')
<div class="mx-auto max-w-2xl px-4 py-24 text-center sm:px-6 lg:px-8">
    <p class="text-sm font-bold uppercase tracking-[0.2em] text-emerald-600">419 · Sesi Berakhir</p>
    <h1 class="mt-4 text-3xl font-black text-slate-900">Halaman ini sudah kedaluwarsa</h1>
    <p class="mt-4 text-slate-600">Sesi Anda berakhir demi keamanan. Masuk kembali, lalu buka ulang halaman dan kirim formulir sekali lagi.</p>
    <div class="mt-8 flex flex-wrap justify-center gap-3">
        <a href="{{ route('login') }}" class="rounded-full bg-emerald-600 px-6 py-3 font-semibold text-white hover:bg-emerald-700">Masuk Kembali</a>
        <a href="{{ route('home') }}" class="rounded-full border border-slate-300 px-6 py-3 font-semibold text-slate-700 hover:bg-slate-50">Ke Beranda</a>
    </div>
</div>
@endsection
