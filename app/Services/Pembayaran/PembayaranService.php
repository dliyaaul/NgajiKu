<?php

namespace App\Services\Pembayaran;

use App\Enums\StatusPembayaran;
use App\Enums\StatusTagihan;
use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Pengajuan dan verifikasi pembayaran tagihan. Menggantikan RLS + trigger
 * `sinkronkan_tagihan_saat_pembayaran` dan fungsi `admin_verifikasi_pembayaran` Supabase.
 */
class PembayaranService
{
    public const DISK = 'bukti_transfer';

    /**
     * Pembayar mengirim bukti transfer; tagihan menjadi "menunggu verifikasi".
     *
     * @param  array{rekening_tujuan_id: string, tanggal_transfer: string, nominal_transfer: numeric-string|int|float, nama_pengirim?: ?string, rekening_pengirim?: ?string, catatan?: ?string}  $data
     */
    public function ajukan(Tagihan $tagihan, User $pengirim, array $data, UploadedFile $bukti): Pembayaran
    {
        $path = $bukti->storeAs(
            $pengirim->id,
            now()->format('Uu').'.'.($bukti->guessExtension() ?? $bukti->extension()),
            self::DISK,
        );

        if ($path === false) {
            throw new PembayaranException('Bukti transfer gagal disimpan. Silakan coba lagi.');
        }

        try {
            return DB::transaction(function () use ($tagihan, $pengirim, $data, $path) {
                $tagihan = Tagihan::query()->lockForUpdate()->findOrFail($tagihan->id);

                if (! $tagihan->bisaDibayar()) {
                    throw new PembayaranException('Tagihan tidak tersedia untuk pembayaran baru.');
                }

                if (! $this->nominalSama($data['nominal_transfer'], $tagihan->total)) {
                    throw new PembayaranException('Nominal transfer harus sama dengan total tagihan.');
                }

                $pembayaran = $tagihan->pembayaran()->create([
                    'nomor_pembayaran' => 'BYR-'.now()->format('Uu'),
                    'rekening_tujuan_id' => $data['rekening_tujuan_id'],
                    'tanggal_transfer' => $data['tanggal_transfer'],
                    'nominal_transfer' => $data['nominal_transfer'],
                    'nama_pengirim' => $this->kosongkan($data['nama_pengirim'] ?? null),
                    'rekening_pengirim' => $this->kosongkan($data['rekening_pengirim'] ?? null),
                    'bukti_transfer' => $path,
                    'catatan' => $this->kosongkan($data['catatan'] ?? null),
                    'status' => StatusPembayaran::MenungguVerifikasi,
                    'created_by' => $pengirim->id,
                ]);

                $tagihan->update(['status' => StatusTagihan::MenungguVerifikasi]);

                $pembayaran->log()->create([
                    'status_lama' => null,
                    'status_baru' => StatusPembayaran::MenungguVerifikasi->value,
                    'catatan' => 'Pembayaran diajukan.',
                    'dilakukan_oleh' => $pengirim->id,
                ]);

                return $pembayaran;
            });
        } catch (Throwable $e) {
            Storage::disk(self::DISK)->delete($path);

            throw $e;
        }
    }

    /**
     * Admin menerima (tagihan lunas) atau menolak (tagihan bisa dibayar ulang) pembayaran.
     */
    public function verifikasi(Pembayaran $pembayaran, User $admin, StatusPembayaran $statusBaru, ?string $catatan): Pembayaran
    {
        if ($statusBaru === StatusPembayaran::MenungguVerifikasi) {
            throw new PembayaranException('Status verifikasi tidak valid.');
        }

        $alasan = $this->kosongkan($catatan);

        if ($statusBaru === StatusPembayaran::Ditolak && $alasan === null) {
            throw new PembayaranException('Alasan penolakan wajib diisi.');
        }

        return DB::transaction(function () use ($pembayaran, $admin, $statusBaru, $alasan) {
            $pembayaran = Pembayaran::query()->lockForUpdate()->findOrFail($pembayaran->id);

            if ($pembayaran->status !== StatusPembayaran::MenungguVerifikasi) {
                throw new PembayaranException('Pembayaran ini sudah diverifikasi atau tidak lagi menunggu verifikasi.');
            }

            $tagihan = Tagihan::query()->lockForUpdate()->findOrFail($pembayaran->tagihan_id);

            if ($statusBaru === StatusPembayaran::Diterima && ! $this->nominalSama($pembayaran->nominal_transfer, $tagihan->total)) {
                throw new PembayaranException('Nominal transfer tidak sesuai dengan total tagihan.');
            }

            $statusLama = $pembayaran->status;

            $pembayaran->update([
                'status' => $statusBaru,
                'diverifikasi_oleh' => $admin->id,
                'diverifikasi_at' => now(),
                'alasan_penolakan' => $statusBaru === StatusPembayaran::Ditolak ? $alasan : null,
            ]);

            $tagihan->update([
                'status' => $statusBaru === StatusPembayaran::Diterima ? StatusTagihan::Lunas : StatusTagihan::Ditolak,
            ]);

            $pembayaran->log()->create([
                'status_lama' => $statusLama->value,
                'status_baru' => $statusBaru->value,
                'catatan' => $alasan,
                'dilakukan_oleh' => $admin->id,
            ]);

            return $pembayaran;
        });
    }

    /**
     * Membandingkan nominal uang dalam satuan sen agar tidak terpengaruh pembulatan float.
     */
    private function nominalSama(mixed $a, mixed $b): bool
    {
        return (int) round(((float) $a) * 100) === (int) round(((float) $b) * 100);
    }

    private function kosongkan(?string $nilai): ?string
    {
        $nilai = trim((string) $nilai);

        return $nilai === '' ? null : $nilai;
    }
}
