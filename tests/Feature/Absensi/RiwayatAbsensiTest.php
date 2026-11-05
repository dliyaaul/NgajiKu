<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\User;
use App\Services\Absensi\AbsensiProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RiwayatAbsensiTest extends TestCase
{
    use InteractsWithAbsensi, RefreshDatabase;

    private User $santri;

    protected function setUp(): void
    {
        parent::setUp();

        $this->santri = User::factory()->santri()->create();
        $this->riwayatTigaProgram($this->santri, User::factory()->pengajar()->create(['username' => 'Ustadzah Fatimah']));
    }

    public function test_santri_sees_own_history_from_all_programs_newest_first(): void
    {
        // Absensi santri lain dan absensi kegiatan PTL (tanpa pertemuan) tidak ikut tampil.
        $kelasLain = $this->kelasUntuk(AbsensiProgram::Bbaq, User::factory()->pengajar()->create(), ['nama_kelas' => 'Kelas Lain']);
        $this->absenBbaq($this->pertemuanDi(AbsensiProgram::Bbaq, $kelasLain, 1, ['tanggal' => '2026-10-04']), $this->santriDi($kelasLain, 'Lain'));
        Absensi::create([
            'santri_program_id' => $this->santri->santriPrograms()->first()->id,
            'user_id' => $this->santri->id,
            'tanggal' => '2026-10-05',
            'program' => 'PTL',
            'kegiatan' => 'Sholat Subuh',
            'status' => 'Hadir',
        ]);

        $this->actingAs($this->santri)
            ->get(route('santri.riwayat-absensi'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('santri/riwayat-absensi')
                ->has('items', 3)
                ->where('items.0.tanggal', '2026-10-03')
                ->where('items.0.program', 'tahfidz')
                ->where('items.0.surat', 'An-Naba')
                ->where('items.0.pengajar', 'Ustadzah Fatimah')
                ->where('items.1.tanggal', '2026-10-02')
                ->where('items.1.program', 'tarjamah')
                ->where('items.2.tanggal', '2026-10-01')
                ->where('items.2.materi', 'Materi BBAQ')
                ->where('ringkasan', ['Hadir' => 2, 'Izin' => 1, 'Sakit' => 0, 'Alpha' => 0]));
    }

    public function test_program_filter_only_shows_that_program(): void
    {
        $this->actingAs($this->santri)
            ->get(route('santri.riwayat-absensi', ['program' => 'tarjamah']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('filter', 'tarjamah')
                ->has('items', 1)
                ->where('items.0.status', 'Izin'));
    }

    public function test_santri_without_programs_sees_empty_history(): void
    {
        $this->actingAs(User::factory()->santri()->create())
            ->get(route('santri.riwayat-absensi'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('items', []));
    }

    public function test_pengajar_cannot_open_santri_history_page(): void
    {
        $this->actingAs(User::factory()->pengajar()->create())
            ->get(route('santri.riwayat-absensi'))
            ->assertForbidden();
    }
}
