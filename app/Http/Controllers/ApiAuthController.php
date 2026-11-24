<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegisterUserRequest;
use App\Models\SupplierProfile;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ApiAuthController extends Controller
{
    public function register(RegisterUserRequest $request, JwtService $jwtService)
    {
        $data = $request->validated();
        $role = $data['role'] ?? 'buyer';
        $nibDocumentPath = null;

        if ($role === 'supplier') {
            $nibDocumentPath = $request->file('nib_document')->store('supplier-nib-documents', 'local');

            if ($nibDocumentPath === false) {
                throw new RuntimeException('Dokumen NIB gagal disimpan.');
            }
        }

        try {
            $user = DB::transaction(function () use ($data, $role, $nibDocumentPath): User {
                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => $role,
                    'phone' => $data['phone'],
                    'sppg_id' => $role === 'buyer' ? $data['sppg_id'] : null,
                    'status' => 'active',
                ]);

                if ($role === 'buyer') {
                    $user->addresses()->create([
                        'type' => 'work',
                        'full_name' => $data['contact_person'],
                        'phone' => $data['phone'],
                        'province' => $data['province'],
                        'city' => $data['city'],
                        'district' => $data['district'],
                        'village' => $data['village'],
                        'postal_code' => $data['postal_code'] ?? '',
                        'street' => $data['address'],
                        'is_primary' => true,
                    ]);
                } else {
                    SupplierProfile::create([
                        'user_id' => $user->id,
                        'business_name' => $data['name'],
                        'contact_person' => $data['contact_person'],
                        'province' => $data['province'],
                        'city' => $data['city'],
                        'district' => $data['district'],
                        'village' => $data['village'],
                        'postal_code' => $data['postal_code'] ?? null,
                        'address' => $data['address'],
                        'nib_number' => $data['nib_number'],
                        'nib_document_path' => $nibDocumentPath,
                        'verification_status' => 'pending',
                    ]);
                }

                return $user;
            });
        } catch (Throwable $exception) {
            if ($nibDocumentPath) {
                Storage::disk('local')->delete($nibDocumentPath);
            }

            throw $exception;
        }

        return response()->json([
            'message' => 'Registrasi berhasil.',
            'user' => $user->only(['id', 'name', 'email', 'role', 'phone']),
            'token' => $jwtService->createToken($user),
        ], 201);
    }

    public function login(LoginUserRequest $request, JwtService $jwtService)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || $user->status !== 'active' || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Kredensial tidak valid.'], 401);
        }

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => $user->only(['id', 'name', 'email', 'role', 'phone']),
            'token' => $jwtService->createToken($user),
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => $request->user()->only(['id', 'name', 'email', 'role', 'phone', 'status']),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->increment('api_token_version');

        return response()->json(['message' => 'Logout berhasil.']);
    }
}
