<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ShippingPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'region',
        'price',
        'description',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Available regions for shipping
    public static function getAvailableRegions(): array
    {
        return [
            'local' => 'Local (Same City)',
            'luzon' => 'Luzon',
            'cebu' => 'Within Cebu',
            'visayas' => 'Visayas',
            'mindanao' => 'Mindanao'
        ];
    }

    // Scope for active shipping prices
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Get shipping price for a specific region
    public static function getPriceForRegion(string $region): ?float
    {
        $shipping = self::where('region', $region)
                       ->where('is_active', true)
                       ->first();
        
        return $shipping ? (float) $shipping->price : null;
    }

    // Get all active shipping prices
    public static function getAllActivePrices(): array
    {
        return self::active()
                  ->orderBy('region')
                  ->get()
                  ->pluck('price', 'region')
                  ->toArray();
    }
}
