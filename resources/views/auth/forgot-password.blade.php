@extends('layouts.app')

@section('title', 'Lupa Kata Sandi - SPPG MART')

@section('content')
<div class="mx-auto max-w-md px-4 py-10 sm:py-14">
    <div class="rounded-3xl border border-sky-100 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#167ba5]">Pemulihan akun</p>
        <h1 class="mt-2 text-2xl font-black text-slate-900 sm:text-3xl">Lupa kata sandi?</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-600">Masukkan email akun Anda. Jika terdaftar, kami akan mengirim tautan untuk mengatur kata sandi baru.</p>

        @if (session('status'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email terdaftar</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                @error('email')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" class="w-full rounded-xl bg-[#167ba5] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#105f82]">Kirim tautan reset</button>
        </form>

        <p class="mt-5 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-[#126586] hover:underline">Kembali ke halaman masuk</a></p>
    </div>
</div>
@endsection
