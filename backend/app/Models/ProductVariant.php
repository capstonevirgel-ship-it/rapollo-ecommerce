<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'color_id', 'size_id', 'stock', 'sku'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'variant_id');
    }

    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Get the price from the product (accessor)
     */
    public function getPriceAttribute(): ?float
    {
        return $this->product?->price;
    }

    /**
     * Get the base_price from the product (accessor)
     */
    public function getBasePriceAttribute(): ?float
    {
        return $this->product?->base_price;
    }

    /**
     * Check if variant has sufficient stock
     */
    public function hasStock(int $quantity = 1): bool
    {
        return $this->stock >= $quantity;
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

    /**
     * Check if variant is low on stock
     */
    public function isLowStock(int $threshold = 10): bool
    {
        return $this->stock > 0 && $this->stock <= $threshold;
    }

    /**
     * Check if variant is out of stock
     */
    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }
}