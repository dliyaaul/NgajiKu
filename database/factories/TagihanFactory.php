<?php

namespace Database\Factories;

use App\Enums\StatusTagihan;
use App\Enums\TipePembayar;
use App\Models\Lembaga;
use App\Models\SantriProgram;
use App\Models\Tagihan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tagihan>
 */
class TagihanFactory extends Factory
{
    /**
     * Tagihan santri bulan berjalan sebesar Rp150.000.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nomor_tagihan' => 'REG-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
            'tipe_pembayar' => TipePembayar::Santri,
            'santri_program_id' => SantriProgram::factory(),
            'lembaga_id' => null,
            'periode_mulai' => now()->startOfMonth()->toDateString(),
            'periode_selesai' => now()->endOfMonth()->toDateString(),
            'tanggal_tagihan' => now()->toDateString(),
            'tanggal_jatuh_tempo' => now()->startOfMonth()->addDays(9)->toDateString(),
            'subtotal' => 150000,
            'diskon' => 0,
            'total' => 150000,
            'status' => StatusTagihan::BelumBayar,
        ];
    }

    public function status(StatusTagihan $status): static
    {
        return $this->state(fn (array $attributes) => ['status' => $status]);
    }

    public function untukSantri(SantriProgram $santriProgram): static
    {
        return $this->state(fn (array $attributes) => ['santri_program_id' => $santriProgram->id]);
    }

    public function untukLembaga(Lembaga $lembaga): static
    {
        return $this->state(fn (array $attributes) => [
            'tipe_pembayar' => TipePembayar::KoordinatorLembaga,
            'santri_program_id' => null,
            'lembaga_id' => $lembaga->id,
        ]);
    }
}
