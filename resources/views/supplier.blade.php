@extends('layouts.app')

@section('title', 'Profil Pemasok - SPPG MART')

@section('content')
<div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div>
                <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">Profil Pemasok</p>
                <h1 class="mt-3 text-4xl font-black text-slate-900">{{ $supplier->name }}</h1>
            </div>
            <div class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700">{{ $supplier->supplierProfile->verification_status ?? 'Approved' }}</div>
        </div>

        @auth
            @if (auth()->user()->isBuyer())
                <form method="POST" action="{{ route('chat.suppliers.start', $supplier) }}" class="mt-6">
                    @csrf
                    <button type="submit" class="rounded-full bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">Chat dengan supplier</button>
                </form>
            @endif
        @endauth

        <div class="mt-8 grid gap-6 md:grid-cols-3">
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm text-slate-500">Nama usaha</div>
                <div class="mt-2 text-xl font-bold text-slate-900">{{ $supplier->supplierProfile->business_name ?? 'Usaha Terkemuka' }}</div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm text-slate-500">Kota</div>
                <div class="mt-2 text-xl font-bold text-slate-900">{{ $supplier->supplierProfile->city ?? 'Bandung' }}</div>
            </div>
            <div class="rounded-2xl bg-slate-50 p-5">
                <div class="text-sm text-slate-500">Status</div>
                <div class="mt-2 text-xl font-bold text-slate-900">{{ $supplier->status ?? 'Aktif' }}</div>
            </div>
        </div>
    </div>

    <div class="mt-12">
        <h2 class="text-2xl font-black text-slate-900">Produk dari {{ $supplier->name }}</h2>
        <div class="mt-6 grid gap-6 md:grid-cols-3">
            @foreach ($supplier->products as $product)
                <article class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-xl font-bold text-slate-900">{{ $product->name }}</h3>
                    <x-product-rating :product="$product" />
                    <p class="mt-2 text-sm text-slate-600">{{ Str::limit($product->description, 100) }}</p>
                    <div class="mt-4 flex items-center justify-between">
                        <div class="text-lg font-black text-emerald-700">Rp {{ number_format($product->price, 0, ',', '.') }}</div>
                        <a href="{{ route('products.show', $product) }}" class="text-sm font-semibold text-emerald-700">Detail</a>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>
@endsection
