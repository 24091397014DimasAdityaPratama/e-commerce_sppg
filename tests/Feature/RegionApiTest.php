<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_region_api_proxies_and_caches_public_region_data(): void
    {
        Cache::forget('indonesia-regions:provinces');
        Http::fake([
            'https://wilayah.id/api/provinces.json' => Http::response([
                'data' => [
                    ['code' => '32', 'name' => 'Jawa Barat'],
                ],
            ]),
        ]);

        $this->getJson(route('regions.provinces'))
            ->assertOk()
            ->assertJsonPath('data.0.code', '32')
            ->assertJsonPath('data.0.name', 'Jawa Barat');

        $this->getJson(route('regions.provinces'))->assertOk();

        Http::assertSentCount(1);
        Http::assertSent(fn (ClientRequest $request) => $request->url() === 'https://wilayah.id/api/provinces.json');
    }

    public function test_region_api_rejects_malformed_administrative_codes(): void
    {
        Http::fake();

        $this->getJson('/regions/regencies/invalid')->assertNotFound();
        $this->getJson('/regions/districts/32.7')->assertNotFound();
        $this->getJson('/regions/villages/32.73.01.extra')->assertNotFound();

        Http::assertNothingSent();
    }
}
