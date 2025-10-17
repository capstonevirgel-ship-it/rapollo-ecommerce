<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'purchase_id',
        'ticket_number',
        'price',
        'status',
        'qr_code',
        'booked_at'
    ];

    protected $casts = [
        'booked_at' => 'datetime',
        'price' => 'decimal:2'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function generateTicketNumber()
    {
        return 'TKT-' . strtoupper(uniqid());
    }

    public function generateQRCode()
    {
        // Simple QR code generation - in production, use a proper QR library
        return 'QR-' . $this->ticket_number . '-' . time();
    }
}
