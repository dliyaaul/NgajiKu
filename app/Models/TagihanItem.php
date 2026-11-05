<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('tagihan_item')]
#[Fillable(['tagihan_id', 'deskripsi', 'jenis', 'referensi_id', 'tanggal', 'jumlah', 'satuan', 'harga_satuan', 'subtotal', 'metadata'])]
class TagihanItem extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'jumlah' => 'decimal:2',
            'harga_satuan' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Tagihan, $this>
     */
    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }
}
