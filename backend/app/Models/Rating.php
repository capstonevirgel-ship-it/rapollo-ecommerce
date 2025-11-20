<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'variant_id', 'product_purchase_id', 'stars', 'comment'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    public function productPurchase()
    {
        return $this->belongsTo(ProductPurchase::class);
    }
}