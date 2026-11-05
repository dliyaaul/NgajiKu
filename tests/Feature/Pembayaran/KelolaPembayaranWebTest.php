<?php

namespace Tests\Feature\Pembayaran;

use App\Enums\StatusPembayaran;
use App\Enums\StatusTagihan;
use App\Models\Pembayaran;
use App\Models\PembayaranRekening;
use App\Models\PembayaranTarif;
use App\Models\SantriProgram;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class KelolaPembayaranWebTest extends TestCase
{
    use InteractsWithPembayaran, RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_verification_queue_lists_waiting_payments_oldest_first(): void
    {
        $lama = Pembayaran::factory()->create(['created_at' => now()->subDay()]);
        Pembayaran::factory()->create();
        Pembayaran::factory()->create(['status' => StatusPembayaran::Diterima]);

        $this->actingAs($this->admin())
            ->get(route('admin.pembayaran.verifikasi.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('pembayaran/verifikasi')
                ->where('filter', 'menunggu_verifikasi')
                ->has('pembayaran', 2)
                ->where('pembayaran.0.id', $lama->id)
                ->where('jumlahPerStatus.diterima', 1));
    }

    public function test_admin_accepts_payment_from_web(): void
    {
        $tagihan = Tagihan::factory()->status(StatusTagihan::MenungguVerifikasi)->create();
        $pembayaran = Pembayaran::factory()->create(['tagihan_id' => $tagihan->id]);

        $this->actingAs($this->admin())
            ->from(route('admin.pembayaran.verifikasi.index'))
            ->put(route('admin.pembayaran.verifikasi.update', $pembayaran), ['status' => 'diterima'])
            ->assertRedirect(route('admin.pembayaran.verifikasi.index'))
            ->assertSessionHas('success', fn (string $pesan) => str_contains($pesan, 'Tagihan ditandai lunas'));

        $this->assertSame(StatusTagihan::Lunas, $tagihan->refresh()->status);
    }

    public function test_rejecting_from_web_requires_reason(): void
    {
        $pembayaran = Pembayaran::factory()->create();

        $this->actingAs($this->admin())
            ->put(route('admin.pembayaran.verifikasi.update', $pembayaran), ['status' => 'ditolak', 'catatan' => ''])
            ->assertSessionHasErrors('catatan');

        $this->assertSame(StatusPembayaran::MenungguVerifikasi, $pembayaran->refresh()->status);
    }

    public function test_verifying_twice_shows_friendly_error(): void
    {
        $pembayaran = Pembayaran::factory()->create(['status' => StatusPembayaran::Diterima]);

        $this->actingAs($this->admin())
            ->from(route('admin.pembayaran.verifikasi.index'))
            ->put(route('admin.pembayaran.verifikasi.update', $pembayaran), ['status' => 'diterima'])
            ->assertRedirect(route('admin.pembayaran.verifikasi.index'))
            ->assertSessionHas('error', 'Pembayaran ini sudah diverifikasi atau tidak lagi menunggu verifikasi.');
    }

    public function test_admin_manages_accounts_from_web(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.pembayaran.rekening.store'), ['nama_bank' => 'BSI', 'nomor_rekening' => '7123', 'nama_pemilik' => 'Yayasan', 'urutan' => 1, 'aktif' => true])
            ->assertSessionHas('success');

        $rekening = PembayaranRekening::sole();

        $this->actingAs($admin)
            ->put(route('admin.pembayaran.rekening.update', $rekening), ['nama_bank' => 'BRI', 'nomor_rekening' => '7123', 'nama_pemilik' => 'Yayasan'])
            ->assertSessionHas('success');
        $this->actingAs($admin)
            ->patch(route('admin.pembayaran.rekening.aktif', $rekening), ['aktif' => false])
            ->assertSessionHas('success');

        $this->assertSame(['BRI', false], [$rekening->refresh()->nama_bank, $rekening->aktif]);

        $this->actingAs($admin)
            ->get(route('admin.pembayaran.rekening.index'))
            ->assertInertia(fn (Assert $page) => $page->component('pembayaran/rekening')->where('rekening.0.nama_bank', 'BRI'));

        $this->actingAs($admin)
            ->post(route('admin.pembayaran.rekening.store'), [])
            ->assertSessionHasErrors(['nama_bank', 'nomor_rekening', 'nama_pemilik']);
    }

    public function test_tariff_list_shows_target_of_each_tariff(): void
    {
        PembayaranTarif::factory()->create(['nama_tarif' => 'SPP Reguler']);
        $privat = SantriProgram::factory()->create(['kelas_type_id' => $this->jenisLayanan('Privat')->id]);
        PembayaranTarif::factory()->create([
            'nama_tarif' => 'Privat Aisyah',
            'kelas_type_id' => $privat->kelas_type_id,
            'santri_program_id' => $privat->id,
            'satuan' => 'pertemuan',
            'aktif' => false,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.pembayaran.tarif.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('pembayaran/tarif/index')
                ->has('tarif', 2)
                ->where('tarif.0.target', 'Semua santri')
                ->where('tarif.0.jenis', 'reguler')
                ->where('tarif.1.target', $privat->santri->username));
    }

    public function test_admin_creates_and_edits_tariff_from_web(): void
    {
        $admin = $this->admin();
        $reguler = $this->jenisLayanan('Reguler');

        $this->actingAs($admin)
            ->get(route('admin.pembayaran.tarif.create'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('pembayaran/tarif/form')
                ->where('tarifId', null)
                ->where('opsi.jenis_layanan.0.jenis', 'reguler'));

        $this->actingAs($admin)
            ->post(route('admin.pembayaran.tarif.store'), [
                'nama_tarif' => 'SPP',
                'kelas_type_id' => $reguler->id,
                'nominal' => '150000',
                'satuan' => 'bulan',
                'berlaku_mulai' => '2026-01-01',
                'aktif' => true,
            ])
            ->assertRedirect(route('admin.pembayaran.tarif.index'));

        $tarif = PembayaranTarif::sole();

        $this->actingAs($admin)
            ->get(route('admin.pembayaran.tarif.edit', $tarif))
            ->assertInertia(fn (Assert $page) => $page->where('tarifId', $tarif->id)->where('values.nominal', '150000'));

        $this->actingAs($admin)
            ->put(route('admin.pembayaran.tarif.update', $tarif), [
                'nama_tarif' => 'SPP Baru',
                'kelas_type_id' => $reguler->id,
                'nominal' => '175000',
                'satuan' => 'bulan',
                'berlaku_mulai' => '2026-01-01',
            ])
            ->assertRedirect(route('admin.pembayaran.tarif.index'));

        $this->assertSame(['SPP Baru', '175000.00'], [$tarif->refresh()->nama_tarif, $tarif->nominal]);
    }
}
