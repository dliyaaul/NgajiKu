<?php

namespace App\Services\Absensi;

use App\Models\KelasPengajaran;
use App\Models\PengajarPengganti;
use App\Models\PengajarProgram;
use App\Models\Pertemuan;
use App\Models\Program;
use App\Models\SantriProgram;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Mengelola pertemuan dan absensi program BBAQ, Tahfidz, dan Tarjamah.
 * Dipakai bersama oleh web (Inertia) dan API mobile; pengguna yang bertindak
 * selalu dikirim eksplisit sehingga service tidak bergantung pada session.
 */
class AbsensiService
{
    /**
     * @var array<string, Program>
     */
    private array $programs = [];

    // ================================================================
    // PROGRAM & KELAS
    // ================================================================

    public function program(AbsensiProgram $program): Program
    {
        return $this->programs[$program->value] ??= Program::query()
            ->where('nama', $program->programName())
            ->first()
            ?? throw new AbsensiException("Program {$program->programName()} belum terdaftar di database.");
    }

    /**
     * Kelas pengajaran aktif pada program. Pengajar hanya melihat kelasnya sendiri.
     *
     * @return Collection<int, KelasPengajaran>
     */
    public function getKelasList(AbsensiProgram $program, User $user): Collection
    {
        return KelasPengajaran::query()
            ->with('pengajar:id,username')
            ->whereBelongsTo($this->program($program))
            ->aktif()
            ->when($user->isPengajar(), fn ($query) => $query->whereBelongsTo($user, 'pengajar'))
            ->orderBy('nama_kelas')
            ->get();
    }

    /**
     * @throws ModelNotFoundException<KelasPengajaran>
     */
    public function findKelasOrFail(AbsensiProgram $program, ?string $kelasId): KelasPengajaran
    {
        return KelasPengajaran::query()
            ->with('pengajar:id,username')
            ->whereBelongsTo($this->program($program))
            ->findOrFail($kelasId);
    }

    // ================================================================
    // PERTEMUAN
    // ================================================================

    /**
     * Daftar pertemuan kelas, terbaru lebih dulu, beserta absensinya untuk rekap.
     *
     * @return Collection<int, Pertemuan>
     */
    public function getPertemuanList(AbsensiProgram $program, KelasPengajaran $kelas): Collection
    {
        return $program->pertemuanModel()::query()
            ->with(['pengajar:id,username', 'pengganti:id,nama', 'absensi:id,'.$program->pertemuanForeignKey().',status'])
            ->whereBelongsTo($kelas)
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * @throws ModelNotFoundException<Pertemuan>
     */
    public function findPertemuanOrFail(AbsensiProgram $program, string $pertemuanId): Pertemuan
    {
        return $program->pertemuanModel()::query()
            ->with(['pengajar:id,username', 'pengganti:id,nama'])
            ->whereBelongsTo($this->program($program))
            ->findOrFail($pertemuanId);
    }

    /**
     * Ringkasan aktivitas absensi untuk dashboard admin & pengajar:
     * jumlah kelas aktif per program dan pertemuan dalam beberapa hari terakhir.
     * Pengajar hanya melihat kelas yang diampunya. Program yang belum terdaftar
     * dicatat di `gagal` tanpa menggagalkan program lain.
     *
     * @return array{
     *     programs: list<array{program: AbsensiProgram, jumlah_kelas: int}>,
     *     pertemuan: list<array{program: AbsensiProgram, id: string, tanggal: string, kelas: string, kelas_id: ?string, materi: ?string, rekap: array<string, int>, editable: bool, batas_edit: ?CarbonImmutable}>,
     *     gagal: list<string>,
     *     dari: string
     * }
     */
    public function getRingkasanDashboard(User $user, int $hari = 7): array
    {
        $dari = now()->subDays($hari - 1)->toDateString();
        $programs = [];
        $pertemuanItems = [];
        $gagal = [];

        foreach (AbsensiProgram::cases() as $program) {
            try {
                $kelasList = $this->getKelasList($program, $user);
            } catch (AbsensiException) {
                $gagal[] = $program->label();

                continue;
            }

            $namaKelas = $kelasList->pluck('nama_kelas', 'id');
            $programs[] = ['program' => $program, 'jumlah_kelas' => $kelasList->count()];

            if ($user->isPengajar() && $kelasList->isEmpty()) {
                continue;
            }

            $pertemuanList = $program->pertemuanModel()::query()
                ->with('absensi:id,'.$program->pertemuanForeignKey().',status')
                ->whereDate('tanggal', '>=', $dari)
                ->when(
                    $user->isPengajar(),
                    fn ($query) => $query->whereIn('kelas_pengajaran_id', $namaKelas->keys()),
                    fn ($query) => $query->whereBelongsTo($this->program($program)),
                )
                ->orderByDesc('tanggal')
                ->orderByDesc('created_at')
                ->get();

            foreach ($pertemuanList as $pertemuan) {
                $pertemuanItems[] = [
                    'program' => $program,
                    'id' => $pertemuan->id,
                    'tanggal' => $pertemuan->tanggal->toDateString(),
                    'kelas' => $namaKelas[$pertemuan->kelas_pengajaran_id] ?? 'Kelas tidak aktif',
                    'kelas_id' => $pertemuan->kelas_pengajaran_id,
                    'materi' => $pertemuan->materi_pembelajaran,
                    'rekap' => $pertemuan->rekap(),
                    'editable' => $pertemuan->isEditable(),
                    'batas_edit' => $pertemuan->batasEdit(),
                ];
            }
        }

        usort($pertemuanItems, fn (array $a, array $b) => strcmp($b['tanggal'], $a['tanggal']));

        return [
            'programs' => $programs,
            'pertemuan' => $pertemuanItems,
            'gagal' => $gagal,
            'dari' => $dari,
        ];
    }

    /**
     * @param  array{tanggal: string, pengajar_id: ?string, pengganti_id: ?string, materi_pembelajaran: ?string, metode: string}  $data
     */
    public function createPertemuan(AbsensiProgram $program, KelasPengajaran $kelas, array $data, User $user): Pertemuan
    {
        return $program->pertemuanModel()::create($this->pertemuanPayload($program, $data) + [
            'program_id' => $this->program($program)->id,
            'kelas_pengajaran_id' => $kelas->id,
            'created_by' => $user->id,
        ]);
    }

    /**
     * @param  array{tanggal: string, pengajar_id: ?string, pengganti_id: ?string, materi_pembelajaran: ?string, metode: string}  $data
     */
    public function updatePertemuan(AbsensiProgram $program, Pertemuan $pertemuan, array $data): void
    {
        $this->ensureEditable($pertemuan);

        $this->tanpaDuplikatTahfidz(fn () => DB::transaction(function () use ($program, $pertemuan, $data) {
            $tanggalBerubah = $pertemuan->tanggal->toDateString() !== $data['tanggal'];

            $pertemuan->update($this->pertemuanPayload($program, $data));

            if ($tanggalBerubah && $program->absensiMenyimpanTanggal()) {
                $pertemuan->absensi()->update(['tanggal' => $data['tanggal']]);
            }
        }));
    }

    /**
     * Absensi pertemuan ikut terhapus (foreign key cascade).
     */
    public function deletePertemuan(Pertemuan $pertemuan): void
    {
        $this->ensureEditable($pertemuan);

        $pertemuan->delete();
    }

    /**
     * Pengajar yang terdaftar pada program, diurutkan berdasarkan nama.
     *
     * @return list<array{id: string, nama: string}>
     */
    public function getPengajarOptions(AbsensiProgram $program): array
    {
        return PengajarProgram::query()
            ->with('pengajar:id,username')
            ->whereBelongsTo($this->program($program))
            ->get()
            ->unique('pengajar_id')
            ->map(fn (PengajarProgram $row) => ['id' => $row->pengajar_id, 'nama' => $row->pengajar?->username ?? 'Tanpa nama'])
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, nama: string}>
     */
    public function getPenggantiOptions(): array
    {
        return PengajarPengganti::query()
            ->aktif()
            ->orderBy('nama')
            ->get(['id', 'nama'])
            ->map(fn (PengajarPengganti $pengganti) => ['id' => $pengganti->id, 'nama' => $pengganti->nama])
            ->all();
    }

    // ================================================================
    // BATAS WAKTU
    // ================================================================

    private function ensureEditable(Pertemuan $pertemuan): void
    {
        if (! $pertemuan->isEditable()) {
            throw new AbsensiException(
                'Batas waktu input, edit, atau hapus sudah berakhir (24 jam sejak pertemuan dibuat).'
            );
        }
    }

    // ================================================================
    // ABSENSI
    // ================================================================

    /**
     * Baris formulir absensi untuk setiap santri anggota kelas. Santri yang belum
     * diabsen otomatis berstatus "Hadir" (tetap harus disimpan), dan ejaan lama
     * "Alpa" diseragamkan menjadi "Alpha".
     *
     * @return list<array{id: string, nama: string, email: ?string, tersimpan: bool, status: string, jenis_tahfidz: ?string, surat: ?string, ayat_mulai: ?int, ayat_selesai: ?int, keterangan: ?string}>
     */
    public function getFormulirAbsensi(AbsensiProgram $program, Pertemuan $pertemuan): array
    {
        $absensi = $pertemuan->absensi()->get()->keyBy('santri_program_id');

        return $this->getSantriKelas($program, $pertemuan)
            ->map(function (SantriProgram $santri) use ($absensi): array {
                $absen = $absensi->get($santri->id);
                $status = AbsensiProgram::statusTampil($absen?->status);

                return [
                    'id' => $santri->id,
                    'nama' => $santri->santri?->username ?? 'Tanpa nama',
                    'email' => $santri->santri?->email,
                    'tersimpan' => $absen !== null,
                    'status' => $status ?? AbsensiProgram::STATUS_HADIR,
                    'jenis_tahfidz' => $absen?->jenis_tahfidz,
                    'surat' => $absen?->surat,
                    'ayat_mulai' => $absen?->ayat_mulai,
                    'ayat_selesai' => $absen?->ayat_selesai,
                    'keterangan' => $absen?->keterangan,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Menyimpan absensi satu pertemuan dalam satu transaksi.
     *
     * @param  list<array{santri_program_id: string, status: string, jenis_tahfidz?: ?string, surat?: ?string, ayat_mulai?: ?int, ayat_selesai?: ?int, keterangan?: ?string}>  $rows
     */
    public function saveAbsensi(AbsensiProgram $program, Pertemuan $pertemuan, array $rows, User $user): void
    {
        $this->ensureEditable($pertemuan);

        $anggotaKelas = $this->getSantriKelas($program, $pertemuan)->modelKeys();
        $ids = array_column($rows, 'santri_program_id');

        if (array_diff($ids, $anggotaKelas) !== [] || count($ids) !== count(array_unique($ids))) {
            throw new AbsensiException('Ada santri yang bukan anggota kelas ini. Muat ulang halaman lalu coba lagi.');
        }

        $this->tanpaDuplikatTahfidz(fn () => DB::transaction(function () use ($program, $pertemuan, $rows, $user) {
            $existing = $pertemuan->absensi()->lockForUpdate()->get()->keyBy('santri_program_id');

            foreach ($rows as $row) {
                /** @var Model|null $current */
                $current = $existing->get($row['santri_program_id']);

                if ($row['status'] === AbsensiProgram::STATUS_BELUM_ABSEN) {
                    $current?->delete();

                    continue;
                }

                if ($program->hasHafalan()) {
                    $pertemuan->absensi()->updateOrCreate(
                        ['santri_program_id' => $row['santri_program_id']],
                        $this->tahfidzPayload($program, $pertemuan, $row, $current, $user),
                    );

                    continue;
                }

                if ($current === null) {
                    $pertemuan->absensi()->create($this->insertPayload($program, $pertemuan, $row, $user));
                } elseif (AbsensiProgram::statusTampil($current->status) !== $row['status']) {
                    $current->update(['status' => $program->statusTersimpan($row['status'])]);
                }
            }
        }));
    }

    /**
     * Santri hanya boleh punya satu absensi Tahfidz per tanggal (constraint unik dari Supabase).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    private function tanpaDuplikatTahfidz(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (UniqueConstraintViolationException $e) {
            throw new AbsensiException(
                'Ada santri yang sudah memiliki absensi Tahfidz pada tanggal tersebut di pertemuan lain.',
                previous: $e,
            );
        }
    }

    /**
     * Santri anggota kelas pertemuan, urut sesuai waktu bergabung.
     *
     * @return Collection<int, SantriProgram>
     */
    private function getSantriKelas(AbsensiProgram $program, Pertemuan $pertemuan): Collection
    {
        if ($pertemuan->kelas_pengajaran_id === null) {
            throw new AbsensiException('Pertemuan belum terhubung dengan kelas pengajaran.');
        }

        return SantriProgram::query()
            ->with('santri:id,username,email')
            ->whereBelongsTo($this->program($program))
            ->where('kelas_pengajaran_id', $pertemuan->kelas_pengajaran_id)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function insertPayload(AbsensiProgram $program, Pertemuan $pertemuan, array $row, User $user): array
    {
        $payload = [
            'santri_program_id' => $row['santri_program_id'],
            'status' => $program->statusTersimpan($row['status']),
            'created_by' => $user->id,
        ];

        if ($program === AbsensiProgram::Bbaq) {
            $payload += [
                'user_id' => $user->id,
                'program' => 'BBAQ',
                'kegiatan' => 'BBAQ',
                'tanggal' => $pertemuan->tanggal->toDateString(),
            ];
        }

        return $payload;
    }

    /**
     * Data hafalan hanya disimpan untuk santri yang hadir.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function tahfidzPayload(AbsensiProgram $program, Pertemuan $pertemuan, array $row, ?Model $current, User $user): array
    {
        $hadir = $row['status'] === AbsensiProgram::STATUS_HADIR;

        return [
            'tanggal' => $pertemuan->tanggal->toDateString(),
            'status' => $program->statusTersimpan($row['status']),
            'jenis_tahfidz' => $hadir ? ($row['jenis_tahfidz'] ?? null) : null,
            'surat' => $hadir ? ($row['surat'] ?? null) : null,
            'ayat_mulai' => $hadir && isset($row['ayat_mulai']) ? (int) $row['ayat_mulai'] : null,
            'ayat_selesai' => $hadir && isset($row['ayat_selesai']) ? (int) $row['ayat_selesai'] : null,
            'keterangan' => $row['keterangan'] ?? null,
            'created_by' => $current?->created_by ?? $user->id,
        ];
    }

    /**
     * @param  array{tanggal: string, pengajar_id: ?string, pengganti_id: ?string, materi_pembelajaran: ?string, metode: string}  $data
     * @return array<string, mixed>
     */
    private function pertemuanPayload(AbsensiProgram $program, array $data): array
    {
        return [
            'tanggal' => $data['tanggal'],
            'pengajar_id' => $data['pengajar_id'] ?? null,
            $program->penggantiColumn() => $data['pengganti_id'] ?? null,
            'materi_pembelajaran' => $data['materi_pembelajaran'] ?? null,
            'metode' => $data['metode'],
        ];
    }

    // ================================================================
    // RIWAYAT SANTRI
    // ================================================================

    /**
     * Riwayat absensi seorang santri dari ketiga program, terbaru lebih dulu.
     *
     * @return array{items: list<array<string, mixed>>, gagal: list<string>}
     */
    public function getRiwayatSantri(User $santri, ?AbsensiProgram $filter = null): array
    {
        $santriProgramIds = $santri->santriPrograms()->pluck('id');

        if ($santriProgramIds->isEmpty()) {
            return ['items' => [], 'gagal' => []];
        }

        $items = [];

        foreach ($filter ? [$filter] : AbsensiProgram::cases() as $program) {
            $foreignKey = $program->pertemuanForeignKey();

            $rows = $program->absensiModel()::query()
                ->with('pertemuan.pengajar:id,username')
                ->whereIn('santri_program_id', $santriProgramIds)
                // Absensi kegiatan PTL di tabel `absensi` tidak punya pertemuan.
                ->when($program === AbsensiProgram::Bbaq, fn ($query) => $query->whereNotNull($foreignKey))
                ->get();

            foreach ($rows as $row) {
                $pertemuan = $row->pertemuan;

                $items[] = [
                    'program' => $program,
                    'tanggal' => ($pertemuan?->tanggal ?? $row->tanggal)?->toDateString(),
                    'status' => AbsensiProgram::statusTampil($row->status) ?? '-',
                    'materi' => $pertemuan?->materi_pembelajaran,
                    'metode' => $pertemuan?->metode,
                    'pengajar' => $pertemuan?->pengajar?->username,
                    'jenis_tahfidz' => $row->jenis_tahfidz,
                    'surat' => $row->surat,
                    'ayat_mulai' => $row->ayat_mulai,
                    'ayat_selesai' => $row->ayat_selesai,
                    'keterangan' => $row->keterangan,
                ];
            }
        }

        usort($items, fn (array $a, array $b) => strcmp((string) $b['tanggal'], (string) $a['tanggal']));

        return ['items' => $items, 'gagal' => []];
    }
}
