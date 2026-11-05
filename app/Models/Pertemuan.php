<?php

namespace App\Models;

use App\Services\Absensi\AbsensiProgram;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Dasar pertemuan BBAQ, Tahfidz, dan Tarjamah. Ketiga tabel berstruktur sama;
 * yang berbeda hanya nama kolom pengajar pengganti dan tabel absensinya.
 *
 * @property string $id
 * @property string $program_id
 * @property ?string $kelas_pengajaran_id
 * @property Carbon $tanggal
 * @property ?string $pengajar_id
 * @property ?string $materi_pembelajaran
 * @property string $metode
 * @property ?string $created_by
 * @property ?Carbon $created_at
 */
abstract class Pertemuan extends Model
{
    use HasFactory, HasUuids;

    /**
     * Absensi & pertemuan hanya bisa diubah selama 24 jam sejak pertemuan dibuat.
     */
    public const BATAS_EDIT_JAM = 24;

    /**
     * Kolom pengajar pengganti pada tabel pertemuan.
     */
    abstract public static function penggantiColumn(): string;

    /**
     * Model absensi milik pertemuan ini.
     *
     * @return class-string<Model>
     */
    abstract public static function absensiModel(): string;

    /**
     * Kolom pada tabel absensi yang menunjuk ke pertemuan.
     */
    abstract public static function absensiForeignKey(): string;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Program, $this>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<KelasPengajaran, $this>
     */
    public function kelasPengajaran(): BelongsTo
    {
        return $this->belongsTo(KelasPengajaran::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengajar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengajar_id');
    }

    /**
     * @return BelongsTo<PengajarPengganti, $this>
     */
    public function pengganti(): BelongsTo
    {
        return $this->belongsTo(PengajarPengganti::class, static::penggantiColumn());
    }

    /**
     * @return HasMany<Model, $this>
     */
    public function absensi(): HasMany
    {
        return $this->hasMany(static::absensiModel(), static::absensiForeignKey());
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function penggantiId(): ?string
    {
        return $this->getAttribute(static::penggantiColumn());
    }

    public function batasEdit(): ?CarbonImmutable
    {
        return $this->created_at?->toImmutable()->addHours(self::BATAS_EDIT_JAM);
    }

    public function isEditable(): bool
    {
        $batas = $this->batasEdit();

        return $batas !== null && now()->lessThan($batas);
    }

    /**
     * Jumlah tiap status absensi. Muat relasi `absensi` lebih dulu agar tidak N+1.
     *
     * @return array<string, int>
     */
    public function rekap(): array
    {
        return $this->absensi
            ->countBy(fn (Model $absen) => AbsensiProgram::statusTampil($absen->status))
            ->all();
    }
}
