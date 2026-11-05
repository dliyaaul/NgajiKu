<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak perubahan status pembayaran (pengajuan, diterima, ditolak).
 */
#[Table('pembayaran_log')]
#[Fillable(['pembayaran_id', 'status_lama', 'status_baru', 'catatan', 'dilakukan_oleh'])]
class PembayaranLog extends Model
{
    use HasUuids;

    /**
     * @return BelongsTo<Pembayaran, $this>
     */
    public function pembayaran(): BelongsTo
    {
        return $this->belongsTo(Pembayaran::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pelaku(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dilakukan_oleh');
    }
}
