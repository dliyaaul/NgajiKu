<?php

namespace Tests\Feature\Supabase;

use App\Models\User;
use App\Services\Supabase\SupabaseRowTransformer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SupabaseRowTransformerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function kolom(string $table): array
    {
        return collect(Schema::getColumns($table))->pluck('type_name', 'name')->all();
    }

    public function test_postgres_values_are_converted_for_the_target_columns(): void
    {
        $row = (new SupabaseRowTransformer)->transform([
            'id' => 'b7f1c1d2-0000-4000-8000-000000000001',
            'nama_kelas' => 'Kelas Al-Fatih',
            'aktif' => true,
            'created_at' => '2026-11-05 01:30:00+00',
            'updated_at' => null,
            'kolom_yang_tidak_ada' => 'x',
        ], $this->kolom('kelas_pengajaran'));

        $this->assertSame([
            'id' => 'b7f1c1d2-0000-4000-8000-000000000001',
            'nama_kelas' => 'Kelas Al-Fatih',
            'aktif' => 1,
            'created_at' => '2026-11-05 08:30:00',
            'updated_at' => '2026-11-05 08:30:00',
        ], $row);
    }

    public function test_jsonb_and_date_columns_are_normalized(): void
    {
        $row = (new SupabaseRowTransformer)->transform([
            'tanggal' => '2026-11-05',
            'metadata' => ['pertemuan' => 4],
            'aktif' => 'f',
        ], ['tanggal' => 'date', 'metadata' => 'json', 'aktif' => 'tinyint']);

        $this->assertSame(['tanggal' => '2026-11-05', 'metadata' => '{"pertemuan":4}', 'aktif' => 0], $row);
    }

    public function test_supabase_bcrypt_password_still_works_after_import(): void
    {
        $supabaseHash = '$2a$'.substr(password_hash('rahasia123', PASSWORD_BCRYPT), 4);

        $row = (new SupabaseRowTransformer)->transformUser([
            'id' => 'b7f1c1d2-0000-4000-8000-000000000002',
            'username' => 'Ustadz Ahmad',
            'role' => 'pengajar',
            'email' => null,
            'auth_email' => 'Ustadz@Example.com',
            'encrypted_password' => $supabaseHash,
            'email_confirmed_at' => '2026-01-01 00:00:00+00',
            'created_at' => '2026-01-01 00:00:00+00',
        ], $this->kolom('users'));

        User::query()->insert($row);

        $this->post(route('login.process'), ['email' => 'ustadz@example.com', 'password' => 'rahasia123'])
            ->assertRedirect(route('pengajar.dashboard'));
    }

    public function test_profile_with_unknown_role_is_skipped(): void
    {
        $this->assertNull((new SupabaseRowTransformer)->transformUser(['id' => 'x', 'role' => 'tamu'], $this->kolom('users')));
    }

    public function test_profile_without_username_falls_back_to_email_name(): void
    {
        $row = (new SupabaseRowTransformer)->transformUser(
            ['id' => 'x', 'role' => 'santri', 'username' => '', 'auth_email' => 'aisyah@example.com'],
            $this->kolom('users')
        );

        $this->assertSame('aisyah', $row['username']);
        $this->assertNull($row['password']);
    }
}
