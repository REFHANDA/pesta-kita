<?php

namespace App\Models;

use App\Enums\CatalogItemStatus;
use App\Enums\CatalogKind;
use App\Enums\PricingMode;
use App\Enums\VerificationStatus;
use Database\Factories\CatalogItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Item katalog Vendor: layanan atau peralatan dalam satu tabel (B15).
 * Harga katalog integer rupiah; perubahan harga lewat PriceChangeRequest
 * dan harga final kelak dikunci Admin (aturan terkunci A5/A11).
 */
#[Fillable([
    'vendor_profile_id',
    'kind',
    'name',
    'category',
    'description',
    'catalog_price',
    'pricing_mode',
    'max_per_day',
    'status',
    'policies',
])]
class CatalogItem extends Model
{
    /** @use HasFactory<CatalogItemFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => CatalogKind::class,
            'pricing_mode' => PricingMode::class,
            'status' => CatalogItemStatus::class,
            'catalog_price' => 'integer',
            'max_per_day' => 'integer',
        ];
    }

    /**
     * Profil usaha Vendor pemilik item.
     *
     * @return BelongsTo<VendorProfile, $this>
     */
    public function vendorProfile(): BelongsTo
    {
        return $this->belongsTo(VendorProfile::class);
    }

    /**
     * Ketersediaan harian item (morph subject catalog_item).
     *
     * @return MorphMany<AvailabilityDay, $this>
     */
    public function availabilityDays(): MorphMany
    {
        return $this->morphMany(AvailabilityDay::class, 'subject');
    }

    /**
     * Media item katalog.
     *
     * @return MorphMany<Media, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'owner');
    }

    /**
     * Pengajuan perubahan harga item.
     *
     * @return MorphMany<PriceChangeRequest, $this>
     */
    public function priceChangeRequests(): MorphMany
    {
        return $this->morphMany(PriceChangeRequest::class, 'subject');
    }

    /**
     * Item pesanan yang mengacu item ini.
     *
     * @return MorphMany<OrderItem, $this>
     */
    public function orderItems(): MorphMany
    {
        return $this->morphMany(OrderItem::class, 'subject');
    }

    /**
     * Filter publik Klien (B14 + keputusan Gate 4B butir 2): item aktif,
     * penyedia terverifikasi, dan tidak soft-deleted (default scope).
     * Visibilitas per item mengikuti verifikasi penyedia, bukan persetujuan
     * per item; tidak ada filter frontend yang menjadi kontrol akses.
     *
     * @param  Builder<CatalogItem>  $query
     * @return Builder<CatalogItem>
     */
    public function scopePublikUntukKlien(Builder $query): Builder
    {
        return $query->where('status', CatalogItemStatus::Aktif)
            ->whereHas('vendorProfile', fn (Builder $q) => $q->where('verification_status', VerificationStatus::Terverifikasi));
    }
}
