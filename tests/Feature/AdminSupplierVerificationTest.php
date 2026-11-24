<?php

namespace Tests\Feature;

use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSupplierVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_a_supplier_before_their_store_is_public(): void
    {
        Storage::fake('local');
        $documentPath = 'supplier-nib-documents/nib.pdf';
        Storage::disk('local')->put($documentPath, 'private NIB document');
        $supplier = User::factory()->create(['role' => 'supplier']);
        SupplierProfile::create([
            'user_id' => $supplier->id,
            'business_name' => 'Pangan Segar',
            'nib_number' => '1234567890123',
            'nib_document_path' => $documentPath,
            'verification_status' => 'pending',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.suppliers.index'))
            ->assertOk()
            ->assertSee('Pangan Segar')
            ->assertSee('Unduh dokumen NIB');

        $this->actingAs($supplier)
            ->get(route('admin.suppliers.index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(route('admin.suppliers.nib-document', $supplier))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->patch(route('admin.suppliers.verification.update', $supplier), [
            'verification_status' => 'approved',
        ])->assertRedirect(route('admin.suppliers.index', ['status' => 'approved']));

        $this->assertDatabaseHas('supplier_profiles', [
            'user_id' => $supplier->id,
            'verification_status' => 'approved',
            'notes' => null,
        ]);
    }

    public function test_admin_must_provide_a_reason_when_rejecting_a_supplier(): void
    {
        $supplier = User::factory()->create(['role' => 'supplier']);
        SupplierProfile::create([
            'user_id' => $supplier->id,
            'business_name' => 'Pangan Segar',
            'verification_status' => 'pending',
        ]);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('admin.suppliers.verification.update', $supplier), [
                'verification_status' => 'rejected',
            ])
            ->assertSessionHasErrors('notes');

        $this->assertDatabaseHas('supplier_profiles', [
            'user_id' => $supplier->id,
            'verification_status' => 'pending',
        ]);
    }
}
