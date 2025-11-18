<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PsgcController extends Controller
{
    private string $baseUrl = 'https://psgc.rootscratch.com';

    public function regions(Request $request)
    {
        Log::info('PSGC proxy: regions request', [
            'path' => $request->path(),
            'query' => $request->query(),
        ]);
        $cacheKey = 'psgc_regions_v1';
        $data = Cache::remember($cacheKey, now()->addHours(24), function () {
            $url = $this->baseUrl . '/regions';
            Log::info('PSGC proxy: GET regions', ['url' => $url]);
            try {
                $resp = Http::timeout(10)->retry(2, 200)->acceptJson()->get($url);
                $resp->throw();
                return $resp->json();
            } catch (\Throwable $e) {
                Log::error('PSGC proxy regions error', [
                    'url' => $url,
                    'status' => method_exists($e, 'getCode') ? $e->getCode() : null,
                    'message' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
        return response()->json($data);
    }

    public function provinces(Request $request)
    {
        $regionId = $request->query('regionId');
        Log::info('PSGC proxy: provinces request', [
            'path' => $request->path(),
            'query' => $request->query(),
        ]);
        $cacheKey = 'psgc_provinces_v1_' . ($regionId ?: 'all');
        $data = Cache::remember($cacheKey, now()->addHours(24), function () use ($regionId) {
            // Rootscratch uses singular path: /province
            $url = $this->baseUrl . '/province';
            $params = ['id' => $regionId];
            Log::info('PSGC proxy: GET province', ['url' => $url, 'params' => $params]);
            try {
                $resp = Http::timeout(10)->retry(2, 200)->acceptJson()->get($url, $params);
                $resp->throw();
                return $resp->json();
            } catch (\Throwable $e) {
                Log::error('PSGC proxy province error', [
                    'url' => $url,
                    'params' => $params,
                    'status' => method_exists($e, 'getCode') ? $e->getCode() : null,
                    'message' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
        return response()->json($data);
    }

    public function citiesMunicipalities(Request $request)
    {
        $provinceId = $request->query('provinceId');
        if (!$provinceId) {
            return response()->json([
                'error' => 'provinceId is required'
            ], 422);
        }
        Log::info('PSGC proxy: cities-municipalities request', [
            'path' => $request->path(),
            'query' => $request->query(),
        ]);
        $cacheKey = 'psgc_cities_v1_' . $provinceId;
        $data = Cache::remember($cacheKey, now()->addHours(24), function () use ($provinceId) {
            // Correct endpoint per docs: /municipal-city?id={provincePsgcId}
            $url = $this->baseUrl . '/municipal-city';
            $params = ['id' => $provinceId];
            Log::info('PSGC proxy: GET municipal-city (for cities)', ['url' => $url, 'params' => $params]);
            try {
                $resp = Http::timeout(10)->retry(2, 200)->acceptJson()->get($url, $params);
                $resp->throw();
                return $resp->json();
            } catch (\Throwable $e) {
                Log::error('PSGC proxy cities error', [
                    'url' => $url,
                    'params' => $params,
                    'status' => method_exists($e, 'getCode') ? $e->getCode() : null,
                    'message' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
        return response()->json($data);
    }

    public function barangays(Request $request)
    {
        $cityMunicipalityId = $request->query('cityMunicipalityId');
        Log::info('PSGC proxy: barangays request', [
            'path' => $request->path(),
            'query' => $request->query(),
        ]);
        $cacheKey = 'psgc_barangays_v1_' . ($cityMunicipalityId ?: 'all');
        $data = Cache::remember($cacheKey, now()->addHours(24), function () use ($cityMunicipalityId) {
            // Use dedicated Barangay endpoint
            $url = $this->baseUrl . '/barangay';
            $params = ['id' => $cityMunicipalityId];
            Log::info('PSGC proxy: GET barangay (for barangays)', ['url' => $url, 'params' => $params]);
            try {
                $resp = Http::timeout(10)->retry(2, 200)->acceptJson()->get($url, $params);
                $resp->throw();
                return $resp->json();
            } catch (\Throwable $e) {
                Log::error('PSGC proxy barangays error', [
                    'url' => $url,
                    'params' => $params,
                    'status' => method_exists($e, 'getCode') ? $e->getCode() : null,
                    'message' => $e->getMessage(),
                ]);
                throw $e;
            }
        });
        return response()->json($data);
    }
}


