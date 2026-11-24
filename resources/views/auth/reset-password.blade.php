@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi - SPPG MART')

@section('content')
<div class="mx-auto max-w-md px-4 py-10 sm:py-14">
    <div class="rounded-3xl border border-sky-100 bg-white p-6 shadow-sm sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#167ba5]">Pemulihan akun</p>
        <h1 class="mt-2 text-2xl font-black text-slate-900 sm:text-3xl">Atur kata sandi baru</h1>
        <p class="mt-2 text-sm text-slate-600">Buat kata sandi baru minimal 8 karakter.</p>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                @error('email')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Kata sandi baru</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                @error('password')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Ulangi kata sandi</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
            </div>
            <button type="submit" class="w-full rounded-xl bg-[#167ba5] px-5 py-3 text-sm font-bold text-white transition hover:bg-[#105f82]">Simpan kata sandi baru</button>
        </form>
    </div>
</div>
@endsection
