<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SupplierProductUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_can_upload_a_product_image(): void
    {
        Storage::fake('public');

        $supplier = User::factory()->create(['role' => 'supplier']);
        $category = Category::create([
            'name' => 'Sayur & Buah',
            'slug' => 'sayur-buah',
            'is_active' => true,
        ]);

        $response = $this->actingAs($supplier)->post(route('supplier.products.store'), [
            'category_id' => $category->id,
            'name' => 'Tomat Segar',
            'description' => 'Tomat segar lokal.',
            'unit' => 'kg',
            'unit_quantity' => 5,
            'price' => 12000,
            'stock' => 20,
            'image' => UploadedFile::fake()->image('tomat.jpg'),
        ]);

        $response->assertRedirect(route('dashboard'));

        $product = Product::where('name', 'Tomat Segar')->firstOrFail();
        $this->assertSame('5.000', $product->unit_quantity);
        $this->assertStringStartsWith('/storage/products/', $product->image_url);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $product->image_url));
    }
}
