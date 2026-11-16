<?php

namespace Database\Factories;

use App\Enums\MediaKind;
use App\Enums\MediaVisibility;
use App\Enums\VerificationDocType;
use App\Models\CatalogItem;
use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Alias morph sesuai enforceMorphMap di AppServiceProvider.
            'owner_type' => 'catalog_item',
            'owner_id' => CatalogItem::factory(),
            'kind' => MediaKind::Foto,
            'doc_type' => null,
            'path' => 'media/'.fake()->uuid().'.jpg',
            'disk' => 'private',
            'mime_type' => 'image/jpeg',
            'size' => fake()->numberBetween(10_000, 5_000_000),
            'original_name' => 'foto.jpg',
            'visibility' => MediaVisibility::Privat,
            'sort_order' => 0,
        ];
    }

    /**
     * Media publik, dapat dibaca profil Klien.
     */
    public function publik(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => MediaVisibility::Publik,
        ]);
    }

    /**
     * Dokumen verifikasi (B14): privat selamanya, satu per jenis.
     */
    public function dokumen(VerificationDocType $docType = VerificationDocType::Ktp): static
    {
        return $this->state(fn (array $attributes) => [
            'kind' => MediaKind::DokumenVerifikasi,
            'doc_type' => $docType,
            'visibility' => MediaVisibility::Privat,
            'mime_type' => 'application/pdf',
            'original_name' => $docType->value.'.pdf',
        ]);
    }
}
