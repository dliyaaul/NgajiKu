<?php

namespace App\Services\Pembayaran;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pelanggaran aturan tagihan/pembayaran yang pesannya aman ditampilkan
 * (mis. tagihan sudah lunas, tarif reguler tidak ditemukan).
 */
class PembayaranException extends RuntimeException
{
    public function render(Request $request): Response
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return new JsonResponse(['message' => $this->getMessage()], 422);
        }

        return back()
            ->withInput()
            ->with('error', $this->getMessage());
    }

    /**
     * Pelanggaran aturan bisnis, bukan galat sistem; tidak perlu dilaporkan.
     */
    public function report(): bool
    {
        return true;
    }
}
