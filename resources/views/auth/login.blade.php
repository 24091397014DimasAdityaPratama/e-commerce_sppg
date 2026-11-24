@extends('layouts.app')

@section('title', 'Masuk - SPPG MART')

@section('content')
<div class="mx-auto max-w-lg px-4 py-8 sm:py-12">
    <div class="rounded-2xl border border-sky-100 bg-white p-5 shadow-sm sm:rounded-3xl sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#167ba5]">Akun SPPG MART</p>
        <h1 class="mt-2 text-2xl font-black text-slate-900 sm:text-3xl">Masuk ke akun Anda</h1>
        <p class="mt-2 text-sm text-slate-600">Lanjutkan belanja dan kelola pesanan Anda.</p>

        @if (session('status'))
            <div class="mt-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4 sm:mt-6">
            @csrf
            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" required autofocus aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4">
                @error('email')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <div class="mb-1.5 flex items-center justify-between gap-3">
                    <label for="password" class="block text-sm font-semibold text-slate-700">Kata sandi</label>
                    <a href="{{ route('password.request') }}" class="text-xs font-semibold text-[#126586] hover:underline">Lupa kata sandi?</a>
                </div>
                <input id="password" name="password" type="password" autocomplete="current-password" required aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4">
                @error('password')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            @include('auth.partials.captcha')
            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-sky-700 focus:ring-sky-500">
                Ingat saya
            </label>
            <button type="submit" class="min-h-11 w-full rounded-xl bg-[#167ba5] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#105f82]">Masuk</button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-500">Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-[#126586] hover:underline">Daftar sekarang</a></p>
    </div>
</div>

@if (session('login_failed'))
    <div data-login-error role="alertdialog" aria-modal="true" aria-labelledby="login-error-title" aria-describedby="login-error-description" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
        <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-red-100 text-xl font-black text-red-700" aria-hidden="true">!</div>
            <h2 id="login-error-title" class="mt-4 text-lg font-black text-slate-900">Tidak dapat masuk</h2>
            <p id="login-error-description" class="mt-2 text-sm text-slate-600">Email atau kata sandi salah. Silakan periksa kembali lalu coba lagi.</p>
            <button type="button" data-close-login-error class="mt-5 w-full rounded-xl bg-[#167ba5] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#105f82]">Coba lagi</button>
        </div>
    </div>
@endif
@endsection
