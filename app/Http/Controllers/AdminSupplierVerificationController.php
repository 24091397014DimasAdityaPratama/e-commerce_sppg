<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AdminSupplierVerificationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
        ]);
        $status = $validated['status'] ?? 'pending';
        $suppliers = User::query()
            ->where('role', 'supplier')
            ->whereHas('supplierProfile', fn ($query) => $query->where('verification_status', $status))
            ->with('supplierProfile')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.suppliers.index', compact('suppliers', 'status'));
    }

    public function update(Request $request, User $supplier)
    {
        abort_unless($supplier->isSupplier(), 404);
        $profile = $supplier->supplierProfile;
        abort_unless($profile, 404);

        $validated = $request->validate([
            'verification_status' => ['required', 'in:approved,rejected'],
            'notes' => ['required_if:verification_status,rejected', 'nullable', 'string', 'max:1000'],
        ]);

        if ($validated['verification_status'] === 'approved'
            && (! preg_match('/^\d{13}$/', (string) $profile->nib_number)
                || ! $profile->nib_document_path
                || ! Storage::disk('local')->exists($profile->nib_document_path))) {
            throw ValidationException::withMessages([
                'verification_status' => 'NIB dan dokumen NIB harus tersedia sebelum pemasok dapat disetujui.',
            ]);
        }

        $profile->update([
            'verification_status' => $validated['verification_status'],
            'notes' => $validated['verification_status'] === 'rejected' ? $validated['notes'] : null,
        ]);

        return redirect()->route('admin.suppliers.index', ['status' => $validated['verification_status']])
            ->with('success', 'Status verifikasi pemasok berhasil diperbarui.');
    }

    public function nibDocument(User $supplier)
    {
        abort_unless($supplier->isSupplier(), 404);
        $path = $supplier->supplierProfile?->nib_document_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download(
            $path,
            'dokumen-nib-pemasok-'.$supplier->id.'.'.pathinfo($path, PATHINFO_EXTENSION),
            ['X-Content-Type-Options' => 'nosniff']
        );
    }
}
