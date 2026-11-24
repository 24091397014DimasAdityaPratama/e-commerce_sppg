<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        try {
            $claims = app(JwtService::class)->decode($token);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Token invalid or expired.'], 401);
        }

        $user = User::find($claims->sub ?? null);

        if (! $user
            || $user->status !== 'active'
            || ! isset($claims->ver)
            || (int) $claims->ver !== (int) $user->api_token_version) {
            return response()->json(['message' => 'Token invalid or expired.'], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
