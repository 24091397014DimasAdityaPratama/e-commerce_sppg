<?php

namespace Tests\Feature;

use App\Models\SupplierProfile;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_api_registration_saves_identity_and_address(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validRegistrationData());

        $response->assertCreated()
            ->assertJsonPath('user.role', 'buyer')
            ->assertJsonStructure(['token']);

        $this->assertDatabaseHas('users', [
            'email' => 'buyer@sppg.test',
            'role' => 'buyer',
            'sppg_id' => 'SPPG-API-001',
        ]);
        $this->assertDatabaseHas('addresses', [
            'full_name' => 'Penanggung Jawab',
            'province' => 'Jawa Barat',
            'city' => 'Kota Bandung',
            'district' => 'Coblong',
            'village' => 'Dago',
            'street' => 'Jl. Merdeka No. 10',
            'is_primary' => true,
        ]);
    }

    public function test_api_registration_requires_a_supported_role(): void
    {
        $data = $this->validRegistrationData();
        unset($data['role']);

        $this->postJson('/api/v1/auth/register', $data)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->postJson('/api/v1/auth/register', [
            ...$this->validRegistrationData(),
            'role' => 'admin',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('role');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_supplier_api_registration_saves_nib_and_private_document(): void
    {
        Storage::fake('local');
        $data = $this->validRegistrationData([
            'name' => 'Pemasok API',
            'email' => 'supplier@sppg.test',
            'role' => 'supplier',
            'nib_number' => '1234567890123',
            'nib_document' => UploadedFile::fake()->create('nib.pdf', 20, 'application/pdf'),
        ]);
        unset($data['sppg_id']);

        $response = $this->postJson('/api/v1/auth/register', $data);

        $response->assertCreated()->assertJsonPath('user.role', 'supplier');

        $profile = SupplierProfile::where('nib_number', '1234567890123')->firstOrFail();
        $this->assertSame('pending', $profile->verification_status);
        Storage::disk('local')->assertExists($profile->nib_document_path);
    }

    public function test_logout_invalidates_existing_api_tokens_and_disabled_users_cannot_login(): void
    {
        $user = User::factory()->create(['role' => 'buyer']);
        $token = app(JwtService::class)->createToken($user);

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
        $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();

        $user->update(['status' => 'suspended']);
        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertUnauthorized();
    }

    private function validRegistrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Dapur API',
            'contact_person' => 'Penanggung Jawab',
            'phone' => '081234567890',
            'email' => 'buyer@sppg.test',
            'address' => 'Jl. Merdeka No. 10',
            'province' => 'Jawa Barat',
            'city' => 'Kota Bandung',
            'district' => 'Coblong',
            'village' => 'Dago',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'buyer',
            'sppg_id' => 'SPPG-API-001',
        ], $overrides);
    }
}
