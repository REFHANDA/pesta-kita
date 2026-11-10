<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $attributes = [
        'quantity' => 1,
        'status' => 'pending',
        'payment_status' => 'pending',
    ];

    protected $fillable = [
        'event_id', 'client_id', 'catalog_item_id', 'vendor_id', 'quantity',
        'unit_price', 'total_amount', 'status', 'payment_status', 'notes',
    ];

    protected function casts(): array
    {
        return ['unit_price' => 'decimal:2', 'total_amount' => 'decimal:2'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function catalogItem(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
