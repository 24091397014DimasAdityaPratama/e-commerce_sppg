<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use RuntimeException;

class JwtService
{
    public function createToken(User $user): string
    {
        $payload = [
            'iss' => config('app.url', 'http://localhost'),
            'sub' => $user->getKey(),
            'role' => $user->role,
            'ver' => (int) ($user->api_token_version ?? 0),
            'iat' => now()->timestamp,
            'exp' => now()->addDays(7)->timestamp,
        ];

        return JWT::encode($payload, $this->secret(), 'HS256');
    }

    public function decode(string $token): object
    {
        return JWT::decode($token, new Key($this->secret(), 'HS256'));
    }

    protected function secret(): string
    {
        $secret = config('services.jwt.secret') ?: config('app.key');

        if (! is_string($secret) || $secret === '') {
            throw new RuntimeException('JWT_SECRET atau APP_KEY harus dikonfigurasi untuk menggunakan API.');
        }

        return $secret;
    }
}
