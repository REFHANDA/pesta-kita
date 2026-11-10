<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $attributes = ['status' => 'draft'];

    protected $fillable = [
        'user_id', 'name', 'event_date', 'guest_count', 'theme', 'description', 'budget', 'status',
    ];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'budget' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
