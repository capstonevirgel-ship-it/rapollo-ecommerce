<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = ['product_purchase_id', 'variant_id', 'quantity', 'price'];

    protected $casts = [
        'price' => 'float',
        'quantity' => 'int',
    ];

    protected $appends = [
        'unit_price',
        'total_price',
        'final_unit_price',
        'final_total_price',
    ];

    public function productPurchase()
    {
        return $this->belongsTo(ProductPurchase::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function getUnitPriceAttribute(): float
    {
        return (float) $this->price;
    }

    public function getTotalPriceAttribute(): float
    {
        return $this->getUnitPriceAttribute() * $this->quantity;
    }

    public function getFinalUnitPriceAttribute(): float
    {
        $taxRate = 0;

        if ($this->relationLoaded('productPurchase') || $this->productPurchase) {
            $shippingAddress = $this->productPurchase->shipping_address;
            if (is_array($shippingAddress) && isset($shippingAddress['tax_rate'])) {
                $taxRate = (float) $shippingAddress['tax_rate'];
            }
        }

        return round($this->getUnitPriceAttribute() * (1 + ($taxRate / 100)), 2);
    }

    public function getFinalTotalPriceAttribute(): float
    {
        return round($this->getFinalUnitPriceAttribute() * $this->quantity, 2);
    }
}
