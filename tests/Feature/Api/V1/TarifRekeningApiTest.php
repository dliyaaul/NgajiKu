<?php

namespace Tests\Feature\Api\V1;

use App\Models\Lembaga;
use App\Models\PembayaranRekening;
use App\Models\PembayaranTarif;
use App\Models\Program;
use App\Models\SantriProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Pembayaran\InteractsWithPembayaran;
use Tests\TestCase;

class TarifRekeningApiTest extends TestCase
{
    use InteractsWithPembayaran, RefreshDatabase;

    public function test_payers_see_active_accounts_in_configured_order(): void
    {
        PembayaranRekening::factory()->create(['nama_bank' => 'BRI', 'urutan' => 2]);
        PembayaranRekening::factory()->create(['nama_bank' => 'BSI', 'urutan' => 1]);
        PembayaranRekening::factory()->create(['nama_bank' => 'Lama', 'aktif' => false]);
        Sanctum::actingAs(User::factory()->santri()->create());

        $this->getJson(route('api.v1.rekening.index', ['semua' => 1]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nama_bank', 'BSI')
            ->assertJsonPath('data.1.nama_bank', 'BRI');
    }

    public function test_admin_manages_accounts(): void
    {
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $id = $this->postJson(route('api.v1.rekening.store'), ['nama_bank' => 'BSI', 'nomor_rekening' => '7123456789', 'nama_pemilik' => 'Yayasan NgajiKu', 'urutan' => 1])
            ->assertCreated()
            ->assertJsonPath('data.aktif', true)
            ->json('data.id');

        $this->putJson(route('api.v1.rekening.update', $id), ['nama_bank' => 'BSI Syariah', 'nomor_rekening' => '7123456789', 'nama_pemilik' => 'Yayasan NgajiKu'])
            ->assertOk()
            ->assertJsonPath('data.nama_bank', 'BSI Syariah');

        $this->patchJson(route('api.v1.rekening.aktif', $id), ['aktif' => false])->assertJsonPath('data.aktif', false);

        $this->getJson(route('api.v1.rekening.index', ['semua' => 1]))->assertJsonCount(1, 'data');
        $this->assertSame($admin->id, PembayaranRekening::sole()->created_by);

        Sanctum::actingAs(User::factory()->santri()->create());
        $this->postJson(route('api.v1.rekening.store'), [])->assertForbidden();
    }

    public function test_reguler_tariff_is_general_and_monthly(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $reguler = $this->jenisLayanan('Reguler');

        $this->postJson(route('api.v1.tarif.store'), [
            'nama_tarif' => 'SPP',
            'kelas_type_id' => $reguler->id,
            'program_id' => Program::factory()->create()->id,
            'nominal' => 150000,
            'satuan' => 'pertemuan',
            'berlaku_mulai' => '2026-01-01',
        ])->assertJsonValidationErrors(['program_id', 'satuan']);

        $this->postJson(route('api.v1.tarif.store'), [
            'nama_tarif' => 'SPP',
            'kelas_type_id' => $reguler->id,
            'nominal' => 150000,
            'satuan' => 'bulan',
            'berlaku_mulai' => '2026-01-01',
        ])
            ->assertCreated()
            ->assertJsonPath('data.jenis_layanan', 'reguler');
    }

    public function test_privat_tariff_targets_one_santri(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $privat = $this->jenisLayanan('Privat');
        $santri = SantriProgram::factory()->create(['kelas_type_id' => $privat->id]);
        $data = ['nama_tarif' => 'Privat', 'kelas_type_id' => $privat->id, 'nominal' => 50000, 'satuan' => 'pertemuan', 'berlaku_mulai' => '2026-01-01'];

        $this->postJson(route('api.v1.tarif.store'), $data)
            ->assertJsonValidationErrors(['santri_program_id' => 'Santri wajib dipilih untuk tarif Privat.']);

        $this->postJson(route('api.v1.tarif.store'), [...$data, 'santri_program_id' => $santri->id])->assertCreated();
    }

    public function test_lembaga_tariff_needs_lembaga_and_its_program(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $lembaga = Lembaga::factory()->create();
        $data = [
            'nama_tarif' => 'Lembaga',
            'kelas_type_id' => $this->jenisLayanan('Lembaga')->id,
            'lembaga_id' => $lembaga->id,
            'nominal' => 300000,
            'satuan' => 'pertemuan',
            'berlaku_mulai' => '2026-01-01',
        ];

        $this->postJson(route('api.v1.tarif.store'), [...$data, 'program_id' => Program::factory()->create()->id])
            ->assertJsonValidationErrors(['program_id' => 'Program harus sama dengan program lembaga.']);

        $this->postJson(route('api.v1.tarif.store'), [...$data, 'program_id' => $lembaga->program_id])->assertCreated();
    }

    public function test_tariff_period_and_toggle(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        $tarif = PembayaranTarif::factory()->create();

        $this->putJson(route('api.v1.tarif.update', $tarif), [
            'nama_tarif' => 'SPP',
            'kelas_type_id' => $tarif->kelas_type_id,
            'nominal' => 150000,
            'satuan' => 'bulan',
            'berlaku_mulai' => '2026-06-01',
            'berlaku_sampai' => '2026-01-01',
        ])->assertJsonValidationErrors('berlaku_sampai');

        $this->patchJson(route('api.v1.tarif.aktif', $tarif), ['aktif' => false])->assertJsonPath('data.aktif', false);
        $this->getJson(route('api.v1.tarif.index', ['aktif' => 1]))->assertJsonCount(0, 'data');
    }

    public function test_tariff_form_options_list_privat_santri(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());
        SantriProgram::factory()->create(['kelas_type_id' => $this->jenisLayanan('Privat')->id]);
        $this->santriReguler();

        $this->getJson(route('api.v1.tarif.opsi'))
            ->assertOk()
            ->assertJsonCount(1, 'data.santri_privat')
            ->assertJsonPath('data.jenis_layanan.0.satuan', 'pertemuan');
    }
}
