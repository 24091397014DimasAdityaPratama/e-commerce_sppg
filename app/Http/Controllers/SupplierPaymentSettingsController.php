<?php

namespace App\Http\Controllers;

use App\Models\SupplierProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SupplierPaymentSettingsController extends Controller
{
    public function edit(Request $request)
    {
        abort_unless($request->user()->isSupplier(), 403);

        $profile = $request->user()->supplierProfile;

        return view('supplier.payment-settings', compact('profile'));
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->isSupplier(), 403);

        $validated = $request->validate([
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_name' => ['nullable', 'string', 'max:150'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'business_name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'village' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:255'],
            'nib_number' => ['nullable', 'digits:13', Rule::unique('supplier_profiles', 'nib_number')->ignore($request->user()->supplierProfile?->id)],
            'nib_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'business_logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_business_logo' => ['nullable', 'boolean'],
        ]);

        $profile = SupplierProfile::firstOrNew(['user_id' => $request->user()->id]);
        $oldLogoPath = $profile->business_logo_path;
        $newLogoPath = null;
        $oldNibDocumentPath = $profile->nib_document_path;
        $newNibDocumentPath = null;
        $nibNumberChanged = $profile->exists
            && array_key_exists('nib_number', $validated)
            && $profile->nib_number !== ($validated['nib_number'] ?? null);

        if ($request->hasFile('business_logo')) {
            $newLogoPath = $request->file('business_logo')->storePublicly('supplier-logos', 'public');

            if ($newLogoPath === false) {
                throw new \RuntimeException('Logo toko gagal disimpan.');
            }
        }

        if ($request->hasFile('nib_document')) {
            $newNibDocumentPath = $request->file('nib_document')->store('supplier-nib-documents', 'local');

            if ($newNibDocumentPath === false) {
                throw new \RuntimeException('Dokumen NIB gagal disimpan.');
            }
        }

        if (! $profile->exists) {
            $profile->business_name = $request->user()->name;
            $profile->verification_status = 'pending';
        }

        $profile->fill([
            'business_name' => $validated['business_name'],
            'business_type' => $validated['business_type'] ?? null,
            'contact_person' => $validated['contact_person'] ?? null,
            'province' => $validated['province'] ?? null,
            'city' => $validated['city'] ?? null,
            'district' => $validated['district'] ?? null,
            'village' => $validated['village'] ?? null,
            'postal_code' => $validated['postal_code'] ?? null,
            'address' => $validated['address'] ?? null,
            'nib_number' => $validated['nib_number'] ?? null,
            'bank_name' => $validated['bank_name'] ?? null,
            'bank_account_name' => $validated['bank_account_name'] ?? null,
            'bank_account_number' => $validated['bank_account_number'] ?? null,
        ]);

        if ($newLogoPath !== null) {
            $profile->business_logo_path = $newLogoPath;
        } elseif ($request->boolean('remove_business_logo')) {
            $profile->business_logo_path = null;
        }

        if ($newNibDocumentPath !== null) {
            $profile->nib_document_path = $newNibDocumentPath;
        }

        if ($nibNumberChanged || $newNibDocumentPath !== null) {
            $profile->verification_status = 'pending';
        }

        $profile->save();

        if ($oldLogoPath && ($newLogoPath !== null || $request->boolean('remove_business_logo'))) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        if ($oldNibDocumentPath && $newNibDocumentPath !== null) {
            Storage::disk('local')->delete($oldNibDocumentPath);
        }

        return redirect()->route('supplier.payment-settings.edit')->with('success', 'Informasi pembayaran berhasil disimpan.');
    }

    public function downloadNibDocument(Request $request)
    {
        abort_unless($request->user()->isSupplier(), 403);

        $path = $request->user()->supplierProfile?->nib_document_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download(
            $path,
            'dokumen-nib.'.pathinfo($path, PATHINFO_EXTENSION),
            ['X-Content-Type-Options' => 'nosniff']
        );
    }
}
