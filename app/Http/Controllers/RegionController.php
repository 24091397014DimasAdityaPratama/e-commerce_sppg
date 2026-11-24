<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use UnexpectedValueException;

class RegionController extends Controller
{
    private const API_BASE_URL = 'https://wilayah.id/api';

    public function provinces(): JsonResponse
    {
        return $this->areas('provinces');
    }

    public function regencies(string $province): JsonResponse
    {
        abort_unless(preg_match('/\A\d{2}\z/', $province) === 1, 404);

        return $this->areas("regencies/{$province}");
    }

    public function districts(string $regency): JsonResponse
    {
        abort_unless(preg_match('/\A\d{2}\.\d{2}\z/', $regency) === 1, 404);

        return $this->areas("districts/{$regency}");
    }

    public function villages(string $district): JsonResponse
    {
        abort_unless(preg_match('/\A\d{2}\.\d{2}\.\d{2}\z/', $district) === 1, 404);

        return $this->areas("villages/{$district}");
    }

    private function areas(string $path): JsonResponse
    {
        $areas = Cache::remember("indonesia-regions:{$path}", now()->addDay(), function () use ($path) {
            $response = Http::acceptJson()
                ->timeout(8)
                ->get(self::API_BASE_URL."/{$path}.json");
            $response->throw();

            $data = $response->json('data');
            if (! is_array($data)) {
                throw new UnexpectedValueException('API wilayah mengembalikan format data yang tidak valid.');
            }

            return $data;
        });

        return response()->json(['data' => $areas]);
    }
}
