<?php

namespace App\Models;

use Database\Factories\VenueFacilityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Fasilitas venue (checkbox form venue). Nama unik per venue.
 */
#[Fillable(['venue_id', 'name'])]
class VenueFacility extends Model
{
    /** @use HasFactory<VenueFacilityFactory> */
    use HasFactory;

    /**
     * Venue induk fasilitas.
     *
     * @return BelongsTo<Venue, $this>
     */
    public function venue(): BelongsTo
    {
        return $this->belongsTo(Venue::class);
    }
}
