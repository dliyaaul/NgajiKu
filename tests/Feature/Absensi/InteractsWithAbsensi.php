<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\KelasPengajaran;
use App\Models\Pertemuan;
use App\Models\Program;
use App\Models\SantriProgram;
use App\Models\User;
use App\Services\Absensi\AbsensiProgram;

/**
 * Data uji absensi: program, kelas milik pengajar, santri anggota kelas, dan pertemuan.
 */
trait InteractsWithAbsensi
{
    protected function programDb(AbsensiProgram $program): Program
    {
        return Program::firstOrCreate(['nama' => $program->programName()]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function kelasUntuk(AbsensiProgram $program, User $pengajar, array $attributes = []): KelasPengajaran
    {
        return KelasPengajaran::factory()
            ->for($this->programDb($program))
            ->create(['pengajar_id' => $pengajar->id, 'nama_kelas' => 'Kelas Al-Fatih', ...$attributes]);
    }

    protected function santriDi(KelasPengajaran $kelas, string $nama): SantriProgram
    {
        return SantriProgram::factory()->create([
            'santri_id' => User::factory()->santri()->create(['username' => $nama])->id,
            'program_id' => $kelas->program_id,
            'kelas_type_id' => $kelas->kelas_type_id,
            'kelas_pengajaran_id' => $kelas->id,
        ]);
    }

    /**
     * Absensi BBAQ seperti yang dibuat aplikasi mobile (user_id = penginput).
     */
    protected function absenBbaq(Pertemuan $pertemuan, SantriProgram $santri, string $status = 'Hadir'): Absensi
    {
        return Absensi::create([
            'bbaq_pertemuan_id' => $pertemuan->id,
            'santri_program_id' => $santri->id,
            'user_id' => $pertemuan->pengajar_id,
            'created_by' => $pertemuan->pengajar_id,
            'program' => 'BBAQ',
            'kegiatan' => 'BBAQ',
            'tanggal' => $pertemuan->tanggal,
            'status' => $status,
        ]);
    }

    /**
     * Santri ikut BBAQ (Hadir 1 Okt), Tarjamah (Izin 2 Okt), Tahfidz (Hadir 3 Okt + setoran An-Naba),
     * semuanya diajar oleh pengajar yang sama.
     */
    protected function riwayatTigaProgram(User $santri, User $pengajar): void
    {
        $data = [
            [AbsensiProgram::Bbaq, '2026-10-01', ['status' => 'Hadir']],
            [AbsensiProgram::Tarjamah, '2026-10-02', ['status' => 'Izin']],
            [AbsensiProgram::Tahfidz, '2026-10-03', ['status' => 'Hadir', 'jenis_tahfidz' => 'Ziyadah', 'surat' => 'An-Naba', 'ayat_mulai' => 1, 'ayat_selesai' => 16]],
        ];

        foreach ($data as [$program, $tanggal, $absen]) {
            $kelas = $this->kelasUntuk($program, $pengajar, ['nama_kelas' => 'Kelas '.$program->label()]);
            $santriProgram = SantriProgram::factory()->create([
                'santri_id' => $santri->id,
                'program_id' => $kelas->program_id,
                'kelas_type_id' => $kelas->kelas_type_id,
                'kelas_pengajaran_id' => $kelas->id,
            ]);
            $pertemuan = $this->pertemuanDi($program, $kelas, 1, ['tanggal' => $tanggal, 'materi_pembelajaran' => 'Materi '.$program->label()]);

            if ($program === AbsensiProgram::Bbaq) {
                $this->absenBbaq($pertemuan, $santriProgram, $absen['status']);

                continue;
            }

            $pertemuan->absensi()->create([
                'santri_program_id' => $santriProgram->id,
                ...($program->absensiMenyimpanTanggal() ? ['tanggal' => $tanggal] : []),
                ...$absen,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function pertemuanDi(AbsensiProgram $program, KelasPengajaran $kelas, int $dibuatJamLalu = 1, array $attributes = []): Pertemuan
    {
        return $program->pertemuanModel()::factory()
            ->untukKelas($kelas)
            ->dibuatJamLalu($dibuatJamLalu)
            ->create($attributes);
    }
}
