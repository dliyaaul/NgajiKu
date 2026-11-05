<?php

namespace Database\Factories;

use App\Models\PengajarPengganti;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengajarPengganti>
 */
class PengajarPenggantiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->name(),
            'no_hp' => fake()->numerify('08##########'),
            'aktif' => true,
        ];
    }
}
