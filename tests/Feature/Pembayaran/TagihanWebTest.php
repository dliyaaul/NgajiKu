<?php

namespace Tests\Feature\Pembayaran;

use App\Enums\StatusPembayaran;
use App\Enums\StatusTagihan;
use App\Models\Pembayaran;
use App\Models\PembayaranRekening;
use App\Models\PembayaranTarif;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TagihanWebTest extends TestCase
{
    use InteractsWithPembayaran, RefreshDatabase;

    public function test_admin_sees_bill_list_with_status_counts_and_pending_verifications(): void
    {
        Tagihan::factory()->untukSantri($this->santriReguler('Aisyah'))->create();
        Tagihan::factory()->untukSantri($this->santriReguler('Bilal'))->status(StatusTagihan::MenungguVerifikasi)->create();
        Pembayaran::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pembayaran.index', ['status' => 'belum_bayar']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('pembayaran/tagihan/index')
                ->where('mode', 'admin')
                ->has('tagihan.data', 2)
                ->where('tagihan.data.0.status', 'belum_bayar')
                ->where('jumlahPerStatus.belum_bayar', 2)
                ->where('jumlahPerStatus.menunggu_verifikasi', 1)
                ->where('menungguVerifikasi', 1)
                ->where('filter.status', 'belum_bayar'));
    }

    public function test_admin_can_search_bills(): void
    {
        Tagihan::factory()->untukSantri($this->santriReguler('Aisyah'))->create();
        Tagihan::factory()->untukSantri($this->santriReguler('Bilal'))->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pembayaran.index', ['cari' => 'aisy']))
            ->assertInertia(fn (Assert $page) => $page
                ->has('tagihan.data', 1)
                ->where('tagihan.data.0.nama_santri', 'Aisyah'));
    }

    public function test_dashboards_remind_about_unpaid_bills_and_pending_verifications(): void
    {
        $santriProgram = $this->santriReguler();
        Tagihan::factory()->untukSantri($santriProgram)->create(['total' => 150000]);
        Tagihan::factory()->untukSantri($santriProgram)->status(StatusTagihan::Ditolak)->create(['total' => 100000]);
        Tagihan::factory()->untukSantri($santriProgram)->status(StatusTagihan::Lunas)->create();
        Pembayaran::factory()->create();

        $this->actingAs($santriProgram->santri)
            ->get(route('santri.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('tagihanBelumDibayar', ['jumlah' => 2, 'total' => 250000]));

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('pembayaranMenunggu', 1));
    }

    public function test_non_admin_cannot_open_admin_billing_pages(): void
    {
        $this->actingAs(User::factory()->santri()->create())
            ->get(route('admin.pembayaran.index'))
            ->assertForbidden();
    }

    public function test_santri_only_sees_own_bills_and_can_open_payment_form(): void
    {
        $tagihan = Tagihan::factory()->untukSantri($this->santriReguler())->create();
        $tagihanLain = Tagihan::factory()->create();
        PembayaranRekening::factory()->create(['nama_bank' => 'BSI']);
        PembayaranRekening::factory()->create(['aktif' => false]);
        $santri = $tagihan->santriProgram->santri;

        $this->actingAs($santri)
            ->get(route('santri.tagihan.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('mode', 'santri')
                ->has('tagihan.data', 1)
                ->where('tagihan.data.0.id', $tagihan->id));

        $this->actingAs($santri)
            ->get(route('santri.tagihan.show', $tagihan))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('pembayaran/tagihan/show')
                ->where('bisaBayar', true)
                ->has('rekeningOptions', 1)
                ->where('rekeningOptions.0.nama_bank', 'BSI'));

        $this->actingAs($santri)->get(route('santri.tagihan.show', $tagihanLain))->assertForbidden();
    }

    public function test_paid_bill_shows_no_payment_form(): void
    {
        $tagihan = Tagihan::factory()->untukSantri($this->santriReguler())->status(StatusTagihan::Lunas)->create();
        PembayaranRekening::factory()->create();

        $this->actingAs($tagihan->santriProgram->santri)
            ->get(route('santri.tagihan.show', $tagihan))
            ->assertInertia(fn (Assert $page) => $page
                ->where('bisaBayar', false)
                ->where('rekeningOptions', []));
    }

    public function test_santri_submits_payment_proof_from_web(): void
    {
        Storage::fake('bukti_transfer');
        $tagihan = Tagihan::factory()->untukSantri($this->santriReguler())->create(['total' => 150000]);

        $this->actingAs($tagihan->santriProgram->santri)
            ->post(route('santri.tagihan.bayar', $tagihan), [
                'rekening_tujuan_id' => PembayaranRekening::factory()->create()->id,
                'tanggal_transfer' => now()->toDateString(),
                'nominal_transfer' => 150000,
                'bukti_transfer' => UploadedFile::fake()->image('bukti.png'),
            ])
            ->assertRedirect(route('santri.tagihan.show', $tagihan))
            ->assertSessionHas('success');

        $this->assertSame(StatusTagihan::MenungguVerifikasi, $tagihan->refresh()->status);
        Storage::disk('bukti_transfer')->assertExists(Pembayaran::sole()->bukti_transfer);
    }

    public function test_web_payment_form_shows_validation_errors(): void
    {
        $tagihan = Tagihan::factory()->untukSantri($this->santriReguler())->create();

        $this->actingAs($tagihan->santriProgram->santri)
            ->post(route('santri.tagihan.bayar', $tagihan), ['nominal_transfer' => 1])
            ->assertSessionHasErrors(['rekening_tujuan_id', 'tanggal_transfer', 'nominal_transfer', 'bukti_transfer']);
    }

    public function test_admin_detail_shows_payment_history_with_web_proof_link(): void
    {
        $pembayaran = Pembayaran::factory()->create(['status' => StatusPembayaran::Ditolak, 'alasan_penolakan' => 'Buram']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pembayaran.tagihan.show', $pembayaran->tagihan_id))
            ->assertInertia(fn (Assert $page) => $page
                ->where('mode', 'admin')
                ->where('tagihan.pembayaran.0.alasan_penolakan', 'Buram')
                ->where('tagihan.pembayaran.0.bukti_url', route('pembayaran.bukti', $pembayaran)));
    }

    public function test_proof_is_served_on_web_to_owner_but_not_other_santri(): void
    {
        Storage::fake('bukti_transfer');
        $pembayaran = Pembayaran::factory()->create(['bukti_transfer' => 'x/1.jpg']);
        Storage::disk('bukti_transfer')->put('x/1.jpg', 'gambar');

        $this->actingAs($pembayaran->tagihan->santriProgram->santri)->get(route('pembayaran.bukti', $pembayaran))->assertOk();
        $this->actingAs(User::factory()->santri()->create())->get(route('pembayaran.bukti', $pembayaran))->assertForbidden();
    }

    public function test_generate_page_shows_preview_for_chosen_period(): void
    {
        PembayaranTarif::factory()->create(['nominal' => 175000]);
        $this->santriReguler('Aisyah');
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.pembayaran.generate.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('pembayaran/generate')
                ->where('pratinjau', null)
                ->where('values.periode_mulai', now()->startOfMonth()->toDateString()));

        $this->actingAs($admin)
            ->get(route('admin.pembayaran.generate.create', ['periode_mulai' => '2026-11-01', 'periode_selesai' => '2026-11-30', 'tanggal_jatuh_tempo' => '']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('pratinjau.total_siap_generate', 1)
                ->where('pratinjau.daftar.0.nama_santri', 'Aisyah')
                ->where('galat', null));
    }

    public function test_generate_page_explains_missing_tariff(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.pembayaran.generate.create', ['periode_mulai' => '2026-11-01', 'periode_selesai' => '2026-11-30']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('pratinjau', null)
                ->where('galat', 'Tarif Reguler bulanan tidak ditemukan untuk periode yang dipilih.'));
    }

    public function test_confirming_generate_creates_bills_and_reports_result(): void
    {
        PembayaranTarif::factory()->create();
        $this->santriReguler();
        $this->santriReguler();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.pembayaran.generate.store'), ['periode_mulai' => '2026-11-01', 'periode_selesai' => '2026-11-30'])
            ->assertRedirect(route('admin.pembayaran.index'))
            ->assertSessionHas('success', '2 tagihan berhasil dibuat.');

        $this->assertSame(2, Tagihan::count());
    }
}
