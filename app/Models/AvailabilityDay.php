<?php

namespace App\Models;

use App\Enums\AvailabilityStatus;
use Database\Factories\AvailabilityDayFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Ketersediaan harian per subjek (catalog_item atau venue_room). B11:
 * hanya status manual yang disimpan; status dipesan kelak diturunkan dari
 * order_items terkonfirmasi, bukan diketik penyedia.
 */
#[Fillable(['subject_type', 'subject_id', 'date', 'status'])]
class AvailabilityDay extends Model
{
    /** @use HasFactory<AvailabilityDayFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'status' => AvailabilityStatus::class,
        ];
    }

    /**
     * Subjek ketersediaan (catalog_item atau venue_room).
     *
     * @return MorphTo<Model, $this>
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
