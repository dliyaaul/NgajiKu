<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAbsensi;
use App\Http\Requests\SimpanAbsensiRequest;
use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\AbsensiService;
use App\Services\Absensi\DaftarSurat;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AbsensiPertemuanController extends Controller
{
    use ResolvesAbsensi;

    public function __construct(private AbsensiService $absensi) {}

    /**
     * Form absensi santri pada satu pertemuan.
     */
    public function show(AbsensiProgram $program, string $pertemuan): Response
    {
        [$pertemuanData, $kelasData] = $this->resolvePertemuan($program, $pertemuan);

        $santriList = $this->absensi->getFormulirAbsensi($program, $pertemuanData);
        $tersimpan = array_fill_keys(AbsensiProgram::STATUSES, 0);

        foreach ($santriList as $santri) {
            if ($santri['tersimpan'] && isset($tersimpan[$santri['status']])) {
                $tersimpan[$santri['status']]++;
            }
        }

        return Inertia::render('absensi/show', [
            'program' => $program,
            'kelas' => [
                'id' => $kelasData->id,
                'nama_kelas' => $kelasData->nama_kelas,
            ],
            'pertemuan' => [
                'id' => $pertemuanData->id,
                'tanggal' => $pertemuanData->tanggal->toDateString(),
                'metode' => $pertemuanData->metode,
                'materi' => $pertemuanData->materi_pembelajaran,
                'pengajar' => $pertemuanData->pengajar?->username,
                'pengganti' => $pertemuanData->pengganti?->nama,
            ],
            'santriList' => $santriList,
            'ringkasanTersimpan' => $tersimpan,
            'editable' => $pertemuanData->isEditable(),
            'batasEdit' => $pertemuanData->batasEdit()?->toIso8601String(),
            'jenisTahfidz' => AbsensiProgram::JENIS_TAHFIDZ,
            'daftarSurat' => $program->hasHafalan() ? DaftarSurat::daftar() : [],
        ]);
    }

    public function update(SimpanAbsensiRequest $request, AbsensiProgram $program, string $pertemuan): RedirectResponse
    {
        [$pertemuanData] = $this->resolvePertemuan($program, $pertemuan);

        $this->absensi->saveAbsensi($program, $pertemuanData, $request->absensiRows(), $request->user());

        return to_route('absensi.show', [$program, $pertemuanData->id])
            ->with('success', 'Absensi berhasil disimpan.');
    }
}
