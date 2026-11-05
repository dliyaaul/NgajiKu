<?php

namespace Tests\Feature\Api\V1;

use App\Enums\StatusPembayaran;
use App\Enums\StatusTagihan;
use App\Models\Lembaga;
use App\Models\Pembayaran;
use App\Models\PembayaranRekening;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Pembayaran\InteractsWithPembayaran;
use Tests\TestCase;

class PembayaranApiTest extends TestCase
{
    use InteractsWithPembayaran, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('bukti_transfer');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function ajukan(Tagihan $tagihan, array $overrides = []): TestResponse
    {
        return $this->post(route('api.v1.tagihan.bayar', $tagihan), [
            'rekening_tujuan_id' => PembayaranRekening::factory()->create()->id,
            'tanggal_transfer' => now()->toDateString(),
            'nominal_transfer' => 150000,
            'nama_pengirim' => 'Ayah Aisyah',
            'bukti_transfer' => UploadedFile::fake()->image('bukti.jpg'),
            ...$overrides,
        ], ['Accept' => 'application/json']);
    }

    private function tagihanSantri(StatusTagihan $status = StatusTagihan::BelumBayar): Tagihan
    {
        return Tagihan::factory()->untukSantri($this->santriReguler())->status($status)->create(['total' => 150000]);
    }

    public function test_santri_submits_payment_with_proof(): void
    {
        $tagihan = $this->tagihanSantri();
        $santri = $tagihan->santriProgram->santri;
        Sanctum::actingAs($santri);

        $this->ajukan($tagihan)
            ->assertCreated()
            ->assertJsonPath('data.status', 'menunggu_verifikasi')
            ->assertJsonPath('data.nominal_transfer', 150000)
            ->assertJsonPath('data.log.0.catatan', 'Pembayaran diajukan.');

        $pembayaran = Pembayaran::sole();
        $this->assertStringStartsWith($santri->id.'/', $pembayaran->bukti_transfer);
        $this->assertStringEndsWith('.jpg', $pembayaran->bukti_transfer);
        Storage::disk('bukti_transfer')->assertExists($pembayaran->bukti_transfer);
        $this->assertSame(StatusTagihan::MenungguVerifikasi, $tagihan->refresh()->status);
        $this->assertSame($santri->id, $pembayaran->created_by);
    }

    public function test_koordinator_pays_bill_of_own_lembaga(): void
    {
        $lembaga = Lembaga::factory()->create();
        $tagihan = Tagihan::factory()->untukLembaga($lembaga)->create(['total' => 150000]);
        Sanctum::actingAs($this->koordinator($lembaga));

        $this->ajukan($tagihan)->assertCreated();
    }

    public function test_nominal_must_match_bill_total(): void
    {
        $tagihan = $this->tagihanSantri();
        Sanctum::actingAs($tagihan->santriProgram->santri);

        $this->ajukan($tagihan, ['nominal_transfer' => 100000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['nominal_transfer' => 'Rp 150.000']);

        $this->assertSame(0, Pembayaran::count());
        $this->assertEmpty(Storage::disk('bukti_transfer')->allFiles());
    }

    public function test_proof_must_be_image_or_pdf_up_to_10_mb(): void
    {
        $tagihan = $this->tagihanSantri();
        Sanctum::actingAs($tagihan->santriProgram->santri);

        $this->ajukan($tagihan, ['bukti_transfer' => UploadedFile::fake()->create('virus.exe', 10)])
            ->assertJsonValidationErrors('bukti_transfer');
        $this->ajukan($tagihan, ['bukti_transfer' => UploadedFile::fake()->create('besar.pdf', 10241, 'application/pdf')])
            ->assertJsonValidationErrors('bukti_transfer');
        $this->ajukan($tagihan, ['bukti_transfer' => UploadedFile::fake()->create('bukti.pdf', 500, 'application/pdf')])
            ->assertCreated();
    }

    public function test_inactive_account_cannot_be_chosen(): void
    {
        $tagihan = $this->tagihanSantri();
        Sanctum::actingAs($tagihan->santriProgram->santri);

        $this->ajukan($tagihan, ['rekening_tujuan_id' => PembayaranRekening::factory()->create(['aktif' => false])->id])
            ->assertJsonValidationErrors('rekening_tujuan_id');
    }

    public function test_bill_that_is_waiting_or_paid_cannot_be_paid_again(): void
    {
        $pesan = [
            'Pembayaran tagihan ini sedang menunggu verifikasi.' => StatusTagihan::MenungguVerifikasi,
            'Tagihan ini sudah lunas.' => StatusTagihan::Lunas,
            'Tagihan ini sudah dibatalkan.' => StatusTagihan::Dibatalkan,
        ];

        foreach ($pesan as $harapan => $status) {
            $tagihan = $this->tagihanSantri($status);
            Sanctum::actingAs($tagihan->santriProgram->santri);

            $this->ajukan($tagihan)
                ->assertForbidden()
                ->assertJsonPath('message', $harapan);
        }

        $this->assertSame(0, Pembayaran::count());
    }

    public function test_other_users_and_admin_cannot_pay_someone_elses_bill(): void
    {
        $tagihan = $this->tagihanSantri();

        foreach ([User::factory()->santri()->create(), User::factory()->admin()->create()] as $user) {
            Sanctum::actingAs($user);
            $this->ajukan($tagihan)->assertForbidden();
        }
    }

    public function test_admin_accepts_payment_and_bill_becomes_paid(): void
    {
        $pembayaran = Pembayaran::factory()->create(['tagihan_id' => $this->tagihanSantri(StatusTagihan::MenungguVerifikasi)->id]);
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $this->postJson(route('api.v1.pembayaran.verifikasi', $pembayaran), ['status' => 'diterima'])
            ->assertOk()
            ->assertJsonPath('data.status', 'diterima')
            ->assertJsonPath('data.log.0.status_lama', 'menunggu_verifikasi')
            ->assertJsonPath('data.log.0.status_baru', 'diterima');

        $pembayaran->refresh();
        $this->assertSame($admin->id, $pembayaran->diverifikasi_oleh);
        $this->assertNotNull($pembayaran->diverifikasi_at);
        $this->assertSame(StatusTagihan::Lunas, $pembayaran->tagihan->status);
    }

    public function test_rejection_needs_reason_and_lets_payer_resubmit(): void
    {
        $tagihan = $this->tagihanSantri(StatusTagihan::MenungguVerifikasi);
        $pembayaran = Pembayaran::factory()->create(['tagihan_id' => $tagihan->id]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson(route('api.v1.pembayaran.verifikasi', $pembayaran), ['status' => 'ditolak'])
            ->assertJsonValidationErrors(['catatan' => 'Alasan penolakan wajib diisi.']);

        $this->postJson(route('api.v1.pembayaran.verifikasi', $pembayaran), ['status' => 'ditolak', 'catatan' => 'Bukti buram'])
            ->assertOk()
            ->assertJsonPath('data.alasan_penolakan', 'Bukti buram');

        $this->assertSame(StatusTagihan::Ditolak, $tagihan->refresh()->status);

        Sanctum::actingAs($tagihan->santriProgram->santri);
        $this->ajukan($tagihan)->assertCreated();
        $this->assertSame(StatusTagihan::MenungguVerifikasi, $tagihan->refresh()->status);
    }

    public function test_payment_cannot_be_verified_twice(): void
    {
        $pembayaran = Pembayaran::factory()->create(['status' => StatusPembayaran::Diterima]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson(route('api.v1.pembayaran.verifikasi', $pembayaran), ['status' => 'ditolak', 'catatan' => 'x'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Pembayaran ini sudah diverifikasi atau tidak lagi menunggu verifikasi.');
    }

    public function test_accepting_payment_with_wrong_amount_is_refused(): void
    {
        $tagihan = $this->tagihanSantri(StatusTagihan::MenungguVerifikasi);
        $pembayaran = Pembayaran::factory()->create(['tagihan_id' => $tagihan->id, 'nominal_transfer' => 100000]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->postJson(route('api.v1.pembayaran.verifikasi', $pembayaran), ['status' => 'diterima'])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Nominal transfer tidak sesuai dengan total tagihan.');

        $this->assertSame(StatusTagihan::MenungguVerifikasi, $tagihan->refresh()->status);
    }

    public function test_only_admin_lists_and_verifies_payments(): void
    {
        $lama = Pembayaran::factory()->create(['created_at' => now()->subDay()]);
        $baru = Pembayaran::factory()->create();
        Pembayaran::factory()->create(['status' => StatusPembayaran::Diterima]);
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson(route('api.v1.pembayaran.index'))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $lama->id)
            ->assertJsonPath('data.1.id', $baru->id);

        Sanctum::actingAs($lama->tagihan->santriProgram->santri);

        $this->getJson(route('api.v1.pembayaran.index'))->assertForbidden();
        $this->postJson(route('api.v1.pembayaran.verifikasi', $lama), ['status' => 'diterima'])->assertForbidden();
    }

    public function test_proof_file_is_only_served_to_admin_and_owner(): void
    {
        $pembayaran = Pembayaran::factory()->create(['bukti_transfer' => 'pemilik/123.jpg']);
        Storage::disk('bukti_transfer')->put('pemilik/123.jpg', 'isi-gambar');

        Sanctum::actingAs($pembayaran->tagihan->santriProgram->santri);
        $this->get(route('api.v1.pembayaran.bukti', $pembayaran))->assertOk();

        Sanctum::actingAs(User::factory()->admin()->create());
        $this->get(route('api.v1.pembayaran.bukti', $pembayaran))->assertOk();

        Sanctum::actingAs(User::factory()->santri()->create());
        $this->getJson(route('api.v1.pembayaran.bukti', $pembayaran))->assertForbidden();
    }

    public function test_missing_proof_file_returns_not_found(): void
    {
        $pembayaran = Pembayaran::factory()->create();
        Sanctum::actingAs(User::factory()->admin()->create());

        $this->getJson(route('api.v1.pembayaran.bukti', $pembayaran))
            ->assertNotFound()
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'Supabase Storage'));
    }

    public function test_bill_detail_includes_items_and_payment_history(): void
    {
        $tagihan = $this->tagihanSantri(StatusTagihan::Ditolak);
        $tagihan->items()->create(['deskripsi' => 'SPP Reguler', 'jenis' => 'bulanan', 'satuan' => 'bulan', 'harga_satuan' => 150000, 'subtotal' => 150000]);
        Pembayaran::factory()->create(['tagihan_id' => $tagihan->id, 'status' => StatusPembayaran::Ditolak, 'alasan_penolakan' => 'Bukti buram']);
        Sanctum::actingAs($tagihan->santriProgram->santri);

        $this->getJson(route('api.v1.tagihan.show', $tagihan))
            ->assertOk()
            ->assertJsonPath('data.items.0.deskripsi', 'SPP Reguler')
            ->assertJsonPath('data.pembayaran.0.alasan_penolakan', 'Bukti buram')
            ->assertJsonPath('data.bisa_dibayar', true);
    }
}
