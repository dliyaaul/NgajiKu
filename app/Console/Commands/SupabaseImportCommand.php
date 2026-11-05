<?php

namespace App\Console\Commands;

use App\Services\Supabase\SupabaseRowTransformer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

#[Signature('supabase:import
    {--table=* : Hanya impor tabel tertentu (nama tabel MySQL)}
    {--fresh : Kosongkan tabel tujuan sebelum impor}
    {--force : Jalankan tanpa konfirmasi}')]
#[Description('Menyalin data Supabase (Postgres) ke database aplikasi dengan ID yang sama')]
class SupabaseImportCommand extends Command
{
    private const BATCH = 500;

    /**
     * Urutan impor (induk lebih dulu). `users` dibentuk dari `profiles` + `auth.users`.
     *
     * @var list<string>
     */
    public const TABLES = [
        'programs',
        'kelas_types',
        'users',
        'lembaga',
        'pengajar_programs',
        'pengajar_pengganti',
        'kelas_pengajaran',
        'santri_programs',
        'riwayat_kelas_santri',
        'bbaq_pertemuan',
        'tahfidz_pertemuan',
        'tarjamah_pertemuan',
        'absensi',
        'absensi_tahfidz',
        'absensi_tarjamah',
        'app_settings',
        'pembayaran_rekening',
        'pembayaran_tarif',
        'tagihan',
        'tagihan_item',
        'pembayaran',
        'pembayaran_log',
    ];

    public function handle(SupabaseRowTransformer $transformer): int
    {
        if (! extension_loaded('pdo_pgsql')) {
            $this->components->error('Ekstensi PHP pdo_pgsql belum aktif. Aktifkan `extension=pdo_pgsql` di php.ini lalu ulangi.');

            return self::FAILURE;
        }

        $tables = $this->option('table') ?: self::TABLES;
        $unknown = array_diff($tables, self::TABLES);

        if ($unknown !== []) {
            $this->components->error('Tabel tidak dikenal: '.implode(', ', $unknown));

            return self::FAILURE;
        }

        $tables = array_values(array_intersect(self::TABLES, $tables));
        $belumAda = array_values(array_filter($tables, fn (string $table) => ! Schema::hasTable($table)));

        if ($belumAda !== []) {
            $this->components->error('Tabel tujuan belum ada: '.implode(', ', $belumAda).'. Jalankan `php artisan migrate` terlebih dahulu.');

            return self::FAILURE;
        }

        $target = DB::connection();

        $this->components->info(sprintf(
            'Impor %d tabel dari Supabase ke [%s] %s.',
            count($tables),
            $target->getDriverName(),
            $target->getDatabaseName(),
        ));

        if (! $this->option('force') && ! $this->confirm('Lanjutkan? Baris dengan ID yang sama akan ditimpa.', true)) {
            return self::FAILURE;
        }

        try {
            $source = DB::connection('supabase');
            $source->getPdo();
        } catch (Throwable $e) {
            $this->components->error('Tidak dapat terhubung ke Supabase: '.$e->getMessage());

            return self::FAILURE;
        }

        Schema::disableForeignKeyConstraints();

        try {
            if ($this->option('fresh')) {
                foreach (array_reverse($tables) as $table) {
                    DB::table($table)->truncate();
                }
            }

            $hasil = [];

            foreach ($tables as $table) {
                $hasil[] = [$table, ...$this->importTable($source, $table, $transformer)];
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->table(['Tabel', 'Diimpor', 'Dilewati', 'Catatan'], $hasil);
        $this->reportOrphans($tables);

        return self::SUCCESS;
    }

    /**
     * @return array{0: int, 1: int, 2: string}
     */
    private function importTable(Connection $source, string $table, SupabaseRowTransformer $transformer): array
    {
        $sourceTable = $table === 'users' ? 'profiles' : $table;

        if (! $source->getSchemaBuilder()->hasTable($sourceTable)) {
            return [0, 0, 'tidak ada di Supabase'];
        }

        $columns = collect(Schema::getColumns($table))->pluck('type_name', 'name')->all();
        $imported = 0;
        $skipped = 0;
        $batch = [];

        $flush = function () use ($table, &$batch, &$imported) {
            if ($batch !== []) {
                DB::table($table)->upsert($batch, ['id']);
                $imported += count($batch);
                $batch = [];
            }
        };

        foreach ($this->sourceQuery($source, $table)->lazy(self::BATCH) as $row) {
            $row = (array) $row;
            $mapped = $table === 'users'
                ? $transformer->transformUser($row, $columns)
                : $transformer->transform($row, $columns);

            if ($mapped === null || ! isset($mapped['id'])) {
                $skipped++;

                continue;
            }

            $batch[] = $mapped;

            if (count($batch) >= self::BATCH) {
                $flush();
            }
        }

        $flush();

        $note = match (true) {
            $table === 'users' && $skipped > 0 => 'role tidak valid dilewati',
            $table === 'users' => 'password Supabase dipertahankan',
            default => '',
        };

        return [$imported, $skipped, $note];
    }

    private function sourceQuery(Connection $source, string $table): Builder
    {
        if ($table !== 'users') {
            return $source->table($table)->orderBy('id');
        }

        return $source->table('profiles as p')
            ->leftJoin('auth.users as u', 'u.id', '=', 'p.id')
            ->select([
                'p.*',
                'u.email as auth_email',
                'u.encrypted_password',
                'u.email_confirmed_at',
                'u.created_at as auth_created_at',
            ])
            ->orderBy('p.id');
    }

    /**
     * Pemeriksaan foreign key menyala lagi tidak memvalidasi baris lama, jadi
     * laporkan baris yang menunjuk ke data yang tidak ada.
     *
     * @param  list<string>  $tables
     */
    private function reportOrphans(array $tables): void
    {
        $orphans = [];

        foreach ($tables as $table) {
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                $column = $foreignKey['columns'][0];
                $foreignTable = $foreignKey['foreign_table'];
                $foreignColumn = $foreignKey['foreign_columns'][0];

                $count = DB::table($table)
                    ->whereNotNull($column)
                    ->whereNotExists(fn (Builder $query) => $query->selectRaw('1')
                        ->from($foreignTable)
                        ->whereColumn("{$foreignTable}.{$foreignColumn}", "{$table}.{$column}"))
                    ->count();

                if ($count > 0) {
                    $orphans[] = ["{$table}.{$column}", $foreignTable, $count];
                }
            }
        }

        if ($orphans === []) {
            $this->components->info('Semua relasi valid.');

            return;
        }

        $this->components->warn('Ada baris yang menunjuk ke data yang tidak ada (periksa sebelum produksi):');
        $this->table(['Kolom', 'Menunjuk ke', 'Jumlah'], $orphans);
    }
}
