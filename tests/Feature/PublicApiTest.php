<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_endpoints_expose_only_public_supplier_fields(): void
    {
        $category = Category::create([
            'name' => 'Bahan Pokok',
            'slug' => 'bahan-pokok',
            'is_active' => true,
        ]);
        $supplier = User::factory()->create([
            'name' => 'Supplier account',
            'email' => 'supplier-private@sppg.test',
            'role' => 'supplier',
        ]);
        SupplierProfile::create([
            'user_id' => $supplier->id,
            'business_name' => 'Pangan Segar',
            'contact_person' => 'Kontak Privat',
            'nib_number' => '1234567890123',
            'nib_document_path' => 'supplier-nib-documents/private.pdf',
            'bank_account_number' => '1234567890',
            'province' => 'Jawa Barat',
            'city' => 'Kota Bandung',
            'verification_status' => 'approved',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Beras',
            'slug' => 'beras-pangan-segar',
            'unit' => 'kg',
            'price' => 15000,
            'stock' => 25,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.supplier.name', 'Pangan Segar')
            ->assertJsonMissingPath('data.0.supplier.email')
            ->assertJsonMissingPath('data.0.supplier.nib_number')
            ->assertJsonMissingPath('data.0.supplier.bank_account_number');

        $this->getJson('/api/v1/products/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('supplier.name', 'Pangan Segar')
            ->assertJsonMissingPath('supplier.email')
            ->assertJsonMissingPath('supplier.nib_number')
            ->assertJsonMissingPath('supplier.bank_account_number');

        $this->getJson('/api/v1/suppliers')
            ->assertOk()
            ->assertJsonPath('0.name', 'Pangan Segar')
            ->assertJsonMissingPath('0.email')
            ->assertJsonMissingPath('0.nib_number')
            ->assertJsonMissingPath('0.bank_account_number');
    }

    public function test_inactive_products_are_not_available_through_public_api(): void
    {
        $category = Category::create([
            'name' => 'Bahan Pokok',
            'slug' => 'bahan-pokok',
            'is_active' => true,
        ]);
        $supplier = User::factory()->create(['role' => 'supplier']);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Produk Nonaktif',
            'slug' => 'produk-nonaktif',
            'unit' => 'kg',
            'price' => 15000,
            'stock' => 25,
            'is_active' => false,
        ]);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonMissing(['slug' => $product->slug]);

        $this->getJson('/api/v1/products/'.$product->slug)->assertNotFound();
    }

    public function test_unverified_suppliers_are_hidden_until_admin_approval(): void
    {
        Storage::fake('local');
        $documentPath = 'supplier-nib-documents/nib.pdf';
        Storage::disk('local')->put($documentPath, 'private NIB document');
        $category = Category::create([
            'name' => 'Bahan Pokok',
            'slug' => 'bahan-pokok',
            'is_active' => true,
        ]);
        $supplier = User::factory()->create(['role' => 'supplier']);
        SupplierProfile::create([
            'user_id' => $supplier->id,
            'business_name' => 'Pangan Menunggu',
            'nib_number' => '1234567890123',
            'nib_document_path' => $documentPath,
            'verification_status' => 'pending',
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Beras',
            'slug' => 'beras-pending',
            'unit' => 'kg',
            'price' => 15000,
            'stock' => 25,
            'is_active' => true,
        ]);

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonMissing(['slug' => $product->slug]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->patch(route('admin.suppliers.verification.update', $supplier), [
            'verification_status' => 'approved',
        ])->assertRedirect();

        $this->getJson('/api/v1/products')
            ->assertOk()
            ->assertJsonPath('data.0.slug', $product->slug);
    }
}
