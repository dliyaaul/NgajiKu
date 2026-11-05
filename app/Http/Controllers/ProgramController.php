<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HapusDataInduk;
use App\Http\Requests\ProgramRequest;
use App\Models\Program;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Program santri (data induk LPQ).
 */
class ProgramController extends Controller
{
    use HapusDataInduk;

    public function index(): Response
    {
        $program = Program::query()
            ->withCount(['santriPrograms', 'pengajarPrograms', 'kelasPengajaran', 'lembaga'])
            ->orderBy('nama')
            ->get();

        return Inertia::render('lpq/program/index', [
            'program' => $program->map(fn (Program $item) => [
                'id' => $item->id,
                'nama' => $item->nama,
                'deskripsi' => $item->deskripsi,
                'terkunci' => $item->terkunci(),
                'jumlah_santri' => $item->santri_programs_count,
                'jumlah_pengajar' => $item->pengajar_programs_count,
                'jumlah_kelas' => $item->kelas_pengajaran_count,
                'jumlah_lembaga' => $item->lembaga_count,
            ]),
        ]);
    }

    public function store(ProgramRequest $request): RedirectResponse
    {
        Program::create($request->programData());

        return back()->with('success', 'Program berhasil ditambahkan.');
    }

    public function update(ProgramRequest $request, Program $program): RedirectResponse
    {
        $program->update($request->programData());

        return back()->with('success', 'Program berhasil diperbarui.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        if ($program->terkunci()) {
            return back()->with('error', "Program {$program->nama} dipakai fitur absensi sehingga tidak dapat dihapus.");
        }

        if ($program->lembaga()->exists()) {
            return back()->with('error', "Program {$program->nama} masih dipakai oleh lembaga. Ubah program lembaga tersebut terlebih dahulu.");
        }

        return $this->hapusDataInduk(
            $program,
            'Program berhasil dihapus.',
            "Program {$program->nama} masih memiliki santri, kelas, pertemuan, atau tarif sehingga tidak dapat dihapus.",
            fn () => $program->pengajarPrograms()->delete(),
        );
    }
}
