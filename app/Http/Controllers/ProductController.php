<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['category', 'supplier.supplierProfile'])
            ->availableInMarketplace()
            ->when($request->category_id, fn ($query, $value) => $query->where('category_id', $value))
            ->when($request->supplier_id, fn ($query, $value) => $query->where('supplier_id', $value))
            ->when($request->search, fn ($query, $value) => $query->where('name', 'like', "%{$value}%"))
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->paginate(12);

        $products->through(fn (Product $product) => $this->publicProduct($product));

        return response()->json($products);
    }

    public function show(Product $product)
    {
        $product->load(['category', 'supplier.supplierProfile']);
        abort_unless($product->isAvailableInMarketplace(), 404);

        return response()->json($this->publicProduct($product));
    }

    public function categories()
    {
        return response()->json(Category::where('is_active', true)->get());
    }

    public function suppliers()
    {
        $suppliers = User::query()
            ->where('role', 'supplier')
            ->where('status', 'active')
            ->whereHas('supplierProfile', fn ($query) => $query->where('verification_status', 'approved'))
            ->with('supplierProfile')
            ->get()
            ->map(function (User $supplier): array {
                $profile = $supplier->supplierProfile;

                return [
                    'id' => $supplier->id,
                    'name' => $profile?->business_name ?? $supplier->name,
                    'business_type' => $profile?->business_type,
                    'province' => $profile?->province,
                    'city' => $profile?->city,
                    'is_verified' => $profile?->verification_status === 'approved',
                    'business_logo_url' => $profile?->business_logo_path
                        ? Storage::disk('public')->url($profile->business_logo_path)
                        : null,
                ];
            });

        return response()->json($suppliers);
    }

    public function store(StoreProductRequest $request)
    {
        $user = $request->user();

        if (! $user->isSupplier() && ! $user->isAdmin()) {
            return response()->json(['message' => 'Hanya supplier yang dapat menambahkan produk.'], 403);
        }

        $product = Product::create([
            'category_id' => $request->category_id,
            'supplier_id' => $user->id,
            'name' => $request->name,
            'slug' => Product::uniqueSlug($request->name),
            'description' => $request->description,
            'unit' => $request->unit,
            'unit_quantity' => $request->input('unit_quantity', 1),
            'price' => $request->price,
            'stock' => $request->stock,
            'image_url' => $request->image_url,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json($product->load(['category', 'supplier']), 201);
    }

    public function myProducts(Request $request)
    {
        $products = Product::with(['category'])
            ->where('supplier_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($products);
    }

    private function publicProduct(Product $product): array
    {
        $profile = $product->supplier?->supplierProfile;

        return [
            'id' => $product->id,
            'category' => $product->category?->only(['id', 'name', 'slug']),
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'unit' => $product->unit,
            'unit_quantity' => $product->unit_quantity,
            'price' => $product->price,
            'stock' => $product->stock,
            'image_url' => $product->image_url,
            'is_featured' => $product->is_featured,
            'supplier' => [
                'id' => $product->supplier?->id,
                'name' => $profile?->business_name ?? $product->supplier?->name,
                'city' => $profile?->city,
                'province' => $profile?->province,
                'is_verified' => $profile?->verification_status === 'approved',
                'business_logo_url' => $profile?->business_logo_path
                    ? Storage::disk('public')->url($profile->business_logo_path)
                    : null,
            ],
        ];
    }
}
