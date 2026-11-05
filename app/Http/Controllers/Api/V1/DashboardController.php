<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Services\Absensi\AbsensiService;
use App\Services\Absensi\RingkasanAbsensi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Ringkasan dashboard sesuai role pengguna (data sama dengan dashboard web).
     */
    public function __invoke(Request $request, RingkasanAbsensi $ringkasan, AbsensiService $absensi): JsonResponse
    {
        $user = $request->user();

        $data = match ($user->role) {
            UserRole::Admin, UserRole::Pengajar => $ringkasan->untukPengelola($user),
            UserRole::Santri => $ringkasan->untukSantri($absensi->getRiwayatSantri($user)['items']),
            UserRole::Staff, UserRole::KoordinatorLembaga => [],
        };

        return response()->json(['data' => ['role' => $user->role->value, ...$data]]);
    }
}
