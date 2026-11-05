<?php

namespace App\Policies;

use App\Models\Pembayaran;
use App\Models\User;

class PembayaranPolicy
{
    /**
     * Bukti transfer & detail pembayaran: admin atau pemilik tagihannya.
     */
    public function view(User $user, Pembayaran $pembayaran): bool
    {
        return $user->isAdmin() || $pembayaran->tagihan->dimilikiOleh($user);
    }
}
