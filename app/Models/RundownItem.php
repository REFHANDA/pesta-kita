<?php

namespace App\Models;

use Database\Factories\RundownItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Item susunan jadwal/rundown acara.
 */
#[Fillable([
    'event_id',
    'created_by',
    'order_item_id',
    'start_time',
    'end_time',
    'activity',
    'description',
])]
class RundownItem extends Model
{
    /** @use HasFactory<RundownItemFactory> */
    use HasFactory;

    /**
     * Acara pemilik item rundown ini.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Pengguna pembuat item rundown (klien atau vendor).
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Item pesanan terkait (opsional).
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }
}
