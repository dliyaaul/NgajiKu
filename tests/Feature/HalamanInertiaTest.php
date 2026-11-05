<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HalamanInertiaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_page_with_logout_status(): void
    {
        $this->withSession(['status' => 'Anda berhasil logout dari NgajiKu.'])
            ->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/login')
                ->where('auth', null)
                ->where('flash.status', 'Anda berhasil logout dari NgajiKu.'));
    }

    public function test_unknown_page_renders_application_error_page(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertInertia(fn (Assert $page) => $page
                ->component('errors/error')
                ->where('status', 404));
    }

    public function test_forbidden_page_shows_reason(): void
    {
        $this->actingAs(User::factory()->santri()->create())
            ->get(route('absensi.index'))
            ->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page
                ->component('errors/error')
                ->where('status', 403)
                ->where('message', 'Anda tidak memiliki akses ke halaman ini.'));
    }

    public function test_logged_in_user_and_dates_are_shared_with_every_page(): void
    {
        $pengajar = User::factory()->pengajar()->create(['avatar_url' => 'https://example.com/a.png']);

        $this->actingAs($pengajar)
            ->get(route('pengajar.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.id', $pengajar->id)
                ->where('auth.role', 'pengajar')
                ->where('auth.avatar', 'https://example.com/a.png')
                ->where('tanggal.iso', now()->toDateString())
                ->has('tanggal.masehi'));
    }
}
