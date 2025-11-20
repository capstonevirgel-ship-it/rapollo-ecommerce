<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketPurchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'event_id',
        'total', 
        'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function payment()
    {
        return $this->morphOne(Payment::class, 'purchasable');
    }

    // Helper methods
    public function isPaid()
    {
        return $this->paid_at !== null;
    }
}
