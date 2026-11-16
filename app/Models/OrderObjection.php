<?php

namespace App\Models;

use Database\Factories\OrderObjectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Keberatan penyedia atas item pesanan. Menjadi catatan untuk Admin dan
 * tidak mengubah status pesanan otomatis. Nilai status/admin_decision
 * belum menjadi himpunan tertutup pada rancangan, sehingga tetap string.
 */
#[Fillable(['order_item_id', 'provider_user_id', 'reason', 'status', 'admin_decision', 'decided_at'])]
class OrderObjection extends Model
{
    /** @use HasFactory<OrderObjectionFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    /**
     * Item pesanan yang disengketakan.
     *
     * @return BelongsTo<OrderItem, $this>
     */
    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /**
     * Akun penyedia pengaju keberatan.
     *
     * @return BelongsTo<User, $this>
     */
    public function providerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_user_id');
    }
}
