<?php

namespace Database\Seeders;

use App\Models\Category;
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

    }
}
