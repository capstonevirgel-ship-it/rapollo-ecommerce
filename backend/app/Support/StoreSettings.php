<?php

namespace App\Support;

use App\Models\Setting;

class StoreSettings
{
    public static function get(string $key, $default = null)
    {
        $value = Setting::query()
            ->where('key', $key)
            ->value('value');
        return $value ?? $default;
    }

    public static function getAddress(): array
    {
        $settings = Setting::query()
            ->whereIn('key', [
                'store_street',
                'store_barangay',
                'store_city',
                'store_province',
                'store_zipcode',
            ])
            ->pluck('value', 'key');

        return [
            'street' => (string) ($settings['store_street'] ?? ''),
            'barangay' => (string) ($settings['store_barangay'] ?? ''),
            'city' => (string) ($settings['store_city'] ?? ''),
            'province' => (string) ($settings['store_province'] ?? ''),
            'zipcode' => (string) ($settings['store_zipcode'] ?? ''),
        ];
    }

    public static function getNormalizedCity(): ?string
    {
        $city = self::get('store_city');
        return self::normalizeCity($city);
    }

    public static function getNormalizedProvince(): ?string
    {
        $province = self::get('store_province');
        return self::normalizeProvince($province);
    }

    public static function normalizeCity(?string $city): ?string
    {
        if ($city === null) {
            return null;
        }
        $normalized = strtolower(trim($city));
        // Handle "City of X" format -> "X"
        $normalized = preg_replace('/^city\s+of\s+/', '', $normalized);
        // Handle "X City" format -> "X"
        $normalized = preg_replace('/\s+city$/', '', $normalized);
        return $normalized ?: null;
    }

    public static function normalizeProvince(?string $province): ?string
    {
        if ($province === null) {
            return null;
        }
        $normalized = strtolower(trim($province));
        return $normalized ?: null;
    }
}


