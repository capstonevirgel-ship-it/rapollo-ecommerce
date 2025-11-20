<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'purchasable_type',
        'purchasable_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_intent_id',
        'transaction_id',
        'payment_method_id',
        'payment_failure_code',
        'payment_failure_message',
        'payment_date',
        'notes',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'datetime',
        'metadata' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchasable()
    {
        return $this->morphTo();
    }
}
