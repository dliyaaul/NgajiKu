<?php

namespace App\Services\Pembayaran;

use App\Enums\JenisLayanan;
use App\Enums\StatusTagihan;
use App\Enums\TipePembayar;
use App\Enums\UserRole;
use App\Models\PembayaranTarif;
use App\Models\SantriProgram;
use App\Models\Tagihan;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Membuat tagihan bulanan untuk semua santri layanan Reguler
 * (port dari fungsi Supabase `generate_tagihan_reguler`).
 *
 * Satu santri hanya mendapat satu tagihan aktif (bukan "dibatalkan") per periode.
 */
class TagihanRegulerGenerator
{
    /**
     * Daftar santri yang akan ditagih tanpa menyimpan apa pun.
     *
     * @return array{berhasil: true, mode: 'pratinjau', tarif_id: string, nama_tarif: string, nominal_per_santri: string, periode_mulai: string, periode_selesai: string, total_siap_generate: int, total_duplikat: int, daftar: list<array{santri_program_id: string, santri_id: string, nama_santri: string, program_id: string, nama_program: ?string, nominal: string, status: 'duplikat'|'siap_generate'}>}
     */
    public function pratinjau(CarbonImmutable $mulai, CarbonImmutable $selesai): array
    {
        $tarif = $this->tarifBerlaku($mulai, $selesai);
        $peserta = $this->peserta();
        $sudahDitagih = $this->sudahDitagih($peserta, $mulai, $selesai);

        $daftar = $peserta->map(fn (SantriProgram $santriProgram) => [
            'santri_program_id' => $santriProgram->id,
            'santri_id' => $santriProgram->santri_id,
            'nama_santri' => $santriProgram->santri->username,
            'program_id' => $santriProgram->program_id,
            'nama_program' => $santriProgram->program?->nama,
            'nominal' => $tarif->nominal,
            'status' => isset($sudahDitagih[$santriProgram->id]) ? 'duplikat' : 'siap_generate',
        ])->values()->all();

        $duplikat = count(array_filter($daftar, fn (array $baris) => $baris['status'] === 'duplikat'));

        return [
            'berhasil' => true,
            'mode' => 'pratinjau',
            'tarif_id' => $tarif->id,
            'nama_tarif' => $tarif->nama_tarif,
            'nominal_per_santri' => $tarif->nominal,
            'periode_mulai' => $mulai->toDateString(),
            'periode_selesai' => $selesai->toDateString(),
            'total_siap_generate' => count($daftar) - $duplikat,
            'total_duplikat' => $duplikat,
            'daftar' => $daftar,
        ];
    }

    /**
     * Membuat tagihan + rincian untuk setiap santri yang belum ditagih pada periode ini.
     * Dikunci agar dua admin tidak menggenerate periode yang sama bersamaan.
     *
     * @return array{berhasil: true, mode: 'generate', nama_tarif: string, nominal_per_santri: string, periode_mulai: string, periode_selesai: string, total_dibuat: int, total_dilewati_duplikat: int}
     */
    public function generate(CarbonImmutable $mulai, CarbonImmutable $selesai, ?CarbonImmutable $jatuhTempo, User $admin): array
    {
        return Cache::lock('generate-tagihan-reguler', 120)->block(10, function () use ($mulai, $selesai, $jatuhTempo, $admin) {
            return DB::transaction(function () use ($mulai, $selesai, $jatuhTempo, $admin) {
                $tarif = $this->tarifBerlaku($mulai, $selesai);
                $peserta = $this->peserta();
                $sudahDitagih = $this->sudahDitagih($peserta, $mulai, $selesai);
                $dibuat = 0;

                foreach ($peserta as $santriProgram) {
                    if (isset($sudahDitagih[$santriProgram->id])) {
                        continue;
                    }

                    $tagihan = Tagihan::create([
                        'nomor_tagihan' => $this->nomorTagihan($mulai, $selesai),
                        'tipe_pembayar' => TipePembayar::Santri,
                        'santri_program_id' => $santriProgram->id,
                        'periode_mulai' => $mulai,
                        'periode_selesai' => $selesai,
                        'tanggal_tagihan' => today(),
                        'tanggal_jatuh_tempo' => $jatuhTempo,
                        'subtotal' => $tarif->nominal,
                        'diskon' => 0,
                        'total' => $tarif->nominal,
                        'status' => StatusTagihan::BelumBayar,
                        'created_by' => $admin->id,
                    ]);

                    $tagihan->items()->create([
                        'deskripsi' => $tarif->nama_tarif,
                        'jenis' => 'bulanan',
                        'referensi_id' => $tarif->id,
                        'tanggal' => $mulai,
                        'jumlah' => 1,
                        'satuan' => 'bulan',
                        'harga_satuan' => $tarif->nominal,
                        'subtotal' => $tarif->nominal,
                        'metadata' => [
                            'nama_santri' => $santriProgram->santri->username,
                            'program_id' => $santriProgram->program_id,
                            'nama_program' => $santriProgram->program?->nama,
                            'periode_mulai' => $mulai->toDateString(),
                            'periode_selesai' => $selesai->toDateString(),
                        ],
                    ]);

                    $dibuat++;
                }

                return [
                    'berhasil' => true,
                    'mode' => 'generate',
                    'nama_tarif' => $tarif->nama_tarif,
                    'nominal_per_santri' => $tarif->nominal,
                    'periode_mulai' => $mulai->toDateString(),
                    'periode_selesai' => $selesai->toDateString(),
                    'total_dibuat' => $dibuat,
                    'total_dilewati_duplikat' => count($sudahDitagih),
                ];
            });
        });
    }

    /**
     * Tepat satu tarif Reguler bulanan umum yang aktif dan berlaku pada periode.
     */
    private function tarifBerlaku(CarbonImmutable $mulai, CarbonImmutable $selesai): PembayaranTarif
    {
        $tarif = PembayaranTarif::query()
            ->whereHas('kelasType', fn ($query) => $query->berjenis(JenisLayanan::Reguler))
            ->whereNull('program_id')
            ->whereNull('santri_program_id')
            ->whereNull('lembaga_id')
            ->where('satuan', 'bulan')
            ->where('aktif', true)
            ->whereDate('berlaku_mulai', '<=', $selesai)
            ->where(fn ($query) => $query->whereNull('berlaku_sampai')->orWhereDate('berlaku_sampai', '>=', $mulai))
            ->limit(2)
            ->get();

        return match ($tarif->count()) {
            0 => throw new PembayaranException('Tarif Reguler bulanan tidak ditemukan untuk periode yang dipilih.'),
            1 => $tarif->first(),
            default => throw new PembayaranException('Ditemukan lebih dari satu tarif Reguler yang berlaku pada periode ini. Periksa data tarif terlebih dahulu.'),
        };
    }

    /**
     * Pendaftaran layanan Reguler milik akun ber-role santri, urut nama santri.
     *
     * @return Collection<int, SantriProgram>
     */
    private function peserta(): Collection
    {
        return SantriProgram::query()
            ->with(['santri:id,username', 'program:id,nama'])
            ->whereHas('kelasType', fn ($query) => $query->berjenis(JenisLayanan::Reguler))
            ->whereHas('santri', fn ($query) => $query->where('role', UserRole::Santri))
            ->get()
            ->sortBy(fn (SantriProgram $santriProgram) => $santriProgram->santri->username, SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * ID pendaftaran yang sudah punya tagihan (selain dibatalkan) pada periode yang sama.
     *
     * @param  Collection<int, SantriProgram>  $peserta
     * @return array<string, true>
     */
    private function sudahDitagih(Collection $peserta, CarbonImmutable $mulai, CarbonImmutable $selesai): array
    {
        return Tagihan::query()
            ->whereIn('santri_program_id', $peserta->modelKeys())
            ->where('tipe_pembayar', TipePembayar::Santri)
            ->whereDate('periode_mulai', $mulai)
            ->whereDate('periode_selesai', $selesai)
            ->where('status', '!=', StatusTagihan::Dibatalkan)
            ->pluck('santri_program_id')
            ->mapWithKeys(fn (string $id) => [$id => true])
            ->all();
    }

    private function nomorTagihan(CarbonImmutable $mulai, CarbonImmutable $selesai): string
    {
        return sprintf('REG-%s-%s-%s', $mulai->format('Ymd'), $selesai->format('Ymd'), Str::upper(Str::random(8)));
    }
}
