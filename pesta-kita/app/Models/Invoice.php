<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $attributes = ['status' => 'pending'];

    protected $fillable = ['order_id', 'invoice_number', 'amount', 'status', 'due_at', 'paid_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'due_at' => 'datetime', 'paid_at' => 'datetime'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
