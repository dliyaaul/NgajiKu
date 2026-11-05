<?php

namespace Database\Factories;

use App\Enums\StatusPembayaran;
use App\Models\Pembayaran;
use App\Models\PembayaranRekening;
use App\Models\Tagihan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pembayaran>
 */
class PembayaranFactory extends Factory
{
    /**
     * Pembayaran yang menunggu verifikasi dengan nominal sama dengan tagihannya.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor_pembayaran' => 'BYR-'.fake()->unique()->numerify('################'),
            'tagihan_id' => Tagihan::factory(),
            'rekening_tujuan_id' => PembayaranRekening::factory(),
            'tanggal_transfer' => now()->toDateString(),
            'nominal_transfer' => fn (array $attributes) => Tagihan::find($attributes['tagihan_id'])->total,
            'nama_pengirim' => fake()->name(),
            'rekening_pengirim' => fake()->numerify('##########'),
            'bukti_transfer' => fake()->uuid().'/'.fake()->numerify('################').'.jpg',
            'status' => StatusPembayaran::MenungguVerifikasi,
        ];
    }
}
