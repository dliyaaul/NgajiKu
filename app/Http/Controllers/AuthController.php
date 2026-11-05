<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Menampilkan halaman login. Pengguna yang sudah masuk diarahkan ke dashboard-nya.
     */
    public function showLogin(Request $request): Response|RedirectResponse
    {
        if (($user = $request->user()) && $user->role->bisaMemakaiWeb()) {
            return to_route($user->role->dashboardRoute());
        }

        return Inertia::render('auth/login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $user = $request->authenticate();

        if (! $user->role->bisaMemakaiWeb()) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Akun koordinator lembaga hanya dapat digunakan di aplikasi mobile NgajiKu.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route($user->role->dashboardRoute()));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login')->with('status', 'Anda berhasil logout dari NgajiKu.');
    }
}
