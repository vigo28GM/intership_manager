<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action_type',
        'description',
        'model_type',
        'model_id',
        'properties',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'status',
        'error_message',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent model (polymorphic).
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope for filtering by action type.
     */
    public function scopeOfType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    /**
     * Scope for filtering by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for recent activities.
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
