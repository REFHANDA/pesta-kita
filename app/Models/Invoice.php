<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tagihan acara (gabungan seluruh pesanan + komisi + kebersihan + kode unik).
 */
#[Fillable([
    'event_id',
    'invoice_number',
    'receipt_number',
    'version',
    'subtotal_provider',
    'total_commission',
    'cleaning_fee',
    'grand_total',
    'unique_code',
    'transfer_total',
    'due_at',
    'status',
    'issued_at',
    'paid_at',
])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'subtotal_provider' => 'integer',
            'total_commission' => 'integer',
            'cleaning_fee' => 'integer',
            'grand_total' => 'integer',
            'unique_code' => 'integer',
            'transfer_total' => 'integer',
            'due_at' => 'datetime',
            'status' => InvoiceStatus::class,
            'issued_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * Acara yang ditagihkan.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Rincian baris dalam tagihan.
     *
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Bukti pembayaran untuk tagihan ini.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Bukti pembayaran terbaru.
     *
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }
}
