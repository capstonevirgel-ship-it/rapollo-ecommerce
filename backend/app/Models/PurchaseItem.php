<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = ['purchase_id', 'variant_id', 'ticket_id', 'quantity', 'price'];

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    // Helper method to get the item (either variant or ticket)
    public function getItem()
    {
        return $this->variant_id ? $this->variant : $this->ticket;
    }
}