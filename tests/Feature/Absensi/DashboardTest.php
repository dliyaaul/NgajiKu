<?php

namespace Tests\Feature\Absensi;

use App\Models\User;
use App\Services\Absensi\AbsensiProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use InteractsWithAbsensi, RefreshDatabase;

    private function programLengkap(): void
    {
        foreach (AbsensiProgram::cases() as $program) {
            $this->programDb($program);
        }
    }

    public function test_pengajar_sees_open_attendance_and_summary_for_own_classes(): void
    {
        $this->freezeTime();
        $this->programLengkap();
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, $pengajar);
        $pertemuan = $this->pertemuanDi(AbsensiProgram::Bbaq, $kelas, 1);

        foreach (['Hadir', 'Hadir', 'Izin'] as $status) {
            $this->absenBbaq($pertemuan, $this->santriDi($kelas, fake()->unique()->name()), $status);
        }

        // Kelas pengajar lain dan pertemuan di luar rentang 7 hari tidak dihitung.
        $this->pertemuanDi(AbsensiProgram::Bbaq, $this->kelasUntuk(AbsensiProgram::Bbaq, User::factory()->pengajar()->create()));
        $this->pertemuanDi(AbsensiProgram::Bbaq, $kelas, 1, ['tanggal' => now()->subDays(7)->toDateString()]);

        $this->actingAs($pengajar)
            ->get(route('pengajar.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/pengajar')
                ->where('jumlahKelas', 1)
                ->where('jumlahPertemuan', 1)
                ->has('terbuka', 1)
                ->where('terbuka.0.kelas', 'Kelas Al-Fatih')
                ->where('terbuka.0.program', 'bbaq')
                ->where('terbuka.0.rekap', ['Hadir' => 2, 'Izin' => 1])
                ->where('terbuka.0.batas_edit', now()->addHours(23)->toIso8601String())
                ->where('belumDiisi', 0)
                ->where('persenHadir', 67));
    }

    public function test_admin_summary_covers_every_class_in_the_program(): void
    {
        $this->programLengkap();
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, User::factory()->pengajar()->create());
        $this->pertemuanDi(AbsensiProgram::Bbaq, $kelas, 30);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/admin')
                ->has('terbuka', 0)
                ->has('terbaru', 1)
                ->where('terbaru.0.kelas', 'Kelas Al-Fatih')
                ->where('terbaru.0.editable', false));
    }

    public function test_pengajar_without_classes_sees_empty_summary(): void
    {
        $this->programLengkap();

        $this->actingAs(User::factory()->pengajar()->create())
            ->get(route('pengajar.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('terbuka', 0)
                ->where('jumlahKelas', 0)
                ->where('programs.0.jumlah_kelas', 0)
                ->where('gagal', []));
    }

    public function test_program_missing_from_database_is_reported_without_hiding_others(): void
    {
        $this->programDb(AbsensiProgram::Bbaq);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('gagal', ['Tahfidz', 'Tarjamah'])
                ->has('programs', 1));
    }

    public function test_santri_sees_attendance_rate_and_latest_memorization(): void
    {
        $this->freezeTime();
        $this->programLengkap();
        $santri = User::factory()->santri()->create();
        $ustadzah = User::factory()->pengajar()->create(['username' => 'Ustadzah Fatimah']);

        $this->riwayatTigaProgram($santri, $ustadzah);

        $this->actingAs($santri)
            ->get(route('santri.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/santri')
                ->where('ringkasan', ['Hadir' => 2, 'Izin' => 1, 'Sakit' => 0, 'Alpha' => 0])
                ->where('persenHadir', 67)
                ->where('setoranTerakhir.surat', 'An-Naba')
                ->where('setoranTerakhir.ayat_selesai', 16)
                ->where('terbaru.0.pengajar', 'Ustadzah Fatimah'));
    }

    public function test_staff_dashboard_shares_the_logged_in_user(): void
    {
        $this->actingAs(User::factory()->staff()->create(['username' => 'Pengguna Uji']))
            ->get(route('staff.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('dashboard/staff')
                ->where('auth.role', 'staff')
                ->where('auth.username', 'Pengguna Uji'));
    }
}
