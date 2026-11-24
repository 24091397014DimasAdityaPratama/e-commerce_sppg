<?php

namespace Tests\Feature;

use App\Models\SupplierProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_page_uses_sppg_label_and_compact_role_controls(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Daftar sebagai')
            ->assertSee('SPPG')
            ->assertSee('Pemasok')
            ->assertSee('type="radio"', false)
            ->assertSee('data-region-base=', false)
            ->assertSee('ID SPPG')
            ->assertSee('Upload Dokumen NIB')
            ->assertSee('Kode CAPTCHA')
            ->assertSee('data-initial-step="1"', false)
            ->assertSee('data-registration-step="5"', false)
            ->assertSee('id="registration-next"', false)
            ->assertDontSee('SPPG / Pembeli');
    }

    public function test_sppg_registration_keeps_the_buyer_role_internally(): void
    {
        $this->get(route('register'));

        $this->post(route('register'), [
            ...$this->validRegistrationData([
                'name' => 'SPPG Harapan',
                'email' => 'harapan@sppg.test',
                'role' => 'buyer',
                'sppg_id' => 'SPPG-2026-001',
                'captcha_answer' => session('web_captcha_answer'),
            ]),
        ])->assertRedirect(route('home'));

        $this->assertDatabaseHas('users', [
            'email' => 'harapan@sppg.test',
            'role' => 'buyer',
            'sppg_id' => 'SPPG-2026-001',
        ]);
        $this->assertDatabaseHas('addresses', [
            'full_name' => 'Rina Penanggung Jawab',
            'phone' => '081234567890',
            'province' => 'Jawa Barat',
            'city' => 'Kota Bandung',
            'district' => 'Coblong',
            'village' => 'Dago',
            'street' => 'Jl. Merdeka No. 10',
        ]);
        $this->assertAuthenticated();
        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('SPPG-2026-001')
            ->assertSee('Rina Penanggung Jawab')
            ->assertSee('Jawa Barat');
    }

    public function test_supplier_registration_stores_nib_and_private_document(): void
    {
        Storage::fake('local');
        $this->get(route('register'));
        $data = $this->validRegistrationData([
            'name' => 'Pemasok Segar',
            'email' => 'pemasok@sppg.test',
            'role' => 'supplier',
            'nib_number' => '1234567890123',
            'captcha_answer' => session('web_captcha_answer'),
            'nib_document' => UploadedFile::fake()->create('nib.pdf', 20, 'application/pdf'),
        ]);

        $this->post(route('register'), [
            ...$data,
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'pemasok@sppg.test',
            'role' => 'supplier',
        ]);
        $this->assertDatabaseHas('supplier_profiles', [
            'business_name' => 'Pemasok Segar',
            'contact_person' => 'Rina Penanggung Jawab',
            'nib_number' => '1234567890123',
            'province' => 'Jawa Barat',
            'city' => 'Kota Bandung',
            'district' => 'Coblong',
            'village' => 'Dago',
            'address' => 'Jl. Merdeka No. 10',
            'verification_status' => 'pending',
        ]);

        $supplierProfile = SupplierProfile::where('nib_number', '1234567890123')->firstOrFail();
        Storage::disk('local')->assertExists($supplierProfile->nib_document_path);

        $this->get(route('supplier.nib-document'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('1234567890123')
            ->assertSee('Rina Penanggung Jawab')
            ->assertSee('Lihat dokumen NIB');
    }

    public function test_registration_rejects_an_incorrect_captcha_answer(): void
    {
        $this->get(route('register'));
        $wrongAnswer = session('web_captcha_answer') + 1;

        $this->from(route('register'))
            ->post(route('register'), $this->validRegistrationData([
                'captcha_answer' => $wrongAnswer,
            ]))
            ->assertRedirect(route('register'))
            ->assertSessionHasErrors('captcha_answer');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('data-initial-step="5"', false);

        $this->assertDatabaseMissing('users', ['email' => 'registrant@sppg.test']);
    }

    private function validRegistrationData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Nama Perusahaan',
            'contact_person' => 'Rina Penanggung Jawab',
            'phone' => '081234567890',
            'email' => 'registrant@sppg.test',
            'address' => 'Jl. Merdeka No. 10',
            'province' => 'Jawa Barat',
            'city' => 'Kota Bandung',
            'district' => 'Coblong',
            'village' => 'Dago',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'buyer',
            'sppg_id' => 'SPPG-2026-001',
            'captcha_answer' => 10,
        ], $overrides);
    }
}
