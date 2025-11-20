<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'total', 
        'status', 
        'shipping_address',
        'billing_address',
        'paid_at'
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'paid_at' => 'datetime',
    ];

    public function productPurchaseItems()
    {
        return $this->hasMany(ProductPurchaseItem::class);
    }

    public function items()
    {
        return $this->productPurchaseItems();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function payment()
    {
        return $this->morphOne(Payment::class, 'purchasable');
    }

    // Helper methods
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    public function isShipped()
    {
        return $this->status === 'shipped';
    }

    public function isDelivered()
    {
        return $this->status === 'delivered';
    }
}
