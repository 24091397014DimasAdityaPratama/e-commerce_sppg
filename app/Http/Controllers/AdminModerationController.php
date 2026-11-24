<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminModerationController extends Controller
{
    public function products(Request $request)
    {
        $validated = $request->validate([
            'visibility' => ['nullable', 'in:all,active,hidden'],
        ]);
        $visibility = $validated['visibility'] ?? 'all';
        $products = Product::query()
            ->with(['supplier', 'category'])
            ->withAvg('visibleReviews', 'rating')
            ->withCount('visibleReviews')
            ->when($visibility === 'active', fn ($query) => $query->where('is_active', true))
            ->when($visibility === 'hidden', fn ($query) => $query->where('is_active', false))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products', 'visibility'));
    }

    public function updateProductVisibility(Request $request, Product $product)
    {
        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);
        $product->update(['is_active' => $validated['is_active']]);

        return back()->with('success', $validated['is_active']
            ? 'Produk kembali ditampilkan di katalog.'
            : 'Produk disembunyikan dari katalog.');
    }

    public function destroyProduct(Product $product)
    {
        [$deleted, $imageUrl] = DB::transaction(function () use ($product): array {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            $hasHistory = OrderItem::where('product_id', $lockedProduct->id)->exists()
                || StockMovement::where('product_id', $lockedProduct->id)->exists()
                || $lockedProduct->reviews()->exists();

            if ($hasHistory) {
                $lockedProduct->update(['is_active' => false]);

                return [false, null];
            }

            $imageUrl = $lockedProduct->image_url;
            $lockedProduct->delete();

            return [true, $imageUrl];
        });

        if ($imageUrl && str_starts_with($imageUrl, '/storage/products/')) {
            if (! Storage::disk('public')->delete(str_replace('/storage/', '', $imageUrl))) {
                throw new \RuntimeException('Gambar produk gagal dihapus dari penyimpanan.');
            }
        }

        return back()->with('success', $deleted
            ? 'Produk dihapus.'
            : 'Produk memiliki riwayat transaksi atau ulasan, jadi disembunyikan agar data pesanan tetap utuh.');
    }

    public function users(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:all,active,blocked'],
        ]);
        $status = $validated['status'] ?? 'all';
        $users = User::query()
            ->whereIn('role', ['buyer', 'supplier'])
            ->with('supplierProfile')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'status'));
    }

    public function updateUserStatus(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 403, 'Administrator tidak dapat menonaktifkan akunnya sendiri.');
        abort_unless(in_array($user->role, ['buyer', 'supplier'], true), 404);

        $validated = $request->validate([
            'status' => ['required', 'in:active,blocked'],
        ]);

        if ($user->status !== $validated['status']) {
            $user->update(['status' => $validated['status']]);
            $user->increment('api_token_version');
        }

        return back()->with('success', $validated['status'] === 'blocked'
            ? 'Akun diblokir dan token API lama dicabut.'
            : 'Akun diaktifkan kembali.');
    }
}
