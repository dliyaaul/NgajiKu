<?php

namespace Tests\Feature\Absensi;

use App\Models\AbsensiTahfidz;
use App\Models\BbaqPertemuan;
use App\Models\PengajarPengganti;
use App\Models\PengajarProgram;
use App\Models\TahfidzPertemuan;
use App\Models\TarjamahPertemuan;
use App\Models\User;
use App\Services\Absensi\AbsensiProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PertemuanTest extends TestCase
{
    use InteractsWithAbsensi, RefreshDatabase;

    public function test_meeting_list_shows_attendance_summary_newest_first(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, $pengajar);
        $lama = $this->pertemuanDi(AbsensiProgram::Bbaq, $kelas, 50, ['tanggal' => '2026-10-01']);
        $baru = $this->pertemuanDi(AbsensiProgram::Bbaq, $kelas, 1, ['tanggal' => now()->toDateString()]);

        foreach (['Hadir', 'Hadir', 'Izin'] as $status) {
            $this->absenBbaq($baru, $this->santriDi($kelas, fake()->unique()->name()), $status);
        }

        $this->actingAs($pengajar)
            ->get(route('absensi.pertemuan.index', ['bbaq', $kelas->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('absensi/pertemuan/index')
                ->has('pertemuanList', 2)
                ->where('pertemuanList.0.id', $baru->id)
                ->where('pertemuanList.0.rekap', ['Hadir' => 2, 'Izin' => 1])
                ->where('pertemuanList.0.editable', true)
                ->where('pertemuanList.1.id', $lama->id)
                ->where('pertemuanList.1.editable', false));
    }

    public function test_tarjamah_meeting_is_created_with_its_own_substitute_column(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Tarjamah, $pengajar);
        $pengganti = PengajarPengganti::factory()->create();

        $this->actingAs($pengajar)
            ->post(route('absensi.pertemuan.store', ['tarjamah', $kelas->id]), [
                'tanggal' => '2026-11-05',
                'pengajar_id' => $pengajar->id,
                'pengganti_id' => $pengganti->id,
                'materi_pembelajaran' => 'Surat Al-Mulk',
                'metode' => 'Online',
            ])
            ->assertRedirect(route('absensi.show', ['tarjamah', TarjamahPertemuan::sole()->id]));

        $pertemuan = TarjamahPertemuan::sole();
        $this->assertSame($pengganti->id, $pertemuan->pengajar_pengganti_id);
        $this->assertSame($kelas->id, $pertemuan->kelas_pengajaran_id);
        $this->assertSame($kelas->program_id, $pertemuan->program_id);
        $this->assertSame($pengajar->id, $pertemuan->created_by);
        $this->assertSame('Online', $pertemuan->metode);
    }

    public function test_meeting_requires_valid_method_and_date(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, $pengajar);

        $this->actingAs($pengajar)
            ->post(route('absensi.pertemuan.store', ['bbaq', $kelas->id]), [
                'tanggal' => '05/11/2026',
                'metode' => 'Tatap Muka',
            ])
            ->assertSessionHasErrors(['tanggal', 'metode']);

        $this->assertSame(0, BbaqPertemuan::count());
    }

    public function test_create_form_preselects_the_logged_in_teacher(): void
    {
        $pengajar = User::factory()->pengajar()->create(['username' => 'Ustadz Ahmad']);
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, $pengajar);
        PengajarProgram::factory()->create(['pengajar_id' => $pengajar->id, 'program_id' => $kelas->program_id]);
        PengajarPengganti::factory()->create(['nama' => 'Ustadzah Maryam']);
        PengajarPengganti::factory()->create(['nama' => 'Tidak Aktif', 'aktif' => false]);

        $this->actingAs($pengajar)
            ->get(route('absensi.pertemuan.create', ['bbaq', $kelas->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('absensi/pertemuan/form')
                ->where('pertemuanId', null)
                ->where('values.pengajar_id', $pengajar->id)
                ->where('values.metode', 'Offline')
                ->where('pengajarOptions', [['id' => $pengajar->id, 'nama' => 'Ustadz Ahmad']])
                ->has('penggantiOptions', 1)
                ->where('penggantiOptions.0.nama', 'Ustadzah Maryam'));
    }

    public function test_invalid_meeting_shows_errors_next_to_fields(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, $pengajar);

        $this->actingAs($pengajar)
            ->from(route('absensi.pertemuan.create', ['bbaq', $kelas->id]))
            ->followingRedirects()
            ->post(route('absensi.pertemuan.store', ['bbaq', $kelas->id]), [
                'tanggal' => '05/11/2026',
                'metode' => 'Offline',
            ])
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('absensi/pertemuan/form')
                ->has('errors.tanggal')
                ->missing('errors.metode'));
    }

    public function test_changing_meeting_date_moves_its_attendance_date(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Tahfidz, $pengajar);
        $pertemuan = $this->pertemuanDi(AbsensiProgram::Tahfidz, $kelas, 1, ['tanggal' => '2026-11-04']);
        $absen = AbsensiTahfidz::create([
            'tahfidz_pertemuan_id' => $pertemuan->id,
            'santri_program_id' => $this->santriDi($kelas, 'Aisyah')->id,
            'tanggal' => '2026-11-04',
            'status' => 'Hadir',
        ]);

        $this->actingAs($pengajar)
            ->put(route('absensi.pertemuan.update', ['tahfidz', $pertemuan->id]), [
                'tanggal' => '2026-11-05',
                'metode' => 'Offline',
            ])
            ->assertRedirect(route('absensi.pertemuan.index', ['tahfidz', $kelas->id]))
            ->assertSessionHas('success');

        $this->assertSame('2026-11-05', $pertemuan->refresh()->tanggal->toDateString());
        $this->assertSame('2026-11-05', $absen->refresh()->tanggal->toDateString());
    }

    public function test_deleting_meeting_removes_its_attendance(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Tahfidz, $pengajar);
        $pertemuan = $this->pertemuanDi(AbsensiProgram::Tahfidz, $kelas);
        AbsensiTahfidz::create([
            'tahfidz_pertemuan_id' => $pertemuan->id,
            'santri_program_id' => $this->santriDi($kelas, 'Aisyah')->id,
            'tanggal' => $pertemuan->tanggal,
            'status' => 'Hadir',
        ]);

        $this->actingAs($pengajar)
            ->delete(route('absensi.pertemuan.destroy', ['tahfidz', $pertemuan->id]))
            ->assertRedirect(route('absensi.pertemuan.index', ['tahfidz', $kelas->id]))
            ->assertSessionHas('success');

        $this->assertModelMissing($pertemuan);
        $this->assertSame(0, AbsensiTahfidz::count());
    }

    public function test_meeting_older_than_24_hours_cannot_be_deleted(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Tahfidz, $pengajar);
        $pertemuan = $this->pertemuanDi(AbsensiProgram::Tahfidz, $kelas, 25);

        $this->actingAs($pengajar)
            ->delete(route('absensi.pertemuan.destroy', ['tahfidz', $pertemuan->id]))
            ->assertSessionHas('error');

        $this->assertModelExists($pertemuan);
        $this->assertSame(1, TahfidzPertemuan::count());
    }

    public function test_meeting_older_than_24_hours_cannot_be_edited(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, $pengajar);
        $pertemuan = $this->pertemuanDi(AbsensiProgram::Bbaq, $kelas, 25);

        $this->actingAs($pengajar)
            ->get(route('absensi.pertemuan.edit', ['bbaq', $pertemuan->id]))
            ->assertRedirect(route('absensi.pertemuan.index', ['bbaq', $kelas->id]))
            ->assertSessionHas('error');
    }
}
