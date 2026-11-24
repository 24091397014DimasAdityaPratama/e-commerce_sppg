@extends('layouts.app')

@section('title', ($product ? 'Edit Produk' : 'Tambah Produk').' - SPPG MART')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
    <div class="mb-8">
        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-emerald-600">Supplier</p>
        <h1 class="mt-3 text-4xl font-black text-slate-900">{{ $product ? 'Edit Produk' : 'Tambah Produk Baru' }}</h1>
    </div>
    @include('supplier.partials.navigation', ['active' => 'inventory'])

    @if (session('error'))
        <div class="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        <form method="POST" action="{{ $product ? route('supplier.products.update', $product) : route('supplier.products.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @if ($product)
                @method('PUT')
            @endif

            <div>
                <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nama Produk <span class="text-red-600">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $product?->name) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none" placeholder="Contoh: Beras Organik Premium" autocomplete="off" required>
                @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="category_id" class="mb-2 block text-sm font-semibold text-slate-700">Kategori <span class="text-red-600">*</span></label>
                    <select name="category_id" id="category_id" class="w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-emerald-500 focus:outline-none" required>
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('category_id', $product?->category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="unit_quantity" class="mb-2 block text-sm font-semibold text-slate-700">Isi per Produk <span class="text-red-600">*</span></label>
                    <div class="flex gap-3">
                        <input type="number" name="unit_quantity" id="unit_quantity" min="0.001" step="0.001" value="{{ old('unit_quantity', $product?->unit_quantity ?? 1) }}" class="min-w-0 flex-1 rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none" placeholder="Contoh: 500" required>
                        <select name="unit" id="unit" class="rounded-2xl border border-slate-300 bg-white px-4 py-3 text-slate-900 focus:border-emerald-500 focus:outline-none" required>
                            <option value="liter" @selected(old('unit', $product?->unit) === 'liter')>Liter</option>
                            <option value="kg" @selected(old('unit', $product?->unit ?? 'kg') === 'kg')>Kg</option>
                            <option value="pcs" @selected(old('unit', $product?->unit) === 'pcs')>Pcs</option>
                            <option value="gram" @selected(old('unit', $product?->unit) === 'gram')>Gram</option>
                        </select>
                    </div>
                    <p class="mt-2 text-xs text-slate-500">Contoh: 500 gram atau 1 liter. Stok diisi terpisah sebagai jumlah produk.</p>
                    @error('unit')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('unit_quantity')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="description" class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi</label>
                <textarea name="description" id="description" rows="4" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none" placeholder="Jelaskan detail produk...">{{ old('description', $product?->description) }}</textarea>
                @error('description')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-6 md:grid-cols-3">
                <div>
                    <label for="price" class="mb-2 block text-sm font-semibold text-slate-700">Harga</label>
                    <input type="number" min="0" step="100" name="price" id="price" value="{{ old('price', $product?->price) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                    @error('price')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($product)
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <p class="text-sm font-semibold text-slate-700">Stok saat ini</p>
                        <p class="mt-1 text-lg font-black text-slate-900">{{ $product->stock }} unit</p>
                        <a href="{{ route('supplier.inventory') }}" class="mt-1 inline-block text-xs font-semibold text-emerald-700 hover:underline">Kelola stok dan riwayatnya →</a>
                    </div>
                @else
                    <div>
                    <label for="stock" class="mb-2 block text-sm font-semibold text-slate-700">Stok</label>
                    <input type="number" min="0" name="stock" id="stock" value="{{ old('stock') }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                    @error('stock')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    </div>
                @endif
                <div>
                    <label for="low_stock_threshold" class="mb-2 block text-sm font-semibold text-slate-700">Peringatan Stok Menipis</label>
                    <input type="number" min="0" name="low_stock_threshold" id="low_stock_threshold" value="{{ old('low_stock_threshold', $product?->low_stock_threshold ?? 10) }}" class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-emerald-500 focus:outline-none" required>
                    <p class="mt-1 text-xs text-slate-500">Peringatan muncul saat stok mencapai angka ini.</p>
                    @error('low_stock_threshold')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="image" class="mb-2 block text-sm font-semibold text-slate-700">Upload Gambar Produk</label>
                <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="w-full rounded-2xl border border-slate-300 px-4 py-3 text-sm text-slate-700 file:mr-4 file:rounded-full file:border-0 file:bg-emerald-50 file:px-4 file:py-2 file:font-semibold file:text-emerald-700">
                <p class="mt-2 text-xs text-slate-500">{{ $product ? 'Kosongkan jika gambar tidak ingin diganti.' : '' }} Format JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                @if ($product?->image_url)
                    <img src="{{ $product->image_url }}" alt="Gambar {{ $product->name }}" class="mt-3 h-24 w-24 rounded-xl border border-slate-200 object-cover">
                @endif
                @error('image')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $product?->is_featured ?? false)) class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    Jadikan produk unggulan
                </label>

                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product?->is_active ?? true)) class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    Produk aktif dan tampil di katalog
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('supplier.inventory') }}" class="rounded-full border border-slate-300 px-5 py-3 font-semibold text-slate-700 transition hover:bg-slate-100">Batal</a>
                <button type="submit" class="rounded-full bg-emerald-600 px-6 py-3 font-semibold text-white transition hover:bg-emerald-700">{{ $product ? 'Simpan Perubahan' : 'Simpan Produk' }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
