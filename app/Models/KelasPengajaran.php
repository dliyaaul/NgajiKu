<?php

namespace App\Models;

use App\Services\Absensi\AbsensiProgram;
use Database\Factories\KelasPengajaranFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Table('kelas_pengajaran')]
#[Fillable(['program_id', 'kelas_type_id', 'nama_kelas', 'pengajar_id', 'lembaga_id', 'aktif', 'created_by'])]
class KelasPengajaran extends Model
{
    /** @use HasFactory<KelasPengajaranFactory> */
    use HasFactory, HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
        ];
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function aktif(Builder $query): void
    {
        $query->where('aktif', true);
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<KelasType, $this>
     */
    public function kelasType(): BelongsTo
    {
        return $this->belongsTo(KelasType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajar_id');
    }

    /**
     * @return BelongsTo<Lembaga, $this>
     */
    public function lembaga(): BelongsTo
    {
        return $this->belongsTo(Lembaga::class);
    }

    /**
     * Kelas yang sudah punya santri atau pertemuan tidak boleh berpindah program
     * (foreign key komposit kelas + program pada pendaftaran & pertemuan).
     */
    public function sudahDipakai(): bool
    {
        return $this->santriPrograms()->exists()
            || collect(AbsensiProgram::cases())->contains(
                fn(AbsensiProgram $program) => $program->pertemuanModel()::query()->where('kelas_pengajaran_id', $this->id)->exists(),
            );
    }

    /**
     * Santri anggota kelas.
     *
     * @return HasMany<SantriProgram, $this>
     */
    public function santriPrograms(): HasMany
    {
        return $this->hasMany(SantriProgram::class);
    }
}
