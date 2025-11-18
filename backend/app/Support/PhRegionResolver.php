<?php

namespace App\Support;

class PhRegionResolver
{
    private const NCR_CITIES = [
        'manila','quezon city','makati','pasig','mandaluyong','pasay','taguig','caloocan',
        'malabon','navotas','valenzuela','parañaque','las piñas','marikina','muntinlupa','san juan','pateros'
    ];

    private const LUZON_PROVINCES = [
        'abra','aurora','bataan','batangas','benguet','bulacan','cagayan','camarines norte','camarines sur',
        'ifugao','ilocos norte','ilocos sur','isabela','kalinga','la union','laguna','nueva ecija','nueva vizcaya',
        'occidental mindoro','oriental mindoro','palawan','pangasinan','quezon','quirino','rizal','romblon','sorsogon',
        'tarlac','zambales','apaya o','apayao'
    ];

    private const VISAYAS_PROVINCES = [
        'aklan','antique','biliran','bohol','capiz','eastern samar','guimaras','iloilo','leyte','negros occidental',
        'negros oriental','northern samar','samar','western samar','southern leyte'
    ];

    private const MINDANAO_PROVINCES = [
        'agusan del norte','agusan del sur','bukidnon','camiguin','compostela valley','davao del norte','davao del sur',
        'davao oriental','dinagat islands','lanao del norte','lanao del sur','maguindanao','misamis occidental',
        'misamis oriental','cotabato','north cotabato','sarangani','south cotabato','sultan kudarat','zamboanga del norte',
        'zamboanga del sur','zamboanga sibugay','surigao del norte','surigao del sur','basilan','sulu','tawi-tawi',
        'davao de oro','davao occidental'
    ];

    public static function resolveRegion(?string $city, ?string $province, ?string $storeOriginCity = null, ?string $storeOriginProvince = null): string
    {
        $city = strtolower(trim((string) $city));
        $province = strtolower(trim((string) $province));
        $origin = strtolower(trim((string) $storeOriginCity));
        $originProv = strtolower(trim((string) $storeOriginProvince));

        // Normalize " city" suffix for comparison
        $cityNorm = preg_replace('/\s+city$/', '', (string) $city);
        $originCityNorm = preg_replace('/\s+city$/', '', (string) $origin);

        if ($originCityNorm && $cityNorm && $cityNorm === $originCityNorm) {
            return 'local';
        }

        // Same-province dynamic rule when store origin province is provided
        if ($originProv && $province && $province === $originProv) {
            return $province; // e.g., 'cebu'
        }

        if (in_array($city, self::NCR_CITIES, true)) {
            return 'luzon';
        }

        // Backward compatibility: explicit Cebu mapping if origin province not provided
        if (!$originProv && $province === 'cebu') {
            return 'cebu';
        }

        if (in_array($province, self::LUZON_PROVINCES, true)) {
            return 'luzon';
        }
        if (in_array($province, self::VISAYAS_PROVINCES, true)) {
            return 'visayas';
        }
        if (in_array($province, self::MINDANAO_PROVINCES, true)) {
            return 'mindanao';
        }

        return 'luzon';
    }
}


