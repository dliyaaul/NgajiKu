<?php

namespace App\Services\Absensi;

use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Angka ringkas dashboard (web & API): untuk admin/pengajar dari pertemuan
 * beberapa hari terakhir, untuk santri dari riwayat absensinya.
 */
class RingkasanAbsensi
{
    /**
     * Jumlah hari ke belakang untuk ringkasan aktivitas absensi.
     */
    public const RENTANG_HARI = 7;

    public function __construct(private AbsensiService $absensi) {}

    /**
     * Data dashboard admin & pengajar: angka ringkas, absensi yang masih bisa diisi,
     * dan pertemuan terbaru.
     *
     * @return array<string, mixed>
     */
    public function untukPengelola(
        User $user,
        ?CarbonImmutable $mulai = null,
        ?CarbonImmutable $sampai = null,
        ?AbsensiProgram $filterProgram = null,
    ): array {
        $sampai ??= CarbonImmutable::today();
        $mulai ??= $sampai->subDays(29);

        $grafik = $this->absensi->getGrafikDashboard(
            $user,
            $mulai,
            $sampai,
            $filterProgram,
        );

        $data = $this->absensi->getRingkasanDashboard($user, self::RENTANG_HARI);
        $pertemuan = collect($data['pertemuan'])->map(fn(array $item): array => [
            ...$item,
            'batas_edit' => $item['batas_edit']?->toIso8601String(),
        ]);

        $jumlahStatus = array_fill_keys(AbsensiProgram::STATUSES, 0);

        foreach ($pertemuan as $item) {
            foreach ($item['rekap'] as $status => $jumlah) {
                if (isset($jumlahStatus[$status])) {
                    $jumlahStatus[$status] += $jumlah;
                }
            }
        }

        $terbuka = $pertemuan->where('editable', true)->sortBy('batas_edit')->values();

        return [
            'programs' => $data['programs'],
            'gagal' => $data['gagal'],
            'rentangHari' => self::RENTANG_HARI,
            'jumlahKelas' => array_sum(array_column($data['programs'], 'jumlah_kelas')),
            'jumlahPertemuan' => $pertemuan->count(),
            'persenHadir' => $this->persen($jumlahStatus['Hadir'], array_sum($jumlahStatus)),
            'terbuka' => $terbuka,
            'belumDiisi' => $terbuka->where('rekap', [])->count(),
            'terbaru' => $pertemuan->where('editable', false)->take(6)->values(),
            'grafikAbsensi' => $grafik,

            'filterGrafik' => [
                'mulai' => $mulai->toDateString(),
                'sampai' => $sampai->toDateString(),
                'program' => $filterProgram?->value,
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items  riwayat absensi santri (terbaru lebih dulu)
     * @return array{ringkasan: array<string, int>, persenHadir: ?int, setoranTerakhir: ?array<string, mixed>, terbaru: list<array<string, mixed>>}
     */
    public function untukSantri(array $items): array
    {
        $ringkasan = $this->hitungStatus($items);

        return [
            'ringkasan' => $ringkasan,
            'persenHadir' => $this->persen($ringkasan['Hadir'], array_sum($ringkasan)),
            'setoranTerakhir' => collect($items)->first(fn(array $item) => filled($item['surat'])),
            'terbaru' => array_slice($items, 0, 5),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, int>
     */
    public function hitungStatus(array $items): array
    {
        $ringkasan = array_fill_keys(AbsensiProgram::STATUSES, 0);

        foreach ($items as $item) {
            if (isset($ringkasan[$item['status']])) {
                $ringkasan[$item['status']]++;
            }
        }

        return $ringkasan;
    }

    private function persen(int $bagian, int $total): ?int
    {
        return $total > 0 ? (int) round($bagian / $total * 100) : null;
    }
}
