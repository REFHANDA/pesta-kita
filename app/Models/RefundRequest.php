<?php

namespace App\Models;

use Database\Factories\RefundRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['event_id', 'invoice_id', 'client_id', 'reason', 'status', 'submitted_at'])]
class RefundRequest extends Model
{
    /** @use HasFactory<RefundRequestFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    /**
     * Event for which the request is submitted.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Paid invoice referenced by the request.
     *
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Client who submitted the intake request.
     *
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }
}
