<?php

namespace App\Models;

use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'event_id',
    'version',
    'unique_code',
    'active_code',
    'provider_subtotal',
    'commission_total',
    'cleaning_total',
    'total_amount',
    'status',
    'issued_at',
    'due_at',
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
            'unique_code' => 'integer',
            'active_code' => 'integer',
            'provider_subtotal' => 'integer',
            'commission_total' => 'integer',
            'cleaning_total' => 'integer',
            'total_amount' => 'integer',
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
        ];
    }

    /**
     * Event this invoice snapshots.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Immutable pricing rows for this invoice version.
     *
     * @return HasMany<InvoiceLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    /**
     * Payment proofs submitted against this invoice version.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Most recently submitted payment proof.
     *
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }
}
