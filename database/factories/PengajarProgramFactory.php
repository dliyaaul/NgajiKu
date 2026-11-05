<?php

namespace Database\Factories;

use App\Models\PengajarProgram;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PengajarProgram>
 */
class PengajarProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pengajar_id' => User::factory()->pengajar(),
            'program_id' => Program::factory(),
        ];
    }
}
