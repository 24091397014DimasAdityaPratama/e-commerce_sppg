@extends('layouts.app')

@section('title', 'Pengaturan Pembayaran - SPPG MART')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="mb-8 mt-6">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">Supplier</p>
        <h1 class="mt-3 text-4xl font-black text-slate-900">Profil Toko & Pembayaran</h1>
        <p class="mt-3 text-slate-600">Atur identitas toko, logo, dan rekening transfer. Logo toko akan digunakan pada nota dan kwitansi; QRIS dinamis dibuat melalui Midtrans untuk setiap pesanan.</p>
    </div>
    @include('supplier.partials.navigation', ['active' => 'settings'])

    @if (session('success'))
        <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('supplier.payment-settings.update') }}" enctype="multipart/form-data" class="space-y-8 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @csrf
        @method('PUT')

        <section>
            <h2 class="text-xl font-bold text-slate-900">Identitas toko</h2>
            <div class="mt-5 grid gap-5 md:grid-cols-2">
                <div>
                    <label for="business_name" class="mb-2 block text-sm font-semibold text-slate-700">Nama Toko</label>
                    <input type="text" name="business_name" id="business_name" value="{{ old('business_name', $profile?->business_name ?? auth()->user()->name) }}" maxlength="255" required class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                    @error('business_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="business_type" class="mb-2 block text-sm font-semibold text-slate-700">Jenis Usaha</label>
                    <input type="text" name="business_type" id="business_type" value="{{ old('business_type', $profile?->business_type) }}" maxlength="100" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none" placeholder="Contoh: Distributor bahan pangan">
                    @error('business_type')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="contact_person" class="mb-2 block text-sm font-semibold text-slate-700">Nama Penanggung Jawab</label>
                    <input type="text" name="contact_person" id="contact_person" value="{{ old('contact_person', $profile?->contact_person) }}" maxlength="255" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                    @error('contact_person')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="province" class="mb-2 block text-sm font-semibold text-slate-700">Provinsi</label>
                    <input type="text" name="province" id="province" value="{{ old('province', $profile?->province) }}" maxlength="100" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                    @error('province')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="city" class="mb-2 block text-sm font-semibold text-slate-700">Kota/Kabupaten</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $profile?->city) }}" maxlength="100" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                    @error('city')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="district" class="mb-2 block text-sm font-semibold text-slate-700">Kecamatan</label>
                    <input type="text" name="district" id="district" value="{{ old('district', $profile?->district) }}" maxlength="100" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                    @error('district')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="village" class="mb-2 block text-sm font-semibold text-slate-700">Desa/Kelurahan</label>
                    <input type="text" name="village" id="village" value="{{ old('village', $profile?->village) }}" maxlength="100" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                    @error('village')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="postal_code" class="mb-2 block text-sm font-semibold text-slate-700">Kode Pos</label>
                    <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $profile?->postal_code) }}" maxlength="20" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                    @error('postal_code')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="address" class="mb-2 block text-sm font-semibold text-slate-700">Alamat Lengkap</label>
                    <textarea name="address" id="address" rows="2" maxlength="255" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">{{ old('address', $profile?->address) }}</textarea>
                    @error('address')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nib_number" class="mb-2 block text-sm font-semibold text-slate-700">Nomor Induk Berusaha (NIB)</label>
                    <input type="text" name="nib_number" id="nib_number" value="{{ old('nib_number', $profile?->nib_number) }}" inputmode="numeric" pattern="[0-9]{13}" maxlength="13" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                    <p class="mt-2 text-xs text-slate-500">Masukkan 13 digit nomor NIB.</p>
                    @error('nib_number')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="nib_document" class="mb-2 block text-sm font-semibold text-slate-700">Dokumen NIB</label>
                    <input type="file" name="nib_document" id="nib_document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:font-semibold file:text-emerald-700">
                    <p class="mt-2 text-xs text-slate-500">PDF, JPG, atau PNG; maksimal 10 MB. Dokumen hanya dapat diakses akun supplier.</p>
                    @if ($profile?->nib_document_path)
                        <a href="{{ route('supplier.nib-document') }}" class="mt-2 inline-flex text-sm font-semibold text-emerald-700 hover:underline">Lihat dokumen NIB tersimpan</a>
                    @endif
                    @error('nib_document')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="business_logo" class="mb-2 block text-sm font-semibold text-slate-700">Logo Toko</label>
                    <input type="file" name="business_logo" id="business_logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:font-semibold file:text-emerald-700">
                    <p class="mt-2 text-xs text-slate-500">JPG, PNG, atau WEBP; maksimal 2 MB. Logo tampil di nota PDF toko.</p>
                    @error('business_logo')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                @if ($profile?->business_logo_path)
                    <div class="md:col-span-2">
                        <p class="mb-2 text-sm font-semibold text-slate-700">Logo saat ini</p>
                        <img src="{{ asset('storage/'.$profile->business_logo_path) }}" alt="Logo {{ $profile->business_name }}" class="h-24 max-w-48 rounded-xl border border-slate-200 bg-white object-contain p-2">
                        <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" name="remove_business_logo" value="1" class="rounded border-slate-300 text-emerald-600">
                            Hapus logo toko saat disimpan
                        </label>
                    </div>
                @endif
            </div>
        </section>

        <section>
            <h2 class="text-xl font-bold text-slate-900">Transfer bank</h2>
            <p class="mt-1 text-sm text-slate-500">Isi semua informasi rekening agar opsi transfer dapat digunakan pembeli.</p>
            <div class="mt-5 grid gap-5 md:grid-cols-3">
                <div>
                    <label for="bank_name" class="mb-2 block text-sm font-semibold text-slate-700">Nama Bank</label>
                    <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name', $profile?->bank_name) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none" placeholder="Contoh: BCA">
                    @error('bank_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="bank_account_name" class="mb-2 block text-sm font-semibold text-slate-700">Nama Pemilik Rekening</label>
                    <input type="text" name="bank_account_name" id="bank_account_name" value="{{ old('bank_account_name', $profile?->bank_account_name) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                    @error('bank_account_name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="bank_account_number" class="mb-2 block text-sm font-semibold text-slate-700">Nomor Rekening</label>
                    <input type="text" name="bank_account_number" id="bank_account_number" value="{{ old('bank_account_number', $profile?->bank_account_number) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none">
                    @error('bank_account_number')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-3 border-t border-slate-200 pt-6">
            <a href="{{ route('dashboard') }}" class="rounded-full border border-slate-300 px-5 py-3 font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
            <button type="submit" class="rounded-full bg-emerald-600 px-6 py-3 font-semibold text-white hover:bg-emerald-700">Simpan Informasi</button>
        </div>
    </form>
</div>
@endsection
