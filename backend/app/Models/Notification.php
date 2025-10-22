<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'custom_notifications';
    
    protected $fillable = [
        'user_id',
        'title',
        'message',
        'type',
        'read',
        'action_url',
        'action_text',
        'metadata'
    ];

    protected $casts = [
        'read' => 'boolean',
        'metadata' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markAsRead(): void
    {
        $this->update(['read' => true]);
    }

    public function markAsUnread(): void
    {
        $this->update(['read' => false]);
    }

    public static function createForUser(int $userId, string $title, string $message, string $type, array $options = []): self
    {
        return self::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'action_url' => $options['action_url'] ?? null,
            'action_text' => $options['action_text'] ?? null,
            'metadata' => $options['metadata'] ?? null,
        ]);
    }
}
