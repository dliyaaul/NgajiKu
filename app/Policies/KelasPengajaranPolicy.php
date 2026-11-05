<?php

namespace App\Policies;

use App\Models\KelasPengajaran;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class KelasPengajaranPolicy
{
    /**
     * Mengelola pertemuan & absensi kelas: admin untuk semua kelas,
     * pengajar hanya untuk kelas yang diampunya.
     */
    public function kelolaAbsensi(User $user, KelasPengajaran $kelas): Response
    {
        if ($user->isAdmin() || ($user->isPengajar() && $kelas->pengajar_id === $user->id)) {
            return Response::allow();
        }

        return Response::deny('Anda tidak memiliki akses ke kelas ini.');
    }
}
