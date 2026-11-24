<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_product_name_from_different_suppliers_gets_unique_slugs(): void
    {
        $category = Category::create([
            'name' => 'Bahan Pokok',
            'slug' => 'bahan-pokok',
            'is_active' => true,
        ]);
        $productData = [
            'category_id' => $category->id,
            'name' => 'Beras',
            'unit' => 'kg',
            'unit_quantity' => 1,
            'price' => 12000,
            'stock' => 10,
        ];
        $firstSupplier = User::factory()->create(['role' => 'supplier']);
        $secondSupplier = User::factory()->create(['role' => 'supplier']);

        $firstResponse = $this->withToken(app(JwtService::class)->createToken($firstSupplier))
            ->postJson('/api/v1/products', $productData);
        $secondResponse = $this->withToken(app(JwtService::class)->createToken($secondSupplier))
            ->postJson('/api/v1/products', $productData);

        $firstResponse->assertCreated()->assertJsonPath('slug', 'beras');
        $secondResponse->assertCreated()->assertJsonPath('slug', 'beras-1');
        $this->assertSame(2, Product::count());
    }
}
