<?php

namespace App\Models;

use App\Enums\QuoteRequestStatus;
use Database\Factories\QuoteRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Permintaan penawaran Klien atas katalog atau ruangan venue. Harga
 * katalog disimpan sebagai snapshot; harga usulan penyedia dan harga
 * final Admin tetap terpisah (aturan terkunci A5).
 */
#[Fillable([
    'client_id',
    'event_id',
    'subject_type',
    'subject_id',
    'event_date',
    'guest_count',
    'budget',
    'notes',
    'catalog_price_snapshot',
    'status',
])]
class QuoteRequest extends Model
{
    /** @use HasFactory<QuoteRequestFactory> */
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
            'catalog_price_snapshot' => 'integer',
            'status' => QuoteRequestStatus::class,
        ];
    }

    /**
     * Akun Klien pengaju permintaan.
     *
     * @return BelongsTo<User, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Acara terkait; nullable selama acara masih draft.
     *
     * @return BelongsTo<Event, $this>
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Subjek penawaran (catalog_item atau venue_room).
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Usulan harga penyedia atas permintaan ini.
     *
     * @return HasMany<QuoteProposal, $this>
     */
    public function proposals(): HasMany
    {
        return $this->hasMany(QuoteProposal::class);
    }

    /**
     * Keputusan final Admin (0..1, dijaga unique constraint).
     *
     * @return HasOne<QuoteDecision, $this>
     */
    public function decision(): HasOne
    {
        return $this->hasOne(QuoteDecision::class);
    }
}
