<?php

namespace Database\Factories;

use App\Models\KelasType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KelasType>
 */
class KelasTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->words(2, true),
        ];
    }
}
