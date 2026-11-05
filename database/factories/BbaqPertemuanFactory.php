<?php

namespace Database\Factories;

use App\Models\BbaqPertemuan;
use App\Models\KelasPengajaran;
use App\Models\Pertemuan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory pertemuan BBAQ; juga menjadi dasar factory Tahfidz & Tarjamah.
 *
 * @template TModel of Pertemuan
 *
 * @extends Factory<TModel>
 */
class BbaqPertemuanFactory extends Factory
{
    /**
     * @var class-string<Pertemuan>
     */
    protected $model = BbaqPertemuan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kelas_pengajaran_id' => KelasPengajaran::factory(),
            'program_id' => fn (array $attributes) => KelasPengajaran::find($attributes['kelas_pengajaran_id'])->program_id,
            'pengajar_id' => fn (array $attributes) => KelasPengajaran::find($attributes['kelas_pengajaran_id'])->pengajar_id,
            'tanggal' => now()->toDateString(),
            'materi_pembelajaran' => fake()->sentence(3),
            'metode' => 'Offline',
        ];
    }

    /**
     * Pertemuan untuk kelas tertentu (program & pengajar mengikuti kelas).
     */
    public function untukKelas(KelasPengajaran $kelas): static
    {
        return $this->state(fn (array $attributes) => [
            'kelas_pengajaran_id' => $kelas->id,
            'program_id' => $kelas->program_id,
            'pengajar_id' => $kelas->pengajar_id,
        ]);
    }

    /**
     * Pertemuan yang dibuat beberapa jam lalu (untuk menguji batas edit 24 jam).
     */
    public function dibuatJamLalu(int $jam): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => now()->subHours($jam),
            'updated_at' => now()->subHours($jam),
        ]);
    }
}
