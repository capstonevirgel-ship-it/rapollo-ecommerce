<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Size extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order'
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($size) {
            if (empty($size->slug)) {
                $size->slug = Str::slug($size->name);
            }
        });
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_sizes');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}