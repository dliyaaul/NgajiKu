<?php

namespace Database\Factories;

use App\Models\Lembaga;
use App\Models\Program;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lembaga>
 */
class LembagaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_lembaga' => 'Lembaga '.fake()->company(),
            'pbm_type' => fake()->randomElement(['Offline', 'Online']),
            'alamat' => fake()->address(),
            'program_id' => Program::factory(),
        ];
    }
}
