<?php

namespace App\Services\Absensi;

use App\Models\KelasPengajaran;
use App\Models\SantriProgram;
use App\Models\User;
use Carbon\CarbonImmutable;

class RekapAbsensiService
{
    public function __construct(
        private AbsensiService $absensi
    ) {}

    public function ambil(
        User $user,
        CarbonImmutable $mulai,
        CarbonImmutable $sampai,
        ?AbsensiProgram $filterProgram = null,
        ?string $kelasId = null,
    ): array {
        $pertemuan = collect();
        $santri = collect();

        $programList = $filterProgram
            ? [$filterProgram]
            : AbsensiProgram::cases();

        foreach ($programList as $program) {
            try {
                $programModel = $this->absensi->program($program);
            } catch (AbsensiException) {
                continue;
            }

            $kelasList = KelasPengajaran::query()
                ->whereBelongsTo($programModel)
                ->when(
                    $user->isPengajar(),
                    fn($query) => $query->where('pengajar_id', $user->id)
                )
                ->when(
                    $kelasId,
                    fn($query) => $query->whereKey($kelasId)
                )
                ->get();

            if ($kelasList->isEmpty()) {
                continue;
            }

            $kelasIds = $kelasList->modelKeys();

            // Ambil anggota kelas.
            $anggota = SantriProgram::query()
                ->with('santri:id,username')
                ->whereBelongsTo($programModel)
                ->whereIn('kelas_pengajaran_id', $kelasIds)
                ->get();

            foreach ($anggota as $item) {
                $key = $program->value . ':' . $item->id;

                $santri->put($key, [
                    'id' => $item->id,
                    'santri_id' => $item->santri_id,
                    'nama' => $item->santri?->username ?? 'Tanpa nama',
                    'program' => $program->value,
                    'kelas' => $kelasList->firstWhere(
                        'id',
                        $item->kelas_pengajaran_id
                    )?->nama_kelas ?? '-',
                    'kelas_id' => $item->kelas_pengajaran_id,
                    'status' => [],
                    'hadir' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'alpha' => 0,
                    'total_pertemuan' => 0,
                    'persen_hadir' => null,
                ]);
            }

            // Ambil pertemuan sesuai periode.
            $pertemuanList = $program->pertemuanModel()::query()
                ->with('absensi')
                ->whereBetween('tanggal', [
                    $mulai->toDateString(),
                    $sampai->toDateString(),
                ])
                ->whereIn('kelas_pengajaran_id', $kelasIds)
                ->orderBy('tanggal')
                ->orderBy('created_at')
                ->get();

            foreach ($pertemuanList as $item) {
                $pertemuanKey = $program->value . ':' . $item->id;

                $pertemuan->put($pertemuanKey, [
                    'id' => $item->id,
                    'program' => $program->value,
                    'tanggal' => $item->tanggal->toDateString(),
                    'kelas_id' => $item->kelas_pengajaran_id,
                    'kelas' => $kelasList->firstWhere(
                        'id',
                        $item->kelas_pengajaran_id
                    )?->nama_kelas ?? '-',
                    'materi' => $item->materi_pembelajaran,
                    'rekap' => [
                        'Hadir' => 0,
                        'Izin' => 0,
                        'Sakit' => 0,
                        'Alpha' => 0,
                    ],
                ]);

                // Semua anggota kelas otomatis Hadir.
                foreach ($anggota as $anggotaKelas) {
                    if (
                        $anggotaKelas->kelas_pengajaran_id
                        !== $item->kelas_pengajaran_id
                    ) {
                        continue;
                    }

                    $santriKey = $program->value . ':' . $anggotaKelas->id;

                    $santri[$santriKey]['status'][$pertemuanKey] = 'Hadir';
                    $santri[$santriKey]['hadir']++;
                    $santri[$santriKey]['total_pertemuan']++;

                    $pertemuan[$pertemuanKey]['rekap']['Hadir']++;
                }

                // Status yang tersimpan menggantikan status default Hadir.
                foreach ($item->absensi as $absen) {
                    $santriKey = $program->value . ':' . $absen->santri_program_id;

                    if (! $santri->has($santriKey)) {
                        continue;
                    }

                    $status = AbsensiProgram::statusTampil($absen->status);

                    if (! in_array($status, AbsensiProgram::STATUSES, true)) {
                        continue;
                    }

                    $statusLama = $santri[$santriKey]['status'][$pertemuanKey]
                        ?? null;

                    if ($statusLama === null) {
                        continue;
                    }

                    // Kurangi status sebelumnya.
                    $kolomLama = match ($statusLama) {
                        'Hadir' => 'hadir',
                        'Izin' => 'izin',
                        'Sakit' => 'sakit',
                        'Alpha' => 'alpha',
                    };

                    $santri[$santriKey][$kolomLama]--;

                    $pertemuan[$pertemuanKey]['rekap'][$statusLama]--;

                    // Tambahkan status baru.
                    $kolomBaru = match ($status) {
                        'Hadir' => 'hadir',
                        'Izin' => 'izin',
                        'Sakit' => 'sakit',
                        'Alpha' => 'alpha',
                    };

                    $santri[$santriKey]['status'][$pertemuanKey] = $status;
                    $santri[$santriKey][$kolomBaru]++;

                    $pertemuan[$pertemuanKey]['rekap'][$status]++;
                }
            }
        }

        // Hitung persentase kehadiran per santri.
        $totalHadir = 0;
        $totalAbsensi = 0;

        foreach ($santri as &$item) {
            $tercatat = $item['hadir']
                + $item['izin']
                + $item['sakit']
                + $item['alpha'];

            $item['persen_hadir'] = $tercatat > 0
                ? (int) round($item['hadir'] / $tercatat * 100)
                : null;

            $totalHadir += $item['hadir'];
            $totalAbsensi += $tercatat;
        }

        unset($item);

        // Ringkasan seluruh status.
        $jumlahStatus = [
            'Hadir' => 0,
            'Izin' => 0,
            'Sakit' => 0,
            'Alpha' => 0,
        ];

        foreach ($santri as $item) {
            $jumlahStatus['Hadir'] += $item['hadir'];
            $jumlahStatus['Izin'] += $item['izin'];
            $jumlahStatus['Sakit'] += $item['sakit'];
            $jumlahStatus['Alpha'] += $item['alpha'];
        }

        return [
            'filter' => [
                'mulai' => $mulai->toDateString(),
                'sampai' => $sampai->toDateString(),
                'program' => $filterProgram?->value,
                'kelas' => $kelasId,
            ],

            'pertemuan' => $pertemuan->values()->all(),

            'santri' => $santri
                ->sortBy('nama')
                ->values()
                ->all(),

            'ringkasan' => [
                'jumlah_santri' => $santri->count(),
                'jumlah_pertemuan' => $pertemuan->count(),
                'hadir' => $jumlahStatus['Hadir'],
                'izin' => $jumlahStatus['Izin'],
                'sakit' => $jumlahStatus['Sakit'],
                'alpha' => $jumlahStatus['Alpha'],
                'persen_hadir' => $totalAbsensi > 0
                    ? (int) round($totalHadir / $totalAbsensi * 100)
                    : null,
            ],
        ];
    }
}
