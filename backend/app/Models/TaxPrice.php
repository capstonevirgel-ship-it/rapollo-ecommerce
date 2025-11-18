<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TaxPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'rate',
        'description',
        'is_active'
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Scope for active tax prices
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Get all active tax prices
    public static function getAllActivePrices()
    {
        return self::active()
                  ->orderBy('name')
                  ->get();
    }

    // Get total tax rate from all active taxes (sum of all rates)
    public static function getTotalTaxRate(): float
    {
        $activeTaxes = self::active()->get();
        return (float) $activeTaxes->sum('rate');
    }

    // Get active tax rate as percentage (for display)
    public static function getTotalTaxRatePercentage(): float
    {
        return self::getTotalTaxRate();
    }

    // Calculate final price from base price
    public static function calculateFinalPrice(float $basePrice): float
    {
        $totalTaxRate = self::getTotalTaxRate();
        return $basePrice * (1 + ($totalTaxRate / 100));
    }
}

