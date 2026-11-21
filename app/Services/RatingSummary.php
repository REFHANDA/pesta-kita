<?php

namespace App\Services;

use App\Models\EvaluationItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan rating per subjek (Tahap C5). Rata-rata dihitung di server,
 * satu angka desimal, `null` bila belum ada penilaian. Rating hanya
 * informasi dan tidak memengaruhi pencairan atau status penyedia.
 */
class RatingSummary
{
    /**
     * Rata-rata dan jumlah penilaian satu subjek.
     *
     * @return array{average: float|null, count: int}
     */
    public function forSubject(string $targetType, ?int $targetId = null): array
    {
        $query = EvaluationItem::query()->where('target_type', $targetType);

        if ($targetId === null) {
            $query->whereNull('target_id');
        } else {
            $query->where('target_id', $targetId);
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            return ['average' => null, 'count' => 0];
        }

        $average = (float) (clone $query)->avg('rating');

        return ['average' => round($average, 1), 'count' => $count];
    }

    /**
     * Rating Admin (target_type admin, tanpa target_id).
     *
     * @return array{average: float|null, count: int}
     */
    public function forAdmin(): array
    {
        return $this->forSubject('admin');
    }

    /**
     * Rating sekumpulan vendor sekaligus (untuk katalog dan rekomendasi).
     *
     * @param  array<int, int>  $vendorIds
     * @return array<int, array{average: float|null, count: int}>
     */
    public function forVendors(array $vendorIds): array
    {
        return $this->forTargets('vendor', $vendorIds);
    }

    /**
     * Rating sekumpulan venue sekaligus.
     *
     * @param  array<int, int>  $venueIds
     * @return array<int, array{average: float|null, count: int}>
     */
    public function forVenues(array $venueIds): array
    {
        return $this->forTargets('venue', $venueIds);
    }

    /**
     * Penilaian terbaru milik satu penyedia, tanpa nama/komentar klien lain.
     * Penyedia hanya menerima baris miliknya sendiri.
     *
     * @return Collection<int, EvaluationItem>
     */
    public function recentForProvider(string $targetType, int $targetId, int $limit = 10): Collection
    {
        return EvaluationItem::query()
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->with('evaluation.event')
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Agregasi rating untuk banyak target dalam satu query.
     *
     * @param  array<int, int>  $targetIds
     * @return array<int, array{average: float|null, count: int}>
     */
    protected function forTargets(string $targetType, array $targetIds): array
    {
        $ids = array_values(array_unique(array_map('intval', $targetIds)));
        $result = [];

        foreach ($ids as $id) {
            $result[$id] = ['average' => null, 'count' => 0];
        }

        if ($ids === []) {
            return $result;
        }

        $rows = EvaluationItem::query()
            ->where('target_type', $targetType)
            ->whereIn('target_id', $ids)
            ->groupBy('target_id')
            ->select('target_id', DB::raw('COUNT(*) as total'), DB::raw('AVG(rating) as rata_rata'))
            ->get();

        foreach ($rows as $row) {
            $result[(int) $row->target_id] = [
                'average' => round((float) $row->rata_rata, 1),
                'count' => (int) $row->total,
            ];
        }

        return $result;
    }
}
