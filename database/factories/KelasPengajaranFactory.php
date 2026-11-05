<?php

namespace Database\Factories;

use App\Models\KelasPengajaran;
use App\Models\KelasType;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KelasPengajaran>
 */
class KelasPengajaranFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'program_id' => Program::factory(),
            'kelas_type_id' => KelasType::factory(),
            'nama_kelas' => 'Kelas '.fake()->unique()->firstName(),
            'pengajar_id' => User::factory()->pengajar(),
            'aktif' => true,
        ];
    }
}
