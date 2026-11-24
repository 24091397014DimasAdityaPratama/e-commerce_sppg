<nav aria-label="Menu pengelolaan toko" class="mb-8 -mx-4 overflow-x-auto border-y border-slate-200 bg-white px-4 sm:mx-0 sm:rounded-2xl sm:border">
    <div class="flex min-w-max gap-2 py-2">
        <a href="{{ route('dashboard') }}" @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-emerald-600 text-white' => $active === 'overview', 'text-slate-600 hover:bg-slate-100' => $active !== 'overview'])>Ringkasan</a>
        <a href="{{ route('supplier.inventory') }}" @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-emerald-600 text-white' => $active === 'inventory', 'text-slate-600 hover:bg-slate-100' => $active !== 'inventory'])>Produk & Stok</a>
        <a href="{{ route('transactions.index') }}" @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-emerald-600 text-white' => $active === 'orders', 'text-slate-600 hover:bg-slate-100' => $active !== 'orders'])>Pesanan</a>
        <a href="{{ route('supplier.distribution') }}" @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-emerald-600 text-white' => $active === 'distribution', 'text-slate-600 hover:bg-slate-100' => $active !== 'distribution'])>Distribusi</a>
        <a href="{{ route('supplier.finance') }}" @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-emerald-600 text-white' => $active === 'finance', 'text-slate-600 hover:bg-slate-100' => $active !== 'finance'])>Arus Kas</a>
        <a href="{{ route('supplier.payment-settings.edit') }}" @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-emerald-600 text-white' => $active === 'settings', 'text-slate-600 hover:bg-slate-100' => $active !== 'settings'])>Profil & Pembayaran</a>
    </div>
</nav>
