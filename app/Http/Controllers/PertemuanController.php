<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAbsensi;
use App\Http\Requests\PertemuanRequest;
use App\Models\KelasPengajaran;
use App\Models\Pertemuan;
use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\AbsensiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PertemuanController extends Controller
{
    use ResolvesAbsensi;

    public function __construct(private AbsensiService $absensi) {}

    /**
     * Daftar pertemuan pada kelas.
     */
    public function index(AbsensiProgram $program, string $kelas): Response
    {
        $kelasData = $this->resolveKelas($program, $kelas);

        return Inertia::render('absensi/pertemuan/index', [
            'program' => $program,
            'kelas' => $this->kelasProps($kelasData),
            'pertemuanList' => $this->absensi->getPertemuanList($program, $kelasData)
                ->map(fn (Pertemuan $pertemuan): array => [
                    'id' => $pertemuan->id,
                    'tanggal' => $pertemuan->tanggal->toDateString(),
                    'metode' => $pertemuan->metode,
                    'pengajar' => $pertemuan->pengajar?->username,
                    'pengganti' => $pertemuan->pengganti?->nama,
                    'materi' => $pertemuan->materi_pembelajaran,
                    'rekap' => $pertemuan->rekap(),
                    'editable' => $pertemuan->isEditable(),
                    'batas_edit' => $pertemuan->batasEdit()?->toIso8601String(),
                ]),
        ]);
    }

    public function create(Request $request, AbsensiProgram $program, string $kelas): Response
    {
        $kelasData = $this->resolveKelas($program, $kelas);
        $user = $request->user();

        return $this->form($program, $kelasData, null, [
            'tanggal' => now()->toDateString(),
            'pengajar_id' => $user->isPengajar() ? $user->id : $kelasData->pengajar_id,
            'pengganti_id' => null,
            'materi_pembelajaran' => '',
            'metode' => 'Offline',
        ]);
    }

    public function store(PertemuanRequest $request, AbsensiProgram $program, string $kelas): RedirectResponse
    {
        $kelasData = $this->resolveKelas($program, $kelas);

        $pertemuan = $this->absensi->createPertemuan($program, $kelasData, $request->pertemuanData(), $request->user());

        return to_route('absensi.show', [$program, $pertemuan->id])
            ->with('success', 'Pertemuan berhasil dibuat. Silakan isi absensi santri.');
    }

    public function edit(AbsensiProgram $program, string $pertemuan): Response|RedirectResponse
    {
        [$pertemuanData, $kelasData] = $this->resolvePertemuan($program, $pertemuan);

        if (! $pertemuanData->isEditable()) {
            return to_route('absensi.pertemuan.index', [$program, $kelasData->id])
                ->with('error', 'Pertemuan sudah lebih dari 24 jam dan tidak dapat diedit.');
        }

        return $this->form($program, $kelasData, $pertemuanData->id, [
            'tanggal' => $pertemuanData->tanggal->toDateString(),
            'pengajar_id' => $pertemuanData->pengajar_id,
            'pengganti_id' => $pertemuanData->penggantiId(),
            'materi_pembelajaran' => $pertemuanData->materi_pembelajaran ?? '',
            'metode' => $pertemuanData->metode,
        ]);
    }

    public function update(PertemuanRequest $request, AbsensiProgram $program, string $pertemuan): RedirectResponse
    {
        [$pertemuanData, $kelasData] = $this->resolvePertemuan($program, $pertemuan);

        $this->absensi->updatePertemuan($program, $pertemuanData, $request->pertemuanData());

        return to_route('absensi.pertemuan.index', [$program, $kelasData->id])
            ->with('success', 'Pertemuan berhasil diperbarui.');
    }

    public function destroy(AbsensiProgram $program, string $pertemuan): RedirectResponse
    {
        [$pertemuanData, $kelasData] = $this->resolvePertemuan($program, $pertemuan);

        $this->absensi->deletePertemuan($pertemuanData);

        return to_route('absensi.pertemuan.index', [$program, $kelasData->id])
            ->with('success', 'Pertemuan beserta absensinya berhasil dihapus.');
    }

    /**
     * Form tambah/edit pertemuan.
     *
     * @param  array{tanggal: string, pengajar_id: ?string, pengganti_id: ?string, materi_pembelajaran: string, metode: string}  $values
     */
    private function form(AbsensiProgram $program, KelasPengajaran $kelas, ?string $pertemuanId, array $values): Response
    {
        return Inertia::render('absensi/pertemuan/form', [
            'program' => $program,
            'kelas' => $this->kelasProps($kelas),
            'pertemuanId' => $pertemuanId,
            'values' => $values,
            'metodeOptions' => AbsensiProgram::METODE,
            'pengajarOptions' => $this->absensi->getPengajarOptions($program),
            'penggantiOptions' => $this->absensi->getPenggantiOptions(),
        ]);
    }

    /**
     * @return array{id: string, nama_kelas: string}
     */
    private function kelasProps(KelasPengajaran $kelas): array
    {
        return [
            'id' => $kelas->id,
            'nama_kelas' => $kelas->nama_kelas,
        ];
    }
}
