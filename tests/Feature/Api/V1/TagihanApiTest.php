<?php

namespace Tests\Feature\Api\V1;

use App\Enums\StatusTagihan;
use App\Models\Lembaga;
use App\Models\PembayaranTarif;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Pembayaran\InteractsWithPembayaran;
use Tests\TestCase;

class TagihanApiTest extends TestCase
{
    use InteractsWithPembayaran, RefreshDatabase;

    public function test_santri_only_lists_own_bills(): void
    {
        $milikSaya = Tagihan::factory()->untukSantri($this->santriReguler('Aisyah'))->create();
        Tagihan::factory()->untukSantri($this->santriReguler('Bilal'))->create();
        Tagihan::factory()->untukLembaga(Lembaga::factory()->create())->create();
        Sanctum::actingAs($milikSaya->santriProgram->santri);

        $this->getJson(route('api.v1.tagihan.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $milikSaya->id)
            ->assertJsonPath('data.0.nama_santri', 'Aisyah')
            ->assertJsonPath('data.0.bisa_dibayar', true);
    }

    public function test_koordinator_only_lists_bills_of_own_lembaga(): void
    {
        $lembaga = Lembaga::factory()->create();
        $milikLembaga = Tagihan::factory()->untukLembaga($lembaga)->create();
        Tagihan::factory()->untukLembaga(Lembaga::factory()->create())->create();
        Tagihan::factory()->create();
        Sanctum::actingAs($this->koordinator($lembaga));

        $this->getJson(route('api.v1.tagihan.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $milikLembaga->id);
    }

    public function test_admin_lists_all_bills_and_can_filter_by_status_and_name(): void
    {
        Tagihan::factory()->untukSantri($this->santriReguler('Aisyah'))->create();
        $lunas = Tagihan::factory()->untukSantri($this->santriReguler('Bilal'))->status(StatusTagihan::Lunas)->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson(route('api.v1.tagihan.index'))->assertJsonCount(2, 'data');
        $this->getJson(route('api.v1.tagihan.index', ['status' => 'lunas']))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $lunas->id);
        $this->getJson(route('api.v1.tagihan.index', ['cari' => 'bil']))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_santri', 'Bilal');
    }

    public function test_staff_and_pengajar_see_no_bills(): void
    {
        Tagihan::factory()->create();

        foreach ([User::factory()->staff()->create(), User::factory()->pengajar()->create()] as $user) {
            Sanctum::actingAs($user);
            $this->getJson(route('api.v1.tagihan.index'))->assertOk()->assertJsonCount(0, 'data');
        }
    }

    public function test_santri_cannot_open_someone_elses_bill(): void
    {
        $tagihan = Tagihan::factory()->create();
        Sanctum::actingAs(User::factory()->santri()->create());

        $this->getJson(route('api.v1.tagihan.show', $tagihan))->assertForbidden();
    }

    public function test_preview_lists_reguler_santri_and_marks_duplicates(): void
    {
        PembayaranTarif::factory()->create(['nominal' => 200000]);
        $aisyah = $this->santriReguler('Aisyah');
        $bilal = $this->santriReguler('Bilal');
        Tagihan::factory()->untukSantri($bilal)->create(['periode_mulai' => '2026-11-01', 'periode_selesai' => '2026-11-30']);
        // Bukan layanan Reguler: tidak ikut ditagih.
        $this->santriReguler('Privat')->update(['kelas_type_id' => $this->jenisLayanan('Privat')->id]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson(route('api.v1.tagihan.generate-reguler'), ['periode_mulai' => '2026-11-01', 'periode_selesai' => '2026-11-30'])
            ->assertOk()
            ->assertJsonPath('data.mode', 'pratinjau')
            ->assertJsonPath('data.total_siap_generate', 1)
            ->assertJsonPath('data.total_duplikat', 1)
            ->assertJsonPath('data.daftar.0.santri_program_id', $aisyah->id)
            ->assertJsonPath('data.daftar.0.status', 'siap_generate')
            ->assertJsonPath('data.daftar.1.status', 'duplikat');

        $this->assertSame(1, Tagihan::count());
    }

    public function test_generate_creates_bill_with_item_and_skips_existing_ones(): void
    {
        $tarif = PembayaranTarif::factory()->create(['nama_tarif' => 'SPP Reguler', 'nominal' => 200000]);
        $aisyah = $this->santriReguler('Aisyah');
        $bilal = $this->santriReguler('Bilal');
        Tagihan::factory()->untukSantri($bilal)->create(['periode_mulai' => '2026-11-01', 'periode_selesai' => '2026-11-30']);
        // Tagihan yang dibatalkan tidak dihitung sebagai duplikat.
        Tagihan::factory()->untukSantri($aisyah)->status(StatusTagihan::Dibatalkan)->create(['periode_mulai' => '2026-11-01', 'periode_selesai' => '2026-11-30']);
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson(route('api.v1.tagihan.generate-reguler'), [
            'periode_mulai' => '2026-11-01',
            'periode_selesai' => '2026-11-30',
            'tanggal_jatuh_tempo' => '2026-11-10',
            'konfirmasi' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.total_dibuat', 1)
            ->assertJsonPath('data.total_dilewati_duplikat', 1);

        $baru = Tagihan::query()->where('santri_program_id', $aisyah->id)->where('status', 'belum_bayar')->sole();
        $this->assertStringStartsWith('REG-20261101-20261130-', $baru->nomor_tagihan);
        $this->assertSame('200000.00', $baru->total);
        $this->assertSame('2026-11-10', $baru->tanggal_jatuh_tempo->toDateString());
        $this->assertSame($admin->id, $baru->created_by);

        $item = $baru->items()->sole();
        $this->assertSame(['SPP Reguler', 'bulanan', 'bulan', $tarif->id], [$item->deskripsi, $item->jenis, $item->satuan, $item->referensi_id]);
        $this->assertSame('Aisyah', $item->metadata['nama_santri']);
    }

    public function test_generate_requires_exactly_one_active_reguler_tariff(): void
    {
        $this->santriReguler();
        Sanctum::actingAs(User::factory()->admin()->create());
        $periode = ['periode_mulai' => '2026-11-01', 'periode_selesai' => '2026-11-30'];

        PembayaranTarif::factory()->create(['aktif' => false]);
        PembayaranTarif::factory()->create(['berlaku_mulai' => '2027-01-01']);

        $this->postJson(route('api.v1.tagihan.generate-reguler'), $periode)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Tarif Reguler bulanan tidak ditemukan untuk periode yang dipilih.');

        PembayaranTarif::factory()->count(2)->create();

        $this->postJson(route('api.v1.tagihan.generate-reguler'), $periode)
            ->assertUnprocessable()
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'lebih dari satu tarif'));
    }

    public function test_generate_validates_period_and_is_admin_only(): void
    {
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson(route('api.v1.tagihan.generate-reguler'), ['periode_mulai' => '2026-11-30', 'periode_selesai' => '2026-11-01'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('periode_selesai');

        Sanctum::actingAs(User::factory()->santri()->create());

        $this->postJson(route('api.v1.tagihan.generate-reguler'), ['periode_mulai' => '2026-11-01', 'periode_selesai' => '2026-11-30'])
            ->assertForbidden();
    }
}
