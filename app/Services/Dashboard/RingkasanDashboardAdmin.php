<?php

namespace App\Services\Dashboard;

use App\Enums\StatusPembayaran;
use App\Enums\StatusTagihan;
use App\Enums\UserRole;
use App\Models\BbaqPertemuan;
use App\Models\KelasPengajaran;
use App\Models\Pembayaran;
use App\Models\PembayaranLog;
use App\Models\Program;
use App\Models\RiwayatKelasSantri;
use App\Models\SantriProgram;
use App\Models\TahfidzPertemuan;
use App\Models\TarjamahPertemuan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RingkasanDashboardAdmin
{
    /**
     * Mengambil seluruh data ringkasan yang dibutuhkan dashboard admin.
     *
     * @return array{
     *     akademik: array<string, int>,
     *     keuangan: array<string, array{jumlah: int, nominal: float}>,
     *     aktivitasTerbaru: array<int, array<string, mixed>>
     * }
     */
    public function ambil(): array
    {
        return [
            'akademik' => $this->ringkasanAkademik(),
            'keuangan' => $this->ringkasanKeuangan(),
            'aktivitasTerbaru' => $this->aktivitasTerbaru(),
        ];
    }

    /**
     * Statistik akademik.
     *
     * Total santri dan pengajar dihitung dari akun pengguna,
     * bukan dari jumlah pendaftaran program.
     *
     * @return array<string, int>
     */
    private function ringkasanAkademik(): array
    {
        return [
            'totalSantri' => User::query()
                ->where('role', UserRole::Santri->value)
                ->count(),

            'totalPendaftaran' => SantriProgram::query()->count(),

            'totalPengajar' => User::query()
                ->where('role', UserRole::Pengajar->value)
                ->count(),

            'totalKelasAktif' => KelasPengajaran::query()
                ->aktif()
                ->count(),

            'totalProgram' => Program::query()->count(),
        ];
    }

    /**
     * Statistik keuangan berdasarkan status terkini tagihan.
     *
     * Tagihan dibatalkan tidak dimasukkan dalam total keseluruhan.
     * Pembayaran menunggu verifikasi dihitung dari tabel pembayaran.
     *
     * @return array<string, array{jumlah: int, nominal: float}>
     */
    private function ringkasanKeuangan(): array
    {
        $tagihan = Tagihan::query()
            ->where('status', '!=', StatusTagihan::Dibatalkan->value)
            ->toBase()
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(total), 0) as nominal')
            ->first();

        $lunas = Tagihan::query()
            ->where('status', StatusTagihan::Lunas->value)
            ->toBase()
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(total), 0) as nominal')
            ->first();

        $belumDibayar = Tagihan::query()
            ->whereIn('status', [
                StatusTagihan::BelumBayar->value,
                StatusTagihan::Ditolak->value,
            ])
            ->toBase()
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(total), 0) as nominal')
            ->first();

        $menungguVerifikasi = Pembayaran::query()
            ->where('status', StatusPembayaran::MenungguVerifikasi->value)
            ->toBase()
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(nominal_transfer), 0) as nominal')
            ->first();

        return [
            'totalTagihan' => [
                'jumlah' => (int) $tagihan->jumlah,
                'nominal' => (float) $tagihan->nominal,
            ],
            'lunas' => [
                'jumlah' => (int) $lunas->jumlah,
                'nominal' => (float) $lunas->nominal,
            ],
            'belumDibayar' => [
                'jumlah' => (int) $belumDibayar->jumlah,
                'nominal' => (float) $belumDibayar->nominal,
            ],
            'menungguVerifikasi' => [
                'jumlah' => (int) $menungguVerifikasi->jumlah,
                'nominal' => (float) $menungguVerifikasi->nominal,
            ],
        ];
    }

    /**
     * Menggabungkan aktivitas dari beberapa tabel dan mengambil 8 aktivitas terbaru.
     *
     * @return array<int, array<string, mixed>>
     */
    private function aktivitasTerbaru(): array
    {
        $aktivitas = collect();

        $this->aktivitasSantriBaru($aktivitas);
        $this->aktivitasPendaftaran($aktivitas);
        $this->aktivitasPerpindahanKelas($aktivitas);
        $this->aktivitasPertemuan($aktivitas);
        $this->aktivitasVerifikasiPembayaran($aktivitas);

        return $aktivitas
            ->sortByDesc('_timestamp')
            ->take(8)
            ->map(function (array $item): array {
                unset($item['_timestamp']);

                return $item;
            })
            ->values()
            ->all();
    }

    /**
     * Aktivitas pembuatan akun santri.
     */
    private function aktivitasSantriBaru(Collection $aktivitas): void
    {
        User::query()
            ->where('role', UserRole::Santri->value)
            ->latest()
            ->limit(10)
            ->get(['id', 'username', 'created_at'])
            ->each(function (User $santri) use ($aktivitas): void {
                $this->tambahkanAktivitas(
                    $aktivitas,
                    'santri_baru',
                    'Santri baru terdaftar',
                    $santri->username,
                    $santri->created_at,
                    $santri->id,
                );
            });
    }

    /**
     * Aktivitas pendaftaran santri ke program.
     */
    private function aktivitasPendaftaran(Collection $aktivitas): void
    {
        SantriProgram::query()
            ->with([
                'santri:id,username',
                'program:id,nama',
            ])
            ->latest()
            ->limit(10)
            ->get()
            ->each(function (SantriProgram $pendaftaran) use ($aktivitas): void {
                $namaSantri = $pendaftaran->santri?->username ?? 'Santri';
                $namaProgram = $pendaftaran->program?->nama ?? 'Program';

                $this->tambahkanAktivitas(
                    $aktivitas,
                    'pendaftaran_program',
                    'Pendaftaran program baru',
                    "{$namaSantri} mendaftar program {$namaProgram}",
                    $pendaftaran->created_at,
                    $pendaftaran->id,
                );
            });
    }

    /**
     * Aktivitas perpindahan kelas santri.
     */
    private function aktivitasPerpindahanKelas(Collection $aktivitas): void
    {
        RiwayatKelasSantri::query()
            ->with([
                'santriProgram.santri:id,username',
                'kelasAsal:id,nama_kelas',
                'kelasTujuan:id,nama_kelas',
                'pengubah:id,username',
            ])
            ->latest()
            ->limit(10)
            ->get()
            ->each(function (RiwayatKelasSantri $riwayat) use ($aktivitas): void {
                $namaSantri = $riwayat->santriProgram?->santri?->username ?? 'Santri';
                $kelasAsal = $riwayat->kelasAsal?->nama_kelas ?? 'Kelas sebelumnya';
                $kelasTujuan = $riwayat->kelasTujuan?->nama_kelas ?? 'Kelas tujuan';

                $this->tambahkanAktivitas(
                    $aktivitas,
                    'perpindahan_kelas',
                    'Perpindahan kelas santri',
                    "{$namaSantri} dipindahkan dari {$kelasAsal} ke {$kelasTujuan}",
                    $riwayat->created_at,
                    $riwayat->id,
                );
            });
    }

    /**
     * Aktivitas pembuatan pertemuan dari seluruh program absensi.
     */
    private function aktivitasPertemuan(Collection $aktivitas): void
    {
        $sumber = [
            [BbaqPertemuan::class, 'Pertemuan BBAQ baru dibuat'],
            [TahfidzPertemuan::class, 'Pertemuan Tahfidz baru dibuat'],
            [TarjamahPertemuan::class, 'Pertemuan Tarjamah baru dibuat'],
        ];

        foreach ($sumber as [$model, $judul]) {
            /** @var Collection<int, Model> $pertemuan */
            $pertemuan = $model::query()
                ->with([
                    'program:id,nama',
                    'kelasPengajaran:id,nama_kelas',
                    'creator:id,username',
                ])
                ->latest()
                ->limit(10)
                ->get();

            foreach ($pertemuan as $item) {
                $namaProgram = $item->program?->nama ?? 'Program';
                $namaKelas = $item->kelasPengajaran?->nama_kelas ?? 'Kelas';
                $tanggal = $item->tanggal?->format('d-m-Y') ?? '-';

                $this->tambahkanAktivitas(
                    $aktivitas,
                    'pertemuan',
                    $judul,
                    "{$namaProgram} - {$namaKelas}, tanggal {$tanggal}",
                    $item->created_at,
                    $item->id,
                );
            }
        }
    }

    /**
     * Aktivitas pembayaran yang telah diterima atau ditolak oleh admin.
     *
     * Pengajuan pembayaran tidak dimasukkan sebagai verifikasi,
     * karena statusnya masih menunggu tindakan admin.
     */
    private function aktivitasVerifikasiPembayaran(Collection $aktivitas): void
    {
        PembayaranLog::query()
            ->with([
                'pembayaran.tagihan.santriProgram.santri:id,username',
                'pelaku:id,username',
            ])
            ->whereIn('status_baru', [
                StatusPembayaran::Diterima->value,
                StatusPembayaran::Ditolak->value,
            ])
            ->latest()
            ->limit(10)
            ->get()
            ->each(function (PembayaranLog $log) use ($aktivitas): void {
                $statusDiterima = $log->status_baru === StatusPembayaran::Diterima->value;

                $santri = $log->pembayaran?->tagihan?->santriProgram?->santri;
                $namaSantri = $santri?->username ?? 'Pembayar';

                $namaAdmin = $log->pelaku?->username ?? 'Admin';

                $this->tambahkanAktivitas(
                    $aktivitas,
                    'verifikasi_pembayaran',
                    $statusDiterima
                        ? 'Pembayaran berhasil diverifikasi'
                        : 'Pembayaran ditolak',
                    "{$namaSantri} - {$namaAdmin}",
                    $log->created_at,
                    $log->id,
                );
            });
    }

    /**
     * Menyeragamkan format aktivitas agar mudah ditampilkan di frontend.
     */
    private function tambahkanAktivitas(
        Collection $aktivitas,
        string $jenis,
        string $judul,
        string $deskripsi,
        mixed $waktu,
        string $referensiId,
    ): void {
        if ($waktu === null) {
            return;
        }

        $aktivitas->push([
            'id' => $referensiId,
            'jenis' => $jenis,
            'judul' => $judul,
            'deskripsi' => $deskripsi,
            'waktu' => $waktu->toIso8601String(),
            '_timestamp' => $waktu->timestamp,
        ]);
    }
}
