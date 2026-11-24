@extends('layouts.app')

@section('title', 'Profil Akun - SPPG MART')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#167ba5]">Akun Saya</p>
            <h1 class="mt-1 text-3xl font-black text-slate-900">Profil Akun</h1>
            <p class="mt-1 text-sm text-slate-600">Kelola informasi akun Anda dengan mudah dan aman.</p>
        </div>
        <span class="inline-flex items-center gap-2 rounded-full {{ $isVerified ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800' }} px-4 py-2 text-xs font-bold">
            <span class="h-2 w-2 rounded-full {{ $isVerified ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
            {{ $statusLabel }}
        </span>
    </div>

    <nav aria-label="Navigasi profil" class="mb-5 overflow-x-auto rounded-2xl border border-sky-100 bg-white p-2 shadow-sm">
        <div class="flex min-w-max gap-2">
            <a href="#informasi-akun" class="rounded-xl bg-sky-50 px-4 py-2.5 text-sm font-bold text-[#126586]">Profil Saya</a>
            <a href="#keamanan-akun" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Keamanan Akun</a>
            @if ($user->isSupplier())
                <a href="{{ route('supplier.payment-settings.edit') }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Data Usaha & Pembayaran</a>
            @elseif ($user->isBuyer())
                <a href="{{ route('transactions.index') }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Pesanan Saya</a>
            @else
                <a href="{{ route('dashboard') }}" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Dashboard</a>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                @csrf
                <button type="submit" class="rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Keluar</button>
            </form>
        </div>
    </nav>

    <main class="min-w-0">
        @if (session('success'))
            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('success') }}</div>
        @endif

            <div class="mb-5 grid gap-4 rounded-2xl border border-sky-100 bg-white p-5 shadow-sm sm:p-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
                <div class="flex min-w-0 items-center gap-4">
                    @if ($user->isSupplier() && $user->supplierProfile?->business_logo_path)
                        <img src="{{ asset('storage/'.$user->supplierProfile->business_logo_path) }}" alt="Logo {{ $user->supplierProfile->business_name }}" class="h-16 w-16 shrink-0 rounded-full border border-sky-100 bg-white object-contain p-1">
                    @else
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#167ba5] to-[#0c5f83] text-2xl font-black text-white">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                    @endif
                    <div class="min-w-0">
                        <h2 class="truncate text-lg font-black text-slate-900">{{ $user->name }}</h2>
                        <span class="mt-1 inline-flex rounded-full bg-sky-100 px-3 py-1 text-xs font-bold text-[#126586]">{{ $roleLabel }}</span>
                        <p class="mt-2 text-xs text-slate-500">ID Akun: SPPGMART-{{ str_pad((string) $user->id, 6, '0', STR_PAD_LEFT) }} · Bergabung {{ $user->created_at->format('d M Y') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-3 divide-x divide-slate-200 rounded-xl bg-[#f7fbfd] py-3 text-center lg:min-w-[390px]">
                    @foreach ($stats as $stat)
                        <div class="px-2">
                            <p class="text-lg font-black text-[#126586] sm:text-xl">{{ $stat['value'] }}</p>
                            <p class="mt-1 text-[11px] font-semibold text-slate-700 sm:text-xs">{{ $stat['label'] }}</p>
                            <p class="mt-1 hidden text-[10px] text-slate-500 sm:block">{{ $stat['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(280px,0.85fr)]">
                <section id="informasi-akun" class="scroll-mt-24 rounded-2xl border border-sky-100 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-black text-slate-900">Informasi Akun</h2>
                            <p class="mt-1 text-xs text-slate-500">Perbarui nama, email, dan nomor telepon akun.</p>
                        </div>
                        <a href="#name" class="shrink-0 rounded-lg bg-sky-50 px-3 py-2 text-xs font-bold text-[#126586] transition hover:bg-sky-100">Edit Profil</a>
                    </div>

                    <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
                        @csrf
                        @method('PUT')
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="name" class="mb-1.5 block text-xs font-semibold text-slate-600">{{ $user->isBuyer() ? 'Nama Perusahaan / Nama Dapur' : 'Nama Lengkap' }}</label>
                                <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="255" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            @if ($user->isBuyer())
                                <div>
                                    <label for="sppg_id" class="mb-1.5 block text-xs font-semibold text-slate-600">ID SPPG</label>
                                    <input id="sppg_id" name="sppg_id" type="text" value="{{ old('sppg_id', $user->sppg_id) }}" maxlength="50" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                    @error('sppg_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            @endif
                            <div>
                                <label for="email" class="mb-1.5 block text-xs font-semibold text-slate-600">Email Terdaftar</label>
                                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" maxlength="255" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label for="phone" class="mb-1.5 block text-xs font-semibold text-slate-600">Nomor Telepon</label>
                                <input id="phone" name="phone" type="tel" value="{{ old('phone', $user->phone) }}" maxlength="30" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                        </div>
                        @if ($user->isBuyer())
                            <div class="border-t border-slate-100 pt-4">
                                <h3 class="text-sm font-bold text-slate-800">Identitas & Alamat SPPG</h3>
                                <p class="mt-1 text-xs text-slate-500">Perbarui penanggung jawab dan alamat utama untuk informasi pengiriman.</p>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label for="address_full_name" class="mb-1.5 block text-xs font-semibold text-slate-600">Penanggung Jawab</label>
                                        <input id="address_full_name" name="address_full_name" type="text" value="{{ old('address_full_name', $primaryAddress?->full_name) }}" maxlength="255" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                        @error('address_full_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="address_phone" class="mb-1.5 block text-xs font-semibold text-slate-600">Telepon Penanggung Jawab</label>
                                        <input id="address_phone" name="address_phone" type="tel" value="{{ old('address_phone', $primaryAddress?->phone) }}" maxlength="30" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                        @error('address_phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="address_street" class="mb-1.5 block text-xs font-semibold text-slate-600">Alamat Jalan</label>
                                        <textarea id="address_street" name="address_street" rows="2" maxlength="2000" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">{{ old('address_street', $primaryAddress?->street) }}</textarea>
                                        @error('address_street')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="address_district" class="mb-1.5 block text-xs font-semibold text-slate-600">Kecamatan</label>
                                        <input id="address_district" name="address_district" type="text" value="{{ old('address_district', $primaryAddress?->district) }}" maxlength="100" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                        @error('address_district')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="address_village" class="mb-1.5 block text-xs font-semibold text-slate-600">Desa/Kelurahan</label>
                                        <input id="address_village" name="address_village" type="text" value="{{ old('address_village', $primaryAddress?->village) }}" maxlength="100" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                        @error('address_village')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="address_city" class="mb-1.5 block text-xs font-semibold text-slate-600">Kota/Kabupaten</label>
                                        <input id="address_city" name="address_city" type="text" value="{{ old('address_city', $primaryAddress?->city) }}" maxlength="100" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                        @error('address_city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="address_province" class="mb-1.5 block text-xs font-semibold text-slate-600">Provinsi</label>
                                        <input id="address_province" name="address_province" type="text" value="{{ old('address_province', $primaryAddress?->province) }}" maxlength="100" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                        @error('address_province')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                    <div>
                                        <label for="address_postal_code" class="mb-1.5 block text-xs font-semibold text-slate-600">Kode Pos</label>
                                        <input id="address_postal_code" name="address_postal_code" type="text" value="{{ old('address_postal_code', $primaryAddress?->postal_code) }}" maxlength="20" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                                        @error('address_postal_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                                @if ($errors->hasAny(['address_full_name', 'address_phone', 'address_province', 'address_city', 'address_district', 'address_village', 'address_postal_code', 'address_street']))
                                    <p class="mt-3 text-xs text-amber-700">Lengkapi semua kolom alamat agar bisa disimpan.</p>
                                @endif
                            </div>
                        @endif
                        <div class="flex justify-end border-t border-slate-100 pt-4">
                            <button type="submit" class="rounded-xl bg-[#167ba5] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#105f82]">Simpan Profil</button>
                        </div>
                    </form>
                </section>

                <section class="rounded-2xl border border-sky-100 bg-white p-5 shadow-sm sm:p-6">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-black text-slate-900">{{ $user->isSupplier() ? 'Informasi Usaha' : ($user->isBuyer() ? 'Informasi SPPG' : 'Informasi Administrator') }}</h2>
                            <p class="mt-1 text-xs text-slate-500">Data identitas yang tersimpan di akun.</p>
                        </div>
                        @if ($user->isSupplier())
                            <a href="{{ route('supplier.payment-settings.edit') }}" class="shrink-0 rounded-lg border border-sky-200 px-3 py-2 text-xs font-bold text-[#126586] hover:bg-sky-50">Kelola</a>
                        @endif
                    </div>
                    <dl class="divide-y divide-slate-100">
                        @foreach ($organizationDetails as $detail)
                            <div class="grid grid-cols-[minmax(100px,0.7fr)_minmax(0,1.3fr)] gap-3 py-3 first:pt-0 last:pb-0">
                                <dt class="text-xs text-slate-500">{{ $detail['label'] }}</dt>
                                <dd class="break-words text-right text-xs font-semibold text-slate-800">{{ $detail['value'] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                    @if ($user->isSupplier() && $user->supplierProfile?->nib_document_path)
                        <a href="{{ route('supplier.nib-document') }}" class="mt-4 inline-flex rounded-lg bg-sky-50 px-3 py-2 text-xs font-bold text-[#126586] transition hover:bg-sky-100">Lihat dokumen NIB</a>
                    @endif
                </section>
            </div>

            <section id="keamanan-akun" class="mt-5 scroll-mt-24 rounded-2xl border border-sky-100 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-5">
                    <h2 class="font-black text-slate-900">Keamanan Akun</h2>
                    <p class="mt-1 text-xs text-slate-500">Gunakan kata sandi yang kuat dan jangan bagikan kepada siapa pun.</p>
                </div>
                <form method="POST" action="{{ route('profile.password.update') }}" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="current_password" class="mb-1.5 block text-xs font-semibold text-slate-600">Kata Sandi Saat Ini</label>
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        @error('current_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="new_password" class="mb-1.5 block text-xs font-semibold text-slate-600">Kata Sandi Baru</label>
                        <input id="new_password" name="new_password" type="password" autocomplete="new-password" minlength="8" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        @error('new_password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="new_password_confirmation" class="mb-1.5 block text-xs font-semibold text-slate-600">Ulangi Kata Sandi Baru</label>
                        <input id="new_password_confirmation" name="new_password_confirmation" type="password" autocomplete="new-password" minlength="8" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                    </div>
                    <div class="flex justify-end border-t border-slate-100 pt-4 sm:col-span-2 xl:col-span-3">
                        <button type="submit" class="rounded-xl border border-sky-200 px-5 py-2.5 text-sm font-bold text-[#126586] transition hover:bg-sky-50">Ubah Kata Sandi</button>
                    </div>
                </form>
            </section>
    </main>
</div>
@endsection
