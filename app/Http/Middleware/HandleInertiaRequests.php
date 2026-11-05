<?php

namespace App\Http\Middleware;

use App\Helpers\TanggalHelper;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Data yang tersedia di setiap halaman: pengguna login, pesan sesi, dan tanggal hari ini.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => fn (): ?array => $this->pengguna($request),
            'flash' => fn (): array => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'status' => $request->session()->get('status'),
            ],
            'tanggal' => fn (): array => [
                'iso' => now()->toDateString(),
                'masehi' => TanggalHelper::masehi(),
                'hijriah' => TanggalHelper::hijriah(),
            ],
        ];
    }

    /**
     * Profil ringkas pengguna yang sedang login (null untuk tamu).
     *
     * @return array{id: string, username: string, email: ?string, avatar: ?string, role: string}|null
     */
    private function pengguna(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        return [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'avatar' => $user->avatar_url,
            'role' => $user->role->value,
        ];
    }
}
