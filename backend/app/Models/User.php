<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Notifications\CustomResetPasswordNotification;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_name',
        'email',
        'password',
        'role',
        'is_suspended',
        'suspended_at',
        'suspension_reason',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
            'is_suspended' => 'boolean',
        ];
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function cartItems()
    {
        return $this->hasMany(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'admin_id');
    }

    public function eventComments()
    {
        return $this->hasMany(EventComment::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function unreadNotifications()
    {
        return $this->hasMany(Notification::class)->where('read', false);
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPasswordNotification($token));
    }

    /**
     * Check if user is suspended.
     *
     * @return bool
     */
    public function isSuspended(): bool
    {
        return $this->is_suspended === true;
    }

    /**
     * Suspend the user.
     *
     * @param  string|null  $reason
     * @return bool
     */
    public function suspend(?string $reason = null): bool
    {
        return $this->update([
            'is_suspended' => true,
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);
    }

    /**
     * Unsuspend the user.
     *
     * @return bool
     */
    public function unsuspend(): bool
    {
        return $this->update([
            'is_suspended' => false,
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);
    }

    /**
     * Get user's product purchases.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function productPurchases()
    {
        return $this->hasMany(ProductPurchase::class);
    }

    /**
     * Get user's ticket purchases.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ticketPurchases()
    {
        return $this->hasMany(TicketPurchase::class);
    }
}
