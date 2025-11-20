<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['admin_id', 'title', 'content', 'date', 'location', 'poster_url', 'base_ticket_price', 'ticket_price', 'max_tickets', 'available_tickets'];
    
    protected $appends = ['booked_tickets_count', 'remaining_tickets'];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function comments()
    {
        return $this->hasMany(EventComment::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function getBookedTicketsCountAttribute()
    {
        return $this->tickets()->whereIn('status', ['pending', 'confirmed'])->count();
    }

    public function getRemainingTicketsAttribute()
    {
        return $this->max_tickets - $this->booked_tickets_count;
    }

    public function isFullyBooked()
    {
        return $this->booked_tickets_count >= $this->max_tickets;
    }

    /**
     * Check if event has enough tickets available
     */
    public function hasTicketsAvailable(int $quantity = 1): bool
    {
        return $this->remaining_tickets >= $quantity;
    }

    /**
     * Reserve tickets (decrement available count)
     */
    public function reserveTickets(int $quantity): bool
    {
        if (!$this->hasTicketsAvailable($quantity)) {
            return false;
        }

        // Note: available_tickets is calculated from booked tickets
        // Actual reservation happens when tickets are created
        return true;
    }

    /**
     * Check if tickets are low
     */
    public function isLowOnTickets(int $threshold = 10): bool
    {
        return $this->remaining_tickets > 0 && $this->remaining_tickets <= $threshold;
    }

    /**
     * Calculate and set final ticket price from base price and taxes
     */
    public function calculateFinalPrice(): void
    {
        if ($this->base_ticket_price !== null) {
            $this->ticket_price = \App\Models\TaxPrice::calculateFinalPrice($this->base_ticket_price);
        }
    }

    /**
     * Boot method to auto-calculate price on save
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($event) {
            if ($event->base_ticket_price !== null && $event->isDirty('base_ticket_price')) {
                $event->calculateFinalPrice();
            }
        });
    }
}