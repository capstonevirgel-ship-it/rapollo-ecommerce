<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'total_amount', 
        'status', 
        'payment_status', 
        'payment_intent_id',
        'payment_amount',
        'payment_currency',
        'payment_failure_code',
        'payment_failure_message',
        'shipping_address',
        'billing_address',
        'paid_at'
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'paid_at' => 'datetime',
    ];

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function items()
    {
        return $this->purchaseItems();
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
        return $this->hasOne(Payment::class);
    }
}