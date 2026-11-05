<?php

namespace Tests\Feature\Absensi;

use App\Models\AbsensiTarjamah;
use App\Models\User;
use App\Services\Absensi\AbsensiProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AksesAbsensiTest extends TestCase
{
    use InteractsWithAbsensi, RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('absensi.index'))->assertRedirect(route('login'));
    }

    public function test_santri_cannot_open_absensi_menu(): void
    {
        $this->actingAs(User::factory()->santri()->create())
            ->get(route('absensi.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_all_three_programs(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('absensi.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('absensi/index')
                ->where('programs', ['bbaq', 'tahfidz', 'tarjamah']));
    }

    public function test_unknown_program_returns_not_found(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get('/absensi/kajian')
            ->assertNotFound();
    }

    public function test_pengajar_only_sees_own_active_classes(): void
    {
        $pengajar = User::factory()->pengajar()->create(['username' => 'Ustadz Ahmad']);
        $this->kelasUntuk(AbsensiProgram::Tahfidz, $pengajar);
        $this->kelasUntuk(AbsensiProgram::Tahfidz, $pengajar, ['nama_kelas' => 'Kelas Lama', 'aktif' => false]);
        $this->kelasUntuk(AbsensiProgram::Tahfidz, User::factory()->pengajar()->create(), ['nama_kelas' => 'Kelas Lain']);
        $this->kelasUntuk(AbsensiProgram::Bbaq, $pengajar, ['nama_kelas' => 'Kelas BBAQ']);

        $this->actingAs($pengajar)
            ->get(route('absensi.kelas', 'tahfidz'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('absensi/kelas')
                ->where('program', 'tahfidz')
                ->has('kelasList', 1)
                ->where('kelasList.0.nama_kelas', 'Kelas Al-Fatih')
                ->where('kelasList.0.pengajar', 'Ustadz Ahmad'));
    }

    public function test_admin_sees_classes_of_every_teacher(): void
    {
        $this->kelasUntuk(AbsensiProgram::Bbaq, User::factory()->pengajar()->create(), ['nama_kelas' => 'Kelas A']);
        $this->kelasUntuk(AbsensiProgram::Bbaq, User::factory()->pengajar()->create(), ['nama_kelas' => 'Kelas B']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('absensi.kelas', 'bbaq'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('kelasList.0.nama_kelas', 'Kelas A')
                ->where('kelasList.1.nama_kelas', 'Kelas B'));
    }

    public function test_class_from_another_program_is_not_found(): void
    {
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, User::factory()->pengajar()->create());
        $this->programDb(AbsensiProgram::Tahfidz);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('absensi.pertemuan.index', ['tahfidz', $kelas->id]))
            ->assertNotFound();
    }

    public function test_pengajar_cannot_open_meetings_of_another_teachers_class(): void
    {
        $kelas = $this->kelasUntuk(AbsensiProgram::Tahfidz, User::factory()->pengajar()->create());

        $this->actingAs(User::factory()->pengajar()->create())
            ->get(route('absensi.pertemuan.index', ['tahfidz', $kelas->id]))
            ->assertForbidden();
    }

    public function test_pengajar_cannot_save_attendance_for_another_teachers_class(): void
    {
        $kelas = $this->kelasUntuk(AbsensiProgram::Tarjamah, User::factory()->pengajar()->create());
        $santri = $this->santriDi($kelas, 'Aisyah');
        $pertemuan = $this->pertemuanDi(AbsensiProgram::Tarjamah, $kelas);

        $this->actingAs(User::factory()->pengajar()->create())
            ->put(route('absensi.update', ['tarjamah', $pertemuan->id]), [
                'absensi' => [['santri_program_id' => $santri->id, 'status' => 'Hadir']],
            ])
            ->assertForbidden();

        $this->assertSame(0, AbsensiTarjamah::count());
    }

    public function test_missing_program_row_shows_friendly_error(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('absensi.kelas', 'bbaq'))
            ->assertStatus(422)
            ->assertInertia(fn (Assert $page) => $page
                ->component('absensi/error')
                ->where('message', 'Program BBAQ belum terdaftar di database.'));
    }
}
