<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketPurchase extends Model
{
    protected $fillable = [
        'user_id',
        'event_id',
        'quantity',
        'total_amount',
        'status',
        'payment_intent_id',
        'payment_date'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_date' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
