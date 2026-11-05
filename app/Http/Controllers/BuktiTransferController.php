<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Services\Pembayaran\PembayaranService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BuktiTransferController extends Controller
{
    /**
     * Menampilkan bukti transfer (privat) kepada admin atau pemilik tagihan.
     */
    public function __invoke(Pembayaran $pembayaran): StreamedResponse
    {
        Gate::authorize('view', $pembayaran);

        $disk = Storage::disk(PembayaranService::DISK);

        abort_unless(
            $disk->exists($pembayaran->bukti_transfer),
            404,
            'File bukti transfer belum tersedia di server. File lama mungkin masih tersimpan di Supabase Storage.'
        );

        return $disk->response($pembayaran->bukti_transfer, basename($pembayaran->bukti_transfer), [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
