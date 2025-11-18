<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Support\PhRegionResolver;
use App\Support\StoreSettings;
use App\Models\ShippingPrice;

class ShippingResolverController extends Controller
{
    public function resolve(Request $request)
    {
        $validated = $request->validate([
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:150',
        ]);

        $originCity = StoreSettings::getNormalizedCity() ?? config('app.store_origin_city', 'cebu city');
        $originProvince = StoreSettings::getNormalizedProvince() ?? 'cebu';
        $region = PhRegionResolver::resolveRegion(
            $validated['city'] ?? null,
            $validated['province'] ?? null,
            $originCity,
            $originProvince
        );
        $price = ShippingPrice::getPriceForRegion($region);

        return response()->json([
            'region' => $region,
            'price' => $price,
        ]);
    }
}


