<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogItem extends Model
{
    protected $attributes = ['is_available' => true];

    protected $fillable = [
        'user_id', 'type', 'name', 'category', 'description', 'location', 'capacity', 'price', 'is_available',
    ];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'is_available' => 'boolean'];
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
