<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subcategory_id', 'brand_id', 'default_color_id', 'name', 'slug', 'description',
        'meta_title', 'meta_description', 'meta_keywords', 'canonical_url',
        'robots', 'is_active', 'is_featured', 'is_hot', 'is_new',
        'base_price', 'price', 'stock', 'sku'
    ];

    protected $casts = [
        'base_price' => 'float',
        'price' => 'float',
    ];

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function defaultColor()
    {
        return $this->belongsTo(Color::class, 'default_color_id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function sizes()
    {
        return $this->belongsToMany(Size::class, 'product_sizes');
    }

    /**
     * Calculate and set final price from base price and taxes
     */
    public function calculateFinalPrice(): void
    {
        if ($this->base_price !== null && $this->base_price > 0) {
            $this->price = \App\Models\TaxPrice::calculateFinalPrice($this->base_price);
        }
    }

    /**
     * Boot method to auto-calculate price on save
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($product) {
            if ($product->base_price !== null && $product->base_price > 0 && $product->isDirty('base_price')) {
                $product->calculateFinalPrice();
            }
        });
    }

    /**
     * Check if product has sufficient stock
     */
    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
    }

    /**
     * Check if product is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * Check if product is low on stock
     */
    public function isLowStock(int $threshold = 10): bool
    {
        return $this->stock > 0 && $this->stock <= $threshold;
    }

    /**
     * Decrement stock
     */
    public function decrementStock(int $quantity): bool
    {
        if (!$this->hasStock($quantity)) {
            return false;
        }

        $this->decrement('stock', $quantity);
        return true;
    }

    /**
     * Increment stock (for returns/cancellations)
     */
    public function incrementStock(int $quantity): void
    {
        $this->increment('stock', $quantity);
    }
}