<?php

namespace Database\Factories;

use App\Models\KelasType;
use App\Models\PembayaranTarif;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PembayaranTarif>
 */
class PembayaranTarifFactory extends Factory
{
    /**
     * Tarif Reguler bulanan umum (kelas_type bernama "Reguler").
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_tarif' => 'SPP Reguler',
            'kelas_type_id' => fn () => KelasType::firstOrCreate(['nama' => 'Reguler'])->id,
            'program_id' => null,
            'santri_program_id' => null,
            'lembaga_id' => null,
            'nominal' => 150000,
            'satuan' => 'bulan',
            'berlaku_mulai' => '2026-01-01',
            'berlaku_sampai' => null,
            'aktif' => true,
        ];
    }
}
