<?php

namespace App\Services\Supabase;

use App\Enums\UserRole;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

/**
 * Mengubah satu baris Postgres Supabase menjadi baris MySQL: hanya kolom yang ada
 * di tabel tujuan, timestamptz → waktu lokal aplikasi, boolean → 0/1, jsonb → string.
 */
class SupabaseRowTransformer
{
    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $columns  nama kolom MySQL => tipe (`type_name` dari Schema::getColumns)
     * @return array<string, mixed>
     */
    public function transform(array $row, array $columns): array
    {
        $result = [];

        foreach ($columns as $name => $type) {
            if (array_key_exists($name, $row)) {
                $result[$name] = $this->value($row[$name], $type);
            }
        }

        if (array_key_exists('updated_at', $columns) && ($result['updated_at'] ?? null) === null && isset($result['created_at'])) {
            $result['updated_at'] = $result['created_at'];
        }

        return $result;
    }

    /**
     * Baris gabungan `public.profiles` + `auth.users`. Mengembalikan null bila role
     * tidak dikenali (baris dilewati dan dilaporkan).
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, string>  $columns
     * @return array<string, mixed>|null
     */
    public function transformUser(array $row, array $columns): ?array
    {
        if (UserRole::tryFrom((string) ($row['role'] ?? '')) === null) {
            return null;
        }

        $email = Str::lower(trim((string) ($row['auth_email'] ?? $row['email'] ?? '')));

        $row['email'] = $email !== '' ? $email : null;
        $row['password'] = $this->bcryptHash($row['encrypted_password'] ?? null);
        $row['email_verified_at'] = $row['email_confirmed_at'] ?? null;
        $row['created_at'] ??= $row['auth_created_at'] ?? null;
        $row['username'] = filled($row['username'] ?? null)
            ? $row['username']
            : ($email !== '' ? Str::before($email, '@') : 'Pengguna');

        return $this->transform($row, $columns);
    }

    /**
     * Supabase (GoTrue) menyimpan bcrypt dengan prefix `$2a$`; PHP memverifikasi
     * hash yang sama dengan prefix `$2y$`, jadi password lama tetap berlaku.
     */
    public function bcryptHash(?string $hash): ?string
    {
        if ($hash === null || $hash === '') {
            return null;
        }

        if (preg_match('/^\$2[ab]\$/', $hash) === 1) {
            return '$2y$'.substr($hash, 4);
        }

        return str_starts_with($hash, '$2y$') ? $hash : null;
    }

    private function value(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match (true) {
            in_array($type, ['timestamp', 'datetime'], true) => CarbonImmutable::parse((string) $value, 'UTC')
                ->setTimezone(config('app.timezone'))
                ->format('Y-m-d H:i:s'),
            $type === 'date' => substr((string) $value, 0, 10),
            in_array($type, ['tinyint', 'boolean', 'bool'], true) => $this->boolean($value),
            $type === 'json' => is_string($value) ? $value : json_encode($value),
            is_bool($value) => (int) $value,
            default => $value,
        };
    }

    private function boolean(mixed $value): int
    {
        if (is_bool($value)) {
            return (int) $value;
        }

        return (int) in_array(Str::lower((string) $value), ['1', 't', 'true', 'y', 'yes'], true);
    }
}
