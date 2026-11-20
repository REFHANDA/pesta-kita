<?php

namespace App\Models;

use App\Enums\InvoiceItemType;
use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rincian baris dalam tagihan acara:
 * harga_penyedia, komisi, atau kebersihan.
 */
#[Fillable([
    'invoice_id',
    'order_item_id',
    'type',
    'description',
    'amount',
])]
class InvoiceItem extends Model
{
    /** @use HasFactory<InvoiceItemFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => InvoiceItemType::class,
            'amount' => 'integer',
        ];
    }

    /**
     * Tagihan pemilik baris rincian ini.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Item pesanan terkait (nullable untuk baris kebersihan).
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
