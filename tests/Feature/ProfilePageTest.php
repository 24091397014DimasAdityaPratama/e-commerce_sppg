<?php

namespace Tests\Feature;

use App\Models\SupplierProfile;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_is_available_to_every_authenticated_role(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));

        foreach (['buyer', 'supplier', 'admin'] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('profile.edit'))
                ->assertOk()
                ->assertSee('Profil Akun')
                ->assertSee('Keamanan Akun')
                ->assertSee('Profil');
        }
    }

    public function test_supplier_profile_shows_store_identity_and_verification_status(): void
    {
        $supplier = User::factory()->create(['role' => 'supplier']);
        SupplierProfile::create([
            'user_id' => $supplier->id,
            'business_name' => 'Sayur Segar Nusantara',
            'business_type' => 'Distributor',
            'city' => 'Bandung',
            'verification_status' => 'approved',
        ]);

        $this->actingAs($supplier)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Sayur Segar Nusantara')
            ->assertSee('Bandung')
            ->assertSee('Terverifikasi');
    }

    public function test_user_can_update_account_information(): void
    {
        $user = User::factory()->create(['role' => 'buyer']);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'SPPG Harapan',
                'email' => 'sppg@example.test',
                'phone' => '08123456789',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'SPPG Harapan',
            'email' => 'sppg@example.test',
            'phone' => '08123456789',
        ]);
    }

    public function test_buyer_can_create_and_update_the_primary_sppg_address_from_profile(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $address = [
            'address_full_name' => 'Budi Santoso',
            'address_phone' => '08123456789',
            'address_province' => 'Jawa Barat',
            'address_city' => 'Bandung',
            'address_district' => 'Cibeunying',
            'address_village' => 'Cibeunying Kaler',
            'address_postal_code' => '40123',
            'address_street' => 'Jl. Merdeka No. 10',
        ];

        $this->actingAs($buyer)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('address_full_name', false)
            ->assertSee('Identitas & Alamat SPPG', false);

        $this->actingAs($buyer)
            ->put(route('profile.update'), array_merge([
                'name' => 'SPPG Harapan',
                'email' => $buyer->email,
            ], $address))
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('addresses', [
            'user_id' => $buyer->id,
            'full_name' => 'Budi Santoso',
            'phone' => '08123456789',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'district' => 'Cibeunying',
            'village' => 'Cibeunying Kaler',
            'postal_code' => '40123',
            'street' => 'Jl. Merdeka No. 10',
            'is_primary' => true,
        ]);
    }

    public function test_supplier_can_update_business_identity_from_payment_settings(): void
    {
        $supplier = User::factory()->create(['role' => 'supplier']);

        $this->actingAs($supplier)
            ->put(route('supplier.payment-settings.update'), [
                'business_name' => 'Sayur Segar Nusantara',
                'business_type' => 'Distributor',
                'province' => 'Jawa Barat',
                'city' => 'Bandung',
                'address' => 'Jl. Pasar Baru No. 12',
            ])
            ->assertRedirect(route('supplier.payment-settings.edit'));

        $this->assertDatabaseHas('supplier_profiles', [
            'user_id' => $supplier->id,
            'business_name' => 'Sayur Segar Nusantara',
            'business_type' => 'Distributor',
            'province' => 'Jawa Barat',
            'city' => 'Bandung',
            'address' => 'Jl. Pasar Baru No. 12',
        ]);
    }

    public function test_changing_supplier_nib_requires_admin_reverification(): void
    {
        Storage::fake('local');
        $supplier = User::factory()->create(['role' => 'supplier']);
        $oldDocumentPath = 'supplier-nib-documents/old.pdf';
        Storage::disk('local')->put($oldDocumentPath, 'old document');
        SupplierProfile::create([
            'user_id' => $supplier->id,
            'business_name' => 'Pemasok Terverifikasi',
            'nib_number' => '1234567890123',
            'nib_document_path' => $oldDocumentPath,
            'verification_status' => 'approved',
        ]);

        $this->actingAs($supplier)
            ->put(route('supplier.payment-settings.update'), [
                'business_name' => 'Pemasok Terverifikasi',
                'nib_number' => '3210987654321',
                'nib_document' => UploadedFile::fake()->create('nib-baru.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('supplier.payment-settings.edit'));

        $profile = SupplierProfile::where('user_id', $supplier->id)->firstOrFail();
        $this->assertSame('pending', $profile->verification_status);
        $this->assertSame('3210987654321', $profile->nib_number);
        Storage::disk('local')->assertMissing($oldDocumentPath);
        Storage::disk('local')->assertExists($profile->nib_document_path);
    }

    public function test_supplier_can_update_account_information_from_profile(): void
    {
        $supplier = User::factory()->create(['role' => 'supplier']);

        $this->actingAs($supplier)
            ->put(route('profile.update'), [
                'name' => 'Andi Supplier',
                'email' => 'andi@supplier.test',
                'phone' => '081111222233',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $supplier->id,
            'name' => 'Andi Supplier',
            'email' => 'andi@supplier.test',
            'phone' => '081111222233',
        ]);
    }

    public function test_user_can_change_password_after_entering_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);
        $token = app(JwtService::class)->createToken($user);

        $this->actingAs($user)
            ->put(route('profile.password.update'), [
                'current_password' => 'old-password',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_password_cannot_be_changed_with_an_incorrect_current_password(): void
    {
        $user = User::factory()->create(['password' => 'old-password']);

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->put(route('profile.password.update'), [
                'current_password' => 'incorrect-password',
                'new_password' => 'new-password',
                'new_password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }
}
