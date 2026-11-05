<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_is_redirected_to_dashboard_of_their_role(): void
    {
        $pengajar = User::factory()->pengajar()->create(['email' => 'ustadz@example.com']);

        $this->post(route('login.process'), ['email' => 'Ustadz@Example.com', 'password' => 'password'])
            ->assertRedirect(route('pengajar.dashboard'));

        $this->assertAuthenticatedAs($pengajar);
    }

    public function test_wrong_password_is_rejected(): void
    {
        User::factory()->create(['email' => 'santri@example.com']);

        $this->post(route('login.process'), ['email' => 'santri@example.com', 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Login gagal. Periksa email dan password Anda.']);

        $this->assertGuest();
    }

    public function test_imported_user_without_password_cannot_log_in(): void
    {
        User::factory()->create(['email' => 'lama@example.com', 'password' => null]);

        $this->post(route('login.process'), ['email' => 'lama@example.com', 'password' => ''])
            ->assertSessionHasErrors('password');

        $this->post(route('login.process'), ['email' => 'lama@example.com', 'password' => 'apa saja'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        User::factory()->create(['email' => 'santri@example.com']);

        foreach (range(1, 5) as $percobaan) {
            $this->post(route('login.process'), ['email' => 'santri@example.com', 'password' => 'salah']);
        }

        $this->post(route('login.process'), ['email' => 'santri@example.com', 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertStringContainsString('Terlalu banyak percobaan', session('errors')->first('email'));
        $this->assertGuest();
    }

    public function test_koordinator_lembaga_is_told_to_use_the_mobile_app(): void
    {
        User::factory()->role(UserRole::KoordinatorLembaga)->create(['email' => 'koordinator@example.com']);

        $this->post(route('login.process'), ['email' => 'koordinator@example.com', 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Akun koordinator lembaga hanya dapat digunakan di aplikasi mobile NgajiKu.']);

        $this->assertGuest();
    }

    public function test_logged_in_user_opening_login_page_goes_to_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->santri()->create())
            ->post(route('logout'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Anda berhasil logout dari NgajiKu.');

        $this->assertGuest();
    }
}
