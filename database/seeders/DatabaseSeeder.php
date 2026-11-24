<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Category;
use App\Models\Product;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $categories = [
            'Beras & Serealia',
            'Sayur & Buah',
            'Protein Nabati',
            'Protein Hewani',
            'Bumbu & Rempah',
            'Minuman',
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => Str::slug($category)],
                [
                    'name' => $category,
                    'description' => 'Kategori kebutuhan pangan untuk kebutuhan operasional SPPG.',
                    'is_active' => true,
                ]
            );
        }

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@sppgmart.test'],
            [
                'name' => 'Admin SPPG MART',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        $buyer = User::firstOrCreate(
            ['email' => 'buyer@sppgmart.test'],
            [
                'name' => 'SPPG Makan Sehat',
                'password' => Hash::make('password123'),
                'role' => 'buyer',
                'status' => 'active',
            ]
        );

        $supplier = User::firstOrCreate(
            ['email' => 'supplier@sppgmart.test'],
            [
                'name' => 'Tani Maju Mandiri',
                'password' => Hash::make('password123'),
                'role' => 'supplier',
                'status' => 'active',
            ]
        );

        SupplierProfile::updateOrCreate(
            ['user_id' => $supplier->id],
            [
                'business_name' => 'Tani Maju Mandiri',
                'business_type' => 'UMKM',
                'province' => 'Jawa Barat',
                'city' => 'Bandung',
                'address' => 'Jl. Raya Ciburial No. 24',
                'verification_status' => 'approved',
            ]
        );

        Address::firstOrCreate(
            [
                'user_id' => $buyer->id,
                'full_name' => 'SPPG Makan Sehat',
            ],
            [
                'type' => 'home',
                'phone' => '081234567890',
                'province' => 'Jawa Barat',
                'city' => 'Bandung',
                'district' => 'Cibeunying',
                'postal_code' => '40123',
                'street' => 'Jl. Merdeka No. 10',
                'is_primary' => true,
            ]
        );

        $productCatalog = [
            ['name' => 'Beras Organik Premium', 'category' => 'Beras & Serealia', 'unit' => 'kg', 'price' => 14000, 'stock' => 120, 'featured' => true],
            ['name' => 'Jagung Pipilan Kering', 'category' => 'Beras & Serealia', 'unit' => 'kg', 'price' => 9000, 'stock' => 80, 'featured' => false],
            ['name' => 'Tomat Segar', 'category' => 'Sayur & Buah', 'unit' => 'kg', 'price' => 11000, 'stock' => 75, 'featured' => true],
            ['name' => 'Kentang Lokal', 'category' => 'Sayur & Buah', 'unit' => 'kg', 'price' => 8500, 'stock' => 65, 'featured' => false],
            ['name' => 'Kedelai Hitam', 'category' => 'Protein Nabati', 'unit' => 'kg', 'price' => 15000, 'stock' => 55, 'featured' => true],
            ['name' => 'Tahu Putih', 'category' => 'Protein Nabati', 'unit' => 'pcs', 'price' => 3500, 'stock' => 140, 'featured' => false],
            ['name' => 'Ayam Kampung Segar', 'category' => 'Protein Hewani', 'unit' => 'kg', 'price' => 42000, 'stock' => 42, 'featured' => true],
            ['name' => 'Cabai Merah', 'category' => 'Bumbu & Rempah', 'unit' => 'kg', 'price' => 28000, 'stock' => 32, 'featured' => true],
        ];

        foreach ($productCatalog as $productData) {
            $category = Category::where('name', $productData['category'])->first();

            Product::firstOrCreate(
                ['slug' => Str::slug($productData['name'])],
                [
                    'category_id' => $category->id,
                    'supplier_id' => $supplier->id,
                    'name' => $productData['name'],
                    'description' => 'Produk berkualitas untuk kebutuhan SPPG dan distribusi pangan harian.',
                    'unit' => $productData['unit'],
                    'price' => $productData['price'],
                    'stock' => $productData['stock'],
                    'image_url' => 'https://images.unsplash.com/photo-1542838132-92c53300491e?auto=format&fit=crop&w=900&q=80',
                    'is_featured' => $productData['featured'],
                    'is_active' => true,
                ]
            );
        }

        User::factory(6)->create();
    }
}
