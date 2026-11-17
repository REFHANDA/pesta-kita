<?php

namespace App\Models;

use Database\Factories\InvoiceLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'invoice_id',
    'order_item_id',
    'description_snapshot',
    'provider_amount',
    'commission_amount',
    'cleaning_fee_amount',
    'total_amount',
])]
class InvoiceLine extends Model
{
    /** @use HasFactory<InvoiceLineFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider_amount' => 'integer',
            'commission_amount' => 'integer',
            'cleaning_fee_amount' => 'integer',
            'total_amount' => 'integer',
        ];
    }

    /**
     * Invoice version containing this snapshot.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Source order item.
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
