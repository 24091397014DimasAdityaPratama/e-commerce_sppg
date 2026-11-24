<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user()->load('supplierProfile');
        $primaryAddress = $user->addresses()->where('is_primary', true)->first()
            ?? $user->addresses()->latest()->first();

        if ($user->isSupplier()) {
            $reviews = Review::query()
                ->where('is_visible', true)
                ->whereHas('product', fn ($query) => $query->where('supplier_id', $user->id));
            $reviewCount = (clone $reviews)->count();
            $averageRating = $reviews->avg('rating');

            $stats = [
                ['label' => 'Total Produk', 'value' => number_format($user->products()->count()), 'description' => 'Produk di etalase'],
                ['label' => 'Total Pesanan', 'value' => number_format($user->supplierOrders()->count()), 'description' => 'Pesanan masuk'],
                ['label' => 'Rating', 'value' => $averageRating === null ? '—' : number_format((float) $averageRating, 1), 'description' => $reviewCount.' ulasan'],
            ];
            $roleLabel = 'Supplier';
            $statusLabel = match ($user->supplierProfile?->verification_status) {
                'approved' => 'Terverifikasi',
                'rejected' => 'Perlu diperbaiki',
                default => 'Menunggu verifikasi',
            };
            $isVerified = $user->supplierProfile?->verification_status === 'approved';
        } elseif ($user->isBuyer()) {
            $stats = [
                ['label' => 'Total Pesanan', 'value' => number_format($user->buyerOrders()->count()), 'description' => 'Transaksi tercatat'],
                ['label' => 'Alamat Tersimpan', 'value' => number_format($user->addresses()->count()), 'description' => 'Alamat pengiriman'],
                ['label' => 'Status Akun', 'value' => ucfirst($user->status), 'description' => 'Status keanggotaan'],
            ];
            $roleLabel = 'SPPG';
            $statusLabel = $user->status === 'active' ? 'Akun Aktif' : ucfirst($user->status);
            $isVerified = $user->status === 'active';
        } else {
            $stats = [
                ['label' => 'Total Pengguna', 'value' => number_format(User::count()), 'description' => 'Akun terdaftar'],
                ['label' => 'Produk Aktif', 'value' => number_format(Product::where('is_active', true)->count()), 'description' => 'Di seluruh katalog'],
                ['label' => 'Total Pesanan', 'value' => number_format(Order::count()), 'description' => 'Transaksi platform'],
            ];
            $roleLabel = 'Administrator';
            $statusLabel = $user->status === 'active' ? 'Akun Aktif' : ucfirst($user->status);
            $isVerified = $user->status === 'active';
        }

        $organizationDetails = $this->organizationDetails($user, $primaryAddress);

        return view('profile.edit', compact('user', 'primaryAddress', 'stats', 'roleLabel', 'statusLabel', 'isVerified', 'organizationDetails'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        if ($user->isBuyer()) {
            $addressRules = 'required_with:address_full_name,address_phone,address_province,address_city,address_district,address_village,address_postal_code,address_street';
            $validated = array_merge($validated, $request->validate([
                'sppg_id' => ['nullable', 'string', 'max:50', Rule::unique('users', 'sppg_id')->ignore($user->id)],
                'address_full_name' => ['nullable', $addressRules, 'string', 'max:255'],
                'address_phone' => ['nullable', $addressRules, 'string', 'max:30'],
                'address_province' => ['nullable', $addressRules, 'string', 'max:100'],
                'address_city' => ['nullable', $addressRules, 'string', 'max:100'],
                'address_district' => ['nullable', $addressRules, 'string', 'max:100'],
                'address_village' => ['nullable', $addressRules, 'string', 'max:100'],
                'address_postal_code' => ['nullable', $addressRules, 'string', 'max:20'],
                'address_street' => ['nullable', $addressRules, 'string', 'max:2000'],
            ]));
        }

        DB::transaction(function () use ($user, $validated) {
            $user->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'sppg_id' => $validated['sppg_id'] ?? $user->sppg_id,
            ]);

            if (! $user->isBuyer()) {
                return;
            }

            $addressData = [
                'full_name' => $validated['address_full_name'] ?? null,
                'phone' => $validated['address_phone'] ?? null,
                'province' => $validated['address_province'] ?? null,
                'city' => $validated['address_city'] ?? null,
                'district' => $validated['address_district'] ?? null,
                'village' => $validated['address_village'] ?? null,
                'postal_code' => $validated['address_postal_code'] ?? null,
                'street' => $validated['address_street'] ?? null,
                'is_primary' => true,
            ];

            if (! collect($addressData)->except('is_primary')->contains(fn ($value) => filled($value))) {
                return;
            }

            $address = $user->addresses()->where('is_primary', true)->first()
                ?? $user->addresses()->latest()->first();

            if ($address) {
                $address->update($addressData);
            } else {
                $user->addresses()->create(array_merge($addressData, ['type' => 'delivery']));
            }
        });

        return redirect()->route('profile.edit')->with('success', 'Informasi akun berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update(['password' => $validated['new_password']]);
        $request->user()->increment('api_token_version');
        $request->session()->regenerate();

        return redirect()->route('profile.edit')->with('success', 'Kata sandi berhasil diperbarui.');
    }

    private function organizationDetails(User $user, $primaryAddress): array
    {
        if ($user->isSupplier()) {
            $profile = $user->supplierProfile;

            return [
                ['label' => 'Nama Usaha', 'value' => $profile?->business_name ?? 'Belum dilengkapi'],
                ['label' => 'Jenis Usaha', 'value' => $profile?->business_type ?? 'Belum dilengkapi'],
                ['label' => 'Penanggung Jawab', 'value' => $profile?->contact_person ?? 'Belum dilengkapi'],
                ['label' => 'Nomor WhatsApp', 'value' => $user->phone ?? 'Belum dilengkapi'],
                ['label' => 'NIB', 'value' => $profile?->nib_number ?? 'Belum dilengkapi'],
                ['label' => 'Alamat Lengkap', 'value' => collect([$profile?->address, $profile?->village, $profile?->district, $profile?->city, $profile?->province, $profile?->postal_code])->filter()->join(', ') ?: 'Belum dilengkapi'],
                ['label' => 'Verifikasi', 'value' => ucfirst($profile?->verification_status ?? 'pending')],
            ];
        }

        if ($user->isBuyer()) {
            return [
                ['label' => 'ID SPPG', 'value' => $user->sppg_id ?? 'Belum dilengkapi'],
                ['label' => 'Nama Perusahaan / Nama Dapur', 'value' => $user->name],
                ['label' => 'Penanggung Jawab', 'value' => $primaryAddress?->full_name ?? $user->name],
                ['label' => 'Nomor WhatsApp', 'value' => $user->phone ?: ($primaryAddress?->phone ?? 'Belum dilengkapi')],
                ['label' => 'Alamat Lengkap', 'value' => $primaryAddress ? collect([$primaryAddress->street, $primaryAddress->village, $primaryAddress->district, $primaryAddress->city, $primaryAddress->province, $primaryAddress->postal_code])->filter()->join(', ') : 'Belum dilengkapi'],
                ['label' => 'Status Verifikasi', 'value' => ucfirst($user->status)],
            ];
        }

        return [
            ['label' => 'ID Akun', 'value' => 'SPPGMART-'.str_pad((string) $user->id, 6, '0', STR_PAD_LEFT)],
            ['label' => 'Peran', 'value' => 'Administrator'],
            ['label' => 'Status Akun', 'value' => ucfirst($user->status)],
            ['label' => 'Tanggal Terdaftar', 'value' => $user->created_at->format('d M Y')],
        ];
    }
}
