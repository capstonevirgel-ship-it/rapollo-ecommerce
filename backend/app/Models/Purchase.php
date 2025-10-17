<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'total', 
        'status', 
        'type',
        'event_id',
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

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    // Helper methods
    public function isProductPurchase()
    {
        return $this->type === 'product';
    }

    public function isTicketPurchase()
    {
        return $this->type === 'ticket';
    }
}