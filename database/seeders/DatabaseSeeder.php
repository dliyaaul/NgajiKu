<?php

namespace Database\Seeders;

use App\Models\KelasType;
use App\Models\Program;
use App\Models\User;
use App\Services\Absensi\AbsensiProgram;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Data awal untuk pengembangan lokal. Data produksi diambil dari Supabase
     * dengan `php artisan supabase:import`.
     */
    public function run(): void
    {
        foreach (AbsensiProgram::cases() as $program) {
            Program::firstOrCreate(['nama' => $program->programName()]);
        }

        foreach (['Reguler', 'Privat'] as $nama) {
            KelasType::firstOrCreate(['nama' => $nama]);
        }

        User::factory()->admin()->create([
            'username' => 'Admin NgajiKu',
            'email' => 'admin@example.com',
        ]);
    }
}
