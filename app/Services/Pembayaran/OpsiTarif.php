<?php

namespace App\Services\Pembayaran;

use App\Enums\JenisLayanan;
use App\Models\KelasType;
use App\Models\Lembaga;
use App\Models\Program;
use App\Models\SantriProgram;

/**
 * Pilihan untuk formulir tarif (web & API).
 */
class OpsiTarif
{
    /**
     * @return array{jenis_layanan: list<array{id: string, nama: string, jenis: ?string, satuan: ?string}>, program: list<array{id: string, nama: string}>, lembaga: list<array{id: string, nama: string, program_id: ?string}>, santri_privat: list<array{id: string, nama: string}>}
     */
    public function semua(): array
    {
        return [
            'jenis_layanan' => KelasType::query()->orderBy('nama')->get()
                ->map(fn (KelasType $kelasType) => [
                    'id' => $kelasType->id,
                    'nama' => $kelasType->nama,
                    'jenis' => $kelasType->jenis()?->value,
                    'satuan' => $kelasType->jenis()?->satuanTarif(),
                ])->all(),
            'program' => Program::query()->orderBy('nama')->get(['id', 'nama'])
                ->map(fn (Program $program) => ['id' => $program->id, 'nama' => $program->nama])->all(),
            'lembaga' => Lembaga::query()->orderBy('nama_lembaga')->get(['id', 'nama_lembaga', 'program_id'])
                ->map(fn (Lembaga $lembaga) => ['id' => $lembaga->id, 'nama' => $lembaga->nama_lembaga, 'program_id' => $lembaga->program_id])->all(),
            'santri_privat' => SantriProgram::query()
                ->with(['santri:id,username', 'program:id,nama'])
                ->whereHas('kelasType', fn ($query) => $query->berjenis(JenisLayanan::Privat))
                ->get()
                ->map(fn (SantriProgram $santriProgram) => [
                    'id' => $santriProgram->id,
                    'nama' => trim(($santriProgram->santri?->username ?? 'Tanpa nama').' · '.($santriProgram->program?->nama ?? '-')),
                ])
                ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->all(),
        ];
    }
}
