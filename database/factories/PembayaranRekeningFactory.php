<?php

namespace Database\Factories;

use App\Models\PembayaranRekening;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PembayaranRekening>
 */
class PembayaranRekeningFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_bank' => fake()->randomElement(['BSI', 'BRI', 'Mandiri', 'BCA']),
            'nomor_rekening' => fake()->numerify('##########'),
            'nama_pemilik' => 'Yayasan NgajiKu',
            'keterangan' => null,
            'aktif' => true,
            'urutan' => 0,
        ];
    }
}
