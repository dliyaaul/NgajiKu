<?php

namespace App\Models;

use Database\Factories\PengajarProgramFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pengajar yang terdaftar mengajar pada sebuah program.
 */
#[Fillable(['pengajar_id', 'program_id'])]
class PengajarProgram extends Model
{
    /** @use HasFactory<PengajarProgramFactory> */
    use HasFactory, HasUuids;

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajar_id');
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }
}
