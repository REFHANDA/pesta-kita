<?php

namespace App\Enums;

namespace App\Models;

use App\Enums\EventStatus;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Acara milik Klien. Sesuai rancangan EMS:
 * status: draft -> diajukan -> menunggu_pembayaran -> terkonfirmasi -> selesai -> dievaluasi; cabang dibatalkan.
 */
#[Fillable(['client_id', 'name', 'event_date', 'guest_count', 'theme', 'budget', 'description', 'status'])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date:Y-m-d',
            'guest_count' => 'integer',
            'budget' => 'integer',
            'status' => EventStatus::class,
        ];
    }

    /**
     * Akun Klien pemilik acara.
     *
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Item pesanan dalam acara.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Seluruh tagihan untuk acara ini.
     *
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Tagihan aktif terkini (belum dibayar, menunggu validasi, atau lunas).
     *
     * @return HasOne<Invoice, $this>
     */
    public function activeInvoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    /**
     * Catatan pengajuan pembatalan acara.
     *
     * @return HasMany<Cancellation, $this>
     */
    public function cancellations(): HasMany
    {
        return $this->hasMany(Cancellation::class);
    }

    /**
     * Agenda rundown acara.
     *
     * @return HasMany<RundownItem, $this>
     */
    public function rundownItems(): HasMany
    {
        return $this->hasMany(RundownItem::class)->orderBy('start_time');
    }

    /**
     * Evaluasi dan rating acara setelah selesai.
     *
     * @return HasOne<Evaluation, $this>
     */
    public function evaluation(): HasOne
    {
        return $this->hasOne(Evaluation::class);
    }
}
