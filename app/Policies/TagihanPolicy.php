<?php

namespace App\Policies;

use App\Enums\StatusTagihan;
use App\Models\Tagihan;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TagihanPolicy
{
    /**
     * Admin melihat semua tagihan; pembayar hanya tagihannya sendiri.
     */
    public function view(User $user, Tagihan $tagihan): bool
    {
        return $user->isAdmin() || $tagihan->dimilikiOleh($user);
    }

    /**
     * Hanya pemilik tagihan yang mengajukan pembayaran, selama tagihan masih bisa dibayar.
     */
    public function bayar(User $user, Tagihan $tagihan): Response
    {
        if (! $tagihan->dimilikiOleh($user)) {
            return Response::deny('Anda tidak dapat membayar tagihan ini.');
        }

        return match ($tagihan->status) {
            StatusTagihan::MenungguVerifikasi => Response::deny('Pembayaran tagihan ini sedang menunggu verifikasi.'),
            StatusTagihan::Lunas => Response::deny('Tagihan ini sudah lunas.'),
            StatusTagihan::Dibatalkan => Response::deny('Tagihan ini sudah dibatalkan.'),
            StatusTagihan::BelumBayar, StatusTagihan::Ditolak => Response::allow(),
        };
    }
}
