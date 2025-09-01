<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'logo_url', 'meta_title', 'meta_description'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}