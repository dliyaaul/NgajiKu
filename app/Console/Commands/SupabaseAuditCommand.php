<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

#[Signature('supabase:audit')]
#[Description('Membandingkan skema Supabase dengan database aplikasi dan menyimpan fungsi, trigger, RLS, serta constraint Supabase')]
class SupabaseAuditCommand extends Command
{
    /**
     * Tabel Supabase yang berganti nama di MySQL.
     *
     * @var array<string, string>
     */
    private const NAMA_TABEL = ['profiles' => 'users'];

    public function handle(): int
    {
        if (! extension_loaded('pdo_pgsql')) {
            $this->components->error('Ekstensi PHP pdo_pgsql belum aktif. Aktifkan `extension=pdo_pgsql` di php.ini lalu ulangi.');

            return self::FAILURE;
        }

        try {
            $source = DB::connection('supabase');
            $source->getPdo();
        } catch (Throwable $e) {
            $this->components->error('Tidak dapat terhubung ke Supabase: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! Schema::hasTable('migrations')) {
            $this->components->warn('Database aplikasi belum dimigrasi, jadi semua kolom akan tertulis "belum ada di MySQL". Jalankan `php artisan migrate` untuk perbandingan yang akurat.');
        }

        $laporan = ['# Audit database Supabase → '.DB::connection()->getDriverName(), '', 'Dibuat: '.now()->toDateTimeString(), ''];
        $ringkasan = [];

        $columns = collect($source->select(
            "select table_name, column_name, data_type, is_nullable, column_default
             from information_schema.columns
             where table_schema = 'public'
             order by table_name, ordinal_position"
        ))->groupBy('table_name');

        $laporan[] = '## Tabel & kolom';

        foreach ($columns as $table => $tableColumns) {
            $target = self::NAMA_TABEL[$table] ?? $table;
            $ada = Schema::hasTable($target);
            $targetColumns = $ada ? Schema::getColumnListing($target) : [];
            $hilang = $tableColumns->pluck('column_name')->diff($targetColumns)->values();
            $jumlah = (int) $source->table($table)->count();

            $ringkasan[] = [$table, $ada ? $target : '✗ TIDAK ADA', $jumlah, $hilang->isEmpty() ? '-' : $hilang->implode(', ')];

            $laporan[] = '';
            $laporan[] = "### {$table} → ".($ada ? $target : '**belum ada di MySQL**')." ({$jumlah} baris)";
            $laporan[] = '';
            $laporan[] = '| Kolom | Tipe Postgres | Null | Default | Di MySQL |';
            $laporan[] = '| --- | --- | --- | --- | --- |';

            foreach ($tableColumns as $column) {
                $laporan[] = sprintf(
                    '| %s | %s | %s | %s | %s |',
                    $column->column_name,
                    $column->data_type,
                    $column->is_nullable,
                    str_replace('|', '\|', (string) $column->column_default),
                    in_array($column->column_name, $targetColumns, true) ? '✓' : '✗',
                );
            }
        }

        $this->table(['Tabel Supabase', 'Tabel MySQL', 'Baris', 'Kolom belum ada di MySQL'], $ringkasan);

        $laporan = [...$laporan, ...$this->bagian($source, 'Trigger', ['Tabel', 'Trigger', 'Waktu', 'Event', 'Aksi'],
            "select event_object_table, trigger_name, action_timing, event_manipulation, action_statement
             from information_schema.triggers where trigger_schema = 'public' order by 1, 2")];

        $laporan = [...$laporan, ...$this->bagian($source, 'Policy RLS', ['Tabel', 'Policy', 'Perintah', 'Role', 'USING', 'WITH CHECK'],
            "select tablename, policyname, cmd, array_to_string(roles, ','), qual, with_check
             from pg_policies where schemaname = 'public' order by 1, 2")];

        $laporan = [...$laporan, ...$this->bagian($source, 'Check constraint', ['Tabel', 'Constraint', 'Definisi'],
            "select conrelid::regclass::text, conname, pg_get_constraintdef(oid)
             from pg_constraint where contype = 'c' and connamespace = 'public'::regnamespace order by 1, 2")];

        $laporan = [...$laporan, ...$this->bagian($source, 'Bucket storage', ['Bucket', 'Publik', 'Jumlah file'],
            'select b.id, b.public, (select count(*) from storage.objects o where o.bucket_id = b.id)
             from storage.buckets b order by 1')];

        $functions = $source->select(
            "select p.proname, pg_get_functiondef(p.oid) as definisi
             from pg_proc p join pg_namespace n on n.oid = p.pronamespace
             where n.nspname = 'public' and p.prokind = 'f'
             order by 1"
        );

        $laporan[] = '';
        $laporan[] = '## Fungsi (RPC & trigger function)';
        $laporan[] = '';
        $laporan[] = count($functions).' fungsi, definisi lengkap di `functions.sql`: '
            .collect($functions)->pluck('proname')->implode(', ');

        Storage::disk('local')->put('supabase-audit/laporan.md', implode("\n", $laporan)."\n");
        Storage::disk('local')->put(
            'supabase-audit/functions.sql',
            collect($functions)->map(fn (object $function) => "-- {$function->proname}\n{$function->definisi};\n")->implode("\n")
        );

        $this->components->info('Laporan lengkap: '.Storage::disk('local')->path('supabase-audit/laporan.md'));
        $this->components->info('Definisi fungsi: '.Storage::disk('local')->path('supabase-audit/functions.sql'));

        return self::SUCCESS;
    }

    /**
     * Satu bagian laporan berbentuk tabel Markdown dari query katalog Postgres.
     *
     * @param  list<string>  $header
     * @return list<string>
     */
    private function bagian(ConnectionInterface $source, string $judul, array $header, string $sql): array
    {
        try {
            $rows = $source->select($sql);
        } catch (Throwable $e) {
            return ['', "## {$judul}", '', '_Gagal dibaca: '.$e->getMessage().'_'];
        }

        $lines = ['', "## {$judul}", '', '| '.implode(' | ', $header).' |', '|'.str_repeat(' --- |', count($header))];

        foreach ($rows as $row) {
            $cells = array_map(
                fn (mixed $value) => str_replace(['|', "\n"], ['\|', ' '], (string) $value),
                array_values((array) $row)
            );
            $lines[] = '| '.implode(' | ', $cells).' |';
        }

        if ($rows === []) {
            $lines[] = '_Tidak ada._';
        }

        return $lines;
    }
}
