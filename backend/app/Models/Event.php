<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['admin_id', 'title', 'description', 'date', 'location', 'poster_url', 'ticket_price', 'max_tickets', 'available_tickets'];
    
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
}