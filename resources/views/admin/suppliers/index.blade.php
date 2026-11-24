@extends('layouts.app')

@section('title', 'Verifikasi Pemasok - SPPG MART')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#167ba5]">Administrator</p>
            <h1 class="mt-1 text-2xl font-black text-slate-900 sm:text-3xl">Verifikasi pemasok</h1>
            <p class="mt-2 text-sm text-slate-600">Periksa identitas usaha dan dokumen NIB sebelum menampilkan toko di marketplace.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="text-sm font-semibold text-[#126586] hover:underline">Kembali ke dashboard</a>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div role="alert" class="mb-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="GET" class="mb-5 flex flex-wrap items-end gap-3">
        <label class="text-sm font-semibold text-slate-700">
            Status verifikasi
            <select name="status" class="mt-1 block rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                @foreach (['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'] as $value => $label)
                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <button class="rounded-xl bg-[#167ba5] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#105f82]">Tampilkan</button>
    </form>

    <div class="space-y-4">
        @forelse ($suppliers as $supplier)
            @php($profile = $supplier->supplierProfile)
            <article class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-black text-slate-900">{{ $profile->business_name }}</h2>
                            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-900">{{ ucfirst($profile->verification_status) }}</span>
                        </div>
                        <dl class="mt-4 grid gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
                            <div><dt class="font-semibold text-slate-500">Akun / email</dt><dd class="break-words text-slate-800">{{ $supplier->name }} · {{ $supplier->email }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Penanggung jawab / WhatsApp</dt><dd class="text-slate-800">{{ $profile->contact_person ?: '—' }} · {{ $supplier->phone ?: '—' }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">NIB</dt><dd class="text-slate-800">{{ $profile->nib_number ?: 'Belum diisi' }}</dd></div>
                            <div><dt class="font-semibold text-slate-500">Alamat usaha</dt><dd class="text-slate-800">{{ collect([$profile->address, $profile->village, $profile->district, $profile->city, $profile->province, $profile->postal_code])->filter()->join(', ') ?: '—' }}</dd></div>
                        </dl>
                        <a href="{{ route('admin.suppliers.nib-document', $supplier) }}" class="mt-4 inline-flex rounded-xl border border-sky-200 px-4 py-2 text-sm font-semibold text-sky-800 hover:bg-sky-50">Unduh dokumen NIB</a>
                    </div>

                    @if ($status !== 'approved')
                        <div class="w-full space-y-3 lg:max-w-sm">
                            <form method="POST" action="{{ route('admin.suppliers.verification.update', $supplier) }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="verification_status" value="approved">
                                <button class="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Setujui pemasok</button>
                            </form>
                            <form method="POST" action="{{ route('admin.suppliers.verification.update', $supplier) }}" class="space-y-2">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="verification_status" value="rejected">
                                <label for="notes-{{ $supplier->id }}" class="block text-xs font-semibold text-slate-600">Alasan penolakan</label>
                                <textarea id="notes-{{ $supplier->id }}" name="notes" rows="2" maxlength="1000" required class="block w-full rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500" placeholder="Jelaskan dokumen atau informasi yang perlu diperbaiki"></textarea>
                                <button class="w-full rounded-xl border border-red-200 px-4 py-2.5 text-sm font-bold text-red-700 hover:bg-red-50">Tolak dan beri catatan</button>
                            </form>
                        </div>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-600">Tidak ada pemasok dengan status ini.</div>
        @endforelse
    </div>

    <div class="mt-6">{{ $suppliers->links() }}</div>
</div>
@endsection
