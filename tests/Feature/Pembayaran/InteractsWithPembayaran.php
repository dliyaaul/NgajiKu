<?php

namespace Tests\Feature\Pembayaran;

use App\Enums\UserRole;
use App\Models\KelasType;
use App\Models\Lembaga;
use App\Models\SantriProgram;
use App\Models\User;

/**
 * Data uji tagihan & pembayaran: santri layanan Reguler dan koordinator lembaga.
 */
trait InteractsWithPembayaran
{
    protected function jenisLayanan(string $nama): KelasType
    {
        return KelasType::firstOrCreate(['nama' => $nama]);
    }

    protected function santriReguler(?string $nama = null, ?User $santri = null): SantriProgram
    {
        $santri ??= User::factory()->santri()->create($nama !== null ? ['username' => $nama] : []);

        return SantriProgram::factory()->create([
            'santri_id' => $santri->id,
            'kelas_type_id' => $this->jenisLayanan('Reguler')->id,
        ]);
    }

    protected function koordinator(Lembaga $lembaga): User
    {
        return User::factory()->role(UserRole::KoordinatorLembaga)->create(['lembaga_id' => $lembaga->id]);
    }
}
