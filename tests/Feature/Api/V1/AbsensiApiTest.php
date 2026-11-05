<?php

namespace Tests\Feature\Api\V1;

use App\Models\AbsensiTahfidz;
use App\Models\BbaqPertemuan;
use App\Models\User;
use App\Services\Absensi\AbsensiProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Absensi\InteractsWithAbsensi;
use Tests\TestCase;

class AbsensiApiTest extends TestCase
{
    use InteractsWithAbsensi, RefreshDatabase;

    public function test_santri_cannot_access_absensi_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->santri()->create());

        $this->getJson(route('api.v1.absensi.programs'))
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki akses ke halaman ini.');
    }

    public function test_pengajar_lists_only_own_classes(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, $pengajar);
        $this->kelasUntuk(AbsensiProgram::Bbaq, User::factory()->pengajar()->create(), ['nama_kelas' => 'Kelas Lain']);
        Sanctum::actingAs($pengajar);

        $this->getJson(route('api.v1.absensi.kelas.index', 'bbaq'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $kelas->id)
            ->assertJsonPath('data.0.pengajar.id', $pengajar->id);
    }

    public function test_pengajar_creates_meeting_and_saves_attendance(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Tahfidz, $pengajar);
        $aisyah = $this->santriDi($kelas, 'Aisyah');
        $bilal = $this->santriDi($kelas, 'Bilal');
        Sanctum::actingAs($pengajar);

        $pertemuanId = $this->postJson(route('api.v1.absensi.pertemuan.store', ['tahfidz', $kelas->id]), [
            'tanggal' => '2026-11-05',
            'metode' => 'Offline',
            'materi_pembelajaran' => 'Juz 30',
        ])
            ->assertCreated()
            ->assertJsonPath('data.tanggal', '2026-11-05')
            ->assertJsonPath('data.editable', true)
            ->json('data.id');

        $this->getJson(route('api.v1.absensi.show', ['tahfidz', $pertemuanId]))
            ->assertOk()
            ->assertJsonPath('data.pertemuan.id', $pertemuanId)
            ->assertJsonPath('data.santri.0.nama', 'Aisyah')
            ->assertJsonPath('data.santri.0.tersimpan', false);

        $this->putJson(route('api.v1.absensi.update', ['tahfidz', $pertemuanId]), ['absensi' => [
            ['santri_program_id' => $aisyah->id, 'status' => 'Hadir', 'jenis_tahfidz' => 'Ziyadah', 'surat' => 'An-Naba', 'ayat_mulai' => 1, 'ayat_selesai' => 10],
            ['santri_program_id' => $bilal->id, 'status' => 'Izin'],
        ]])
            ->assertOk()
            ->assertJsonPath('data.santri.0.surat', 'An-Naba')
            ->assertJsonPath('data.santri.1.status', 'Izin')
            ->assertJsonPath('data.santri.1.tersimpan', true);

        $this->assertSame(2, AbsensiTahfidz::count());
    }

    public function test_invalid_attendance_returns_json_validation_errors(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Tahfidz, $pengajar);
        $santri = $this->santriDi($kelas, 'Aisyah');
        $pertemuan = $this->pertemuanDi(AbsensiProgram::Tahfidz, $kelas);
        Sanctum::actingAs($pengajar);

        $this->putJson(route('api.v1.absensi.update', ['tahfidz', $pertemuan->id]), ['absensi' => [
            ['santri_program_id' => $santri->id, 'status' => 'Hadir', 'jenis_tahfidz' => 'Ziyadah', 'surat' => 'Al-Ikhlas', 'ayat_mulai' => 1, 'ayat_selesai' => 9],
        ]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('absensi.0.ayat_selesai');
    }

    public function test_expired_meeting_returns_business_rule_error(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, $pengajar);
        $pertemuan = $this->pertemuanDi(AbsensiProgram::Bbaq, $kelas, 25);
        Sanctum::actingAs($pengajar);

        $this->deleteJson(route('api.v1.absensi.pertemuan.destroy', ['bbaq', $pertemuan->id]))
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message) => str_contains($message, '24 jam'));

        $this->assertModelExists($pertemuan);
    }

    public function test_pengajar_cannot_touch_another_teachers_meeting(): void
    {
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, User::factory()->pengajar()->create());
        $pertemuan = $this->pertemuanDi(AbsensiProgram::Bbaq, $kelas);
        Sanctum::actingAs(User::factory()->pengajar()->create());

        $this->deleteJson(route('api.v1.absensi.pertemuan.destroy', ['bbaq', $pertemuan->id]))
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki akses ke kelas ini.');

        $this->assertSame(1, BbaqPertemuan::count());
    }

    public function test_unknown_meeting_returns_generic_not_found(): void
    {
        $this->programDb(AbsensiProgram::Bbaq);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson(route('api.v1.absensi.pertemuan.show', ['bbaq', fake()->uuid()]))
            ->assertNotFound()
            ->assertExactJson(['message' => 'Data tidak ditemukan.']);
    }

    public function test_form_options_include_quran_surahs_only_for_tahfidz(): void
    {
        $this->programDb(AbsensiProgram::Tahfidz);
        $this->programDb(AbsensiProgram::Bbaq);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson(route('api.v1.absensi.opsi', 'tahfidz'))
            ->assertOk()
            ->assertJsonCount(114, 'data.daftar_surat')
            ->assertJsonPath('data.metode', ['Offline', 'Online']);

        $this->getJson(route('api.v1.absensi.opsi', 'bbaq'))
            ->assertOk()
            ->assertJsonCount(0, 'data.daftar_surat');
    }

    public function test_santri_gets_own_history_and_dashboard(): void
    {
        $santri = User::factory()->santri()->create();
        $this->riwayatTigaProgram($santri, User::factory()->pengajar()->create());
        Sanctum::actingAs($santri);

        $this->getJson(route('api.v1.santri.riwayat-absensi', ['program' => 'tahfidz']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.surat', 'An-Naba')
            ->assertJsonPath('meta.ringkasan.Hadir', 1);

        $this->getJson(route('api.v1.santri.riwayat-absensi', ['program' => 'kajian']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('program');

        $this->getJson(route('api.v1.dashboard'))
            ->assertOk()
            ->assertJsonPath('data.role', 'santri')
            ->assertJsonPath('data.persenHadir', 67);
    }
}
