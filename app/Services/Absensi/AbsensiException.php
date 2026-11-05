<?php

namespace App\Services\Absensi;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kesalahan aturan absensi yang pesannya aman ditampilkan kepada pengguna
 * (mis. batas waktu edit habis, santri bukan anggota kelas).
 */
class AbsensiException extends RuntimeException
{
    public function render(Request $request): Response
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return new JsonResponse(['message' => $this->getMessage()], 422);
        }

        if ($request->isMethod('GET')) {
            return Inertia::render('absensi/error', [
                'message' => $this->getMessage(),
            ])->toResponse($request)->setStatusCode(422);
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
