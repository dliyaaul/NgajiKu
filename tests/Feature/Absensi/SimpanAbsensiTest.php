<?php

namespace Tests\Feature\Absensi;

use App\Models\Absensi;
use App\Models\AbsensiTahfidz;
use App\Models\KelasPengajaran;
use App\Models\Pertemuan;
use App\Models\SantriProgram;
use App\Models\User;
use App\Services\Absensi\AbsensiProgram;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SimpanAbsensiTest extends TestCase
{
    use InteractsWithAbsensi, RefreshDatabase;

    private User $pengajar;

    private KelasPengajaran $kelas;

    private SantriProgram $aisyah;

    private SantriProgram $bilal;

    private Pertemuan $pertemuan;

    /**
     * Kelas Tahfidz dengan dua santri dan satu pertemuan yang masih bisa diedit.
     */
    private function kelasTahfidz(int $dibuatJamLalu = 1): void
    {
        $this->pengajar = User::factory()->pengajar()->create();
        $this->kelas = $this->kelasUntuk(AbsensiProgram::Tahfidz, $this->pengajar);
        $this->aisyah = $this->santriDi($this->kelas, 'Aisyah');
        $this->bilal = $this->santriDi($this->kelas, 'Bilal');
        $this->pertemuan = $this->pertemuanDi(AbsensiProgram::Tahfidz, $this->kelas, $dibuatJamLalu, ['tanggal' => '2026-11-05']);
    }

    /**
     * @param  list<array<string, mixed>>  $absensi
     */
    private function simpanTahfidz(array $absensi): TestResponse
    {
        return $this->actingAs($this->pengajar)
            ->put(route('absensi.update', ['tahfidz', $this->pertemuan->id]), ['absensi' => $absensi]);
    }

    public function test_hafalan_of_present_santri_is_saved(): void
    {
        $this->kelasTahfidz();

        $this->simpanTahfidz([[
            'santri_program_id' => $this->aisyah->id,
            'status' => 'Hadir',
            'jenis_tahfidz' => 'Ziyadah',
            'surat' => 'Yasin',
            'ayat_mulai' => 1,
            'ayat_selesai' => 12,
            'keterangan' => 'Lancar',
        ]])
            ->assertRedirect(route('absensi.show', ['tahfidz', $this->pertemuan->id]))
            ->assertSessionHas('success');

        $absen = AbsensiTahfidz::sole();
        $this->assertSame($this->aisyah->id, $absen->santri_program_id);
        $this->assertSame('2026-11-05', $absen->tanggal->toDateString());
        $this->assertSame(['Hadir', 'Ziyadah', 'Yasin', 1, 12, 'Lancar'], [
            $absen->status, $absen->jenis_tahfidz, $absen->surat, $absen->ayat_mulai, $absen->ayat_selesai, $absen->keterangan,
        ]);
        $this->assertSame($this->pengajar->id, $absen->created_by);
    }

    public function test_saving_again_updates_the_same_tahfidz_row(): void
    {
        $this->kelasTahfidz();
        $penginput = User::factory()->admin()->create();
        $awal = AbsensiTahfidz::create([
            'tahfidz_pertemuan_id' => $this->pertemuan->id,
            'santri_program_id' => $this->aisyah->id,
            'tanggal' => '2026-11-05',
            'status' => 'Izin',
            'created_by' => $penginput->id,
        ]);

        $this->simpanTahfidz([['santri_program_id' => $this->aisyah->id, 'status' => 'Hadir', 'jenis_tahfidz' => 'Murojaah', 'surat' => 'Al-Mulk', 'ayat_mulai' => 1, 'ayat_selesai' => 5]])
            ->assertSessionHas('success');

        $absen = AbsensiTahfidz::sole();
        $this->assertSame($awal->id, $absen->id);
        $this->assertSame('Al-Mulk', $absen->surat);
        $this->assertSame($penginput->id, $absen->created_by);
    }

    public function test_hafalan_is_cleared_when_santri_is_not_present(): void
    {
        $this->kelasTahfidz();

        $this->simpanTahfidz([[
            'santri_program_id' => $this->aisyah->id,
            'status' => 'Sakit',
            'jenis_tahfidz' => 'Ziyadah',
            'surat' => 'Yasin',
            'ayat_mulai' => 1,
            'ayat_selesai' => 12,
        ]])->assertSessionHas('success');

        $absen = AbsensiTahfidz::sole();
        $this->assertSame('Sakit', $absen->status);
        $this->assertNull($absen->jenis_tahfidz);
        $this->assertNull($absen->surat);
        $this->assertNull($absen->ayat_mulai);
        $this->assertNull($absen->ayat_selesai);
    }

    public function test_belum_absen_removes_existing_tahfidz_attendance(): void
    {
        $this->kelasTahfidz();
        AbsensiTahfidz::create([
            'tahfidz_pertemuan_id' => $this->pertemuan->id,
            'santri_program_id' => $this->aisyah->id,
            'tanggal' => '2026-11-05',
            'status' => 'Hadir',
        ]);

        $this->simpanTahfidz([['santri_program_id' => $this->aisyah->id, 'status' => 'Belum Absen']])
            ->assertSessionHas('success');

        $this->assertSame(0, AbsensiTahfidz::count());
    }

    public function test_ayat_selesai_beyond_surat_length_is_rejected(): void
    {
        $this->kelasTahfidz();

        $this->simpanTahfidz([[
            'santri_program_id' => $this->aisyah->id,
            'status' => 'Hadir',
            'surat' => 'Al-Ikhlas',
            'ayat_mulai' => 1,
            'ayat_selesai' => 5,
        ]])->assertSessionHasErrors('absensi.0.ayat_selesai');

        $this->assertSame(0, AbsensiTahfidz::count());
    }

    public function test_ayat_selesai_before_ayat_mulai_is_rejected(): void
    {
        $this->kelasTahfidz();

        $this->simpanTahfidz([[
            'santri_program_id' => $this->aisyah->id,
            'status' => 'Hadir',
            'surat' => 'Yasin',
            'ayat_mulai' => 10,
            'ayat_selesai' => 5,
        ]])->assertSessionHasErrors('absensi.0.ayat_selesai');
    }

    public function test_unknown_surat_and_status_are_rejected(): void
    {
        $this->kelasTahfidz();

        $this->simpanTahfidz([[
            'santri_program_id' => $this->aisyah->id,
            'status' => 'Bolos',
            'surat' => 'Surat Palsu',
            'ayat_mulai' => 1,
            'ayat_selesai' => 2,
        ]])->assertSessionHasErrors(['absensi.0.status', 'absensi.0.surat']);
    }

    public function test_attendance_cannot_be_saved_after_24_hours(): void
    {
        $this->kelasTahfidz(dibuatJamLalu: 25);

        $this->from(route('absensi.show', ['tahfidz', $this->pertemuan->id]))
            ->simpanTahfidz([['santri_program_id' => $this->aisyah->id, 'status' => 'Izin']])
            ->assertRedirect(route('absensi.show', ['tahfidz', $this->pertemuan->id]))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, '24 jam'));

        $this->assertSame(0, AbsensiTahfidz::count());
    }

    public function test_santri_outside_the_class_is_rejected(): void
    {
        $this->kelasTahfidz();
        $kelasLain = $this->kelasUntuk(AbsensiProgram::Tahfidz, $this->pengajar, ['nama_kelas' => 'Kelas Lain']);
        $santriLain = $this->santriDi($kelasLain, 'Umar');

        $this->simpanTahfidz([['santri_program_id' => $santriLain->id, 'status' => 'Izin']])
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'bukan anggota kelas'));

        $this->assertSame(0, AbsensiTahfidz::count());
    }

    public function test_bbaq_inserts_new_updates_changed_and_deletes_cleared_attendance(): void
    {
        $pengajar = User::factory()->pengajar()->create();
        $kelas = $this->kelasUntuk(AbsensiProgram::Bbaq, $pengajar);
        [$a, $b, $c] = [$this->santriDi($kelas, 'A'), $this->santriDi($kelas, 'B'), $this->santriDi($kelas, 'C')];
        $pertemuan = $this->pertemuanDi(AbsensiProgram::Bbaq, $kelas, 1, ['tanggal' => '2026-11-05']);

        $absenA = $this->absenBbaq($pertemuan, $a);
        $absenC = $this->absenBbaq($pertemuan, $c);

        $this->actingAs($pengajar)
            ->put(route('absensi.update', ['bbaq', $pertemuan->id]), ['absensi' => [
                ['santri_program_id' => $a->id, 'status' => 'Sakit'],
                ['santri_program_id' => $b->id, 'status' => 'Hadir'],
                ['santri_program_id' => $c->id, 'status' => 'Belum Absen'],
            ]])
            ->assertSessionHas('success');

        $this->assertSame('Sakit', $absenA->refresh()->status);
        $this->assertModelMissing($absenC);

        $baru = Absensi::query()->where('santri_program_id', $b->id)->sole();
        $this->assertSame('Hadir', $baru->status);
        $this->assertSame('BBAQ', $baru->program);
        $this->assertSame('BBAQ', $baru->kegiatan);
        $this->assertSame('2026-11-05', $baru->tanggal->toDateString());
        $this->assertSame($pengajar->id, $baru->created_by);
        $this->assertSame($pengajar->id, $baru->user_id);
    }

    public function test_present_tahfidz_santri_must_submit_memorization(): void
    {
        $this->kelasTahfidz();

        $this->simpanTahfidz([['santri_program_id' => $this->aisyah->id, 'status' => 'Hadir']])
            ->assertSessionHasErrors(['absensi.0.jenis_tahfidz', 'absensi.0.surat', 'absensi.0.ayat_mulai', 'absensi.0.ayat_selesai']);

        $this->assertSame(0, AbsensiTahfidz::count());
    }

    public function test_alpha_is_stored_with_each_tables_spelling_and_shown_as_alpha(): void
    {
        $this->kelasTahfidz();
        $kelasBbaq = $this->kelasUntuk(AbsensiProgram::Bbaq, $this->pengajar, ['nama_kelas' => 'Kelas BBAQ']);
        $santriBbaq = $this->santriDi($kelasBbaq, 'Hasan');
        $pertemuanBbaq = $this->pertemuanDi(AbsensiProgram::Bbaq, $kelasBbaq);

        $this->simpanTahfidz([['santri_program_id' => $this->aisyah->id, 'status' => 'Alpha']])->assertSessionHas('success');
        $this->actingAs($this->pengajar)
            ->put(route('absensi.update', ['bbaq', $pertemuanBbaq->id]), ['absensi' => [['santri_program_id' => $santriBbaq->id, 'status' => 'Alpha']]])
            ->assertSessionHas('success');

        $this->assertSame('Alpa', AbsensiTahfidz::sole()->status);
        $this->assertSame('Alpha', Absensi::sole()->status);

        $this->actingAs($this->pengajar)
            ->get(route('absensi.pertemuan.index', ['tahfidz', $this->kelas->id]))
            ->assertInertia(fn (Assert $page) => $page->where('pertemuanList.0.rekap', ['Alpha' => 1]));
    }

    public function test_santri_cannot_have_two_tahfidz_records_on_the_same_date(): void
    {
        $this->kelasTahfidz();
        $pertemuanLain = $this->pertemuanDi(AbsensiProgram::Tahfidz, $this->kelas, 1, ['tanggal' => '2026-11-05']);
        AbsensiTahfidz::create([
            'tahfidz_pertemuan_id' => $pertemuanLain->id,
            'santri_program_id' => $this->aisyah->id,
            'tanggal' => '2026-11-05',
            'status' => 'Izin',
        ]);

        $this->simpanTahfidz([['santri_program_id' => $this->aisyah->id, 'status' => 'Sakit']])
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'sudah memiliki absensi Tahfidz'));

        $this->assertSame(1, AbsensiTahfidz::count());
    }

    public function test_show_page_displays_santri_and_saved_hafalan(): void
    {
        $this->kelasTahfidz();
        AbsensiTahfidz::create([
            'tahfidz_pertemuan_id' => $this->pertemuan->id,
            'santri_program_id' => $this->aisyah->id,
            'tanggal' => '2026-11-05',
            'status' => 'Hadir',
            'jenis_tahfidz' => 'Ziyadah',
            'surat' => 'Yasin',
            'ayat_mulai' => 1,
            'ayat_selesai' => 12,
            'keterangan' => 'Perlu diulang',
        ]);

        $this->actingAs($this->pengajar)
            ->get(route('absensi.show', ['tahfidz', $this->pertemuan->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('absensi/show')
                ->where('editable', true)
                ->has('santriList', 2)
                ->where('santriList.0.nama', 'Aisyah')
                ->where('santriList.0.surat', 'Yasin')
                ->where('santriList.0.ayat_selesai', 12)
                ->where('santriList.0.keterangan', 'Perlu diulang')
                ->where('santriList.1.nama', 'Bilal'));
    }

    public function test_unsaved_santri_default_to_hadir_and_legacy_alpa_is_normalized(): void
    {
        $this->kelasTahfidz();
        AbsensiTahfidz::create([
            'tahfidz_pertemuan_id' => $this->pertemuan->id,
            'santri_program_id' => $this->aisyah->id,
            'tanggal' => '2026-11-05',
            'status' => 'Alpa',
        ]);

        $this->actingAs($this->pengajar)
            ->get(route('absensi.show', ['tahfidz', $this->pertemuan->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('santriList.0.status', 'Alpha')
                ->where('santriList.0.tersimpan', true)
                ->where('santriList.1.status', 'Hadir')
                ->where('santriList.1.tersimpan', false)
                ->where('ringkasanTersimpan', ['Hadir' => 0, 'Izin' => 0, 'Sakit' => 0, 'Alpha' => 1])
                ->has('daftarSurat', 114)
                ->where('daftarSurat.35', ['nomor' => 36, 'nama' => 'Yasin', 'jumlah_ayat' => 83]));
    }
}
