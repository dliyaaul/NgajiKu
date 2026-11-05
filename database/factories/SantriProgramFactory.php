<?php

namespace Database\Factories;

use App\Models\KelasType;
use App\Models\Program;
use App\Models\SantriProgram;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SantriProgram>
 */
class SantriProgramFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'santri_id' => User::factory()->santri(),
            'program_id' => Program::factory(),
            'kelas_type_id' => KelasType::factory(),
            'kelas_pengajaran_id' => null,
            'default_pbm' => 'Offline',
        ];
    }
}
