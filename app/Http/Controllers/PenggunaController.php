<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Controllers\Concerns\HapusDataInduk;
use App\Http\Requests\PenggunaRequest;
use App\Models\KelasPengajaran;
use App\Models\KelasType;
use App\Models\Lembaga;
use App\Models\Program;
use App\Models\RiwayatKelasSantri;
use App\Models\SantriProgram;
use App\Models\User;
use App\Services\Absensi\AbsensiProgram;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Akun pengguna per peran (admin, staff, pengajar, santri) pada manajemen LPQ.
 */
class PenggunaController extends Controller
{
    use HapusDataInduk;

    /**
     * Peran yang dikelola dari web (koordinator lembaga hanya memakai aplikasi mobile).
     *
     * @var list<string>
     */
    public const PERAN = ['admin', 'staff', 'pengajar', 'santri'];

    public function index(Request $request, string $peran): Response
    {
        $role = UserRole::from($peran);
        $cari = trim((string) $request->query('cari'));
        $programId = (string) $request->query('program');

        $halaman = User::query()
            ->where('role', $role)
            ->when($role === UserRole::Pengajar, fn (Builder $query) => $query
                ->with('pengajarPrograms.program:id,nama')
                ->withCount(['kelasDiajar' => fn (Builder $query) => $query->where('aktif', true)]))
            ->when($role === UserRole::Santri, fn (Builder $query) => $query->with('santriPrograms.program:id,nama'))
            ->when($cari !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('username', 'like', "%{$cari}%")
                ->orWhere('email', 'like', "%{$cari}%")
                ->orWhere('no_hp', 'like', "%{$cari}%")))
            ->when($programId !== '' && $role === UserRole::Pengajar, fn (Builder $query) => $query
                ->whereHas('pengajarPrograms', fn (Builder $query) => $query->where('program_id', $programId)))
            ->when($programId !== '' && $role === UserRole::Santri, fn (Builder $query) => $query
                ->whereHas('santriPrograms', fn (Builder $query) => $query->where('program_id', $programId)))
            ->orderBy('username')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('lpq/pengguna/index', [
            'peran' => $peran,
            'pengguna' => [
                'data' => $halaman->getCollection()->map(fn (User $user) => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'no_hp' => $user->no_hp,
                    'avatar' => $user->avatar_url,
                    'angkatan' => $user->angkatan,
                    'program' => match ($role) {
                        UserRole::Pengajar => $user->pengajarPrograms->pluck('program.nama')->filter()->sort()->values(),
                        UserRole::Santri => $user->santriPrograms->pluck('program.nama')->filter()->sort()->values(),
                        default => [],
                    },
                    'jumlah_kelas' => $user->kelas_diajar_count,
                    'created_at' => $user->created_at?->toDateString(),
                    'saya' => $user->is($request->user()),
                ]),
                'pagination' => [
                    'halaman' => $halaman->currentPage(),
                    'total_halaman' => $halaman->lastPage(),
                    'total' => $halaman->total(),
                    'sebelumnya' => $halaman->previousPageUrl(),
                    'berikutnya' => $halaman->nextPageUrl(),
                ],
            ],
            'filter' => ['cari' => $cari, 'program' => $programId],
            'opsi' => [
                'program' => in_array($role, [UserRole::Pengajar, UserRole::Santri], true)
                    ? Program::query()->orderBy('nama')->get(['id', 'nama'])
                    : [],
            ],
        ]);
    }

    public function create(string $peran): Response
    {
        return $this->form($peran, null, [
            'username' => '',
            'email' => '',
            'password' => '',
            'password_confirmation' => '',
            'no_hp' => '',
            'alamat' => '',
            'angkatan' => '',
            'program_ids' => [],
        ]);
    }

    public function store(PenggunaRequest $request, string $peran): RedirectResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = new User($request->penggunaData());
            $user->role = $request->peran();
            $user->email_verified_at = now();
            $user->save();

            $this->sinkronkanProgramPengajar($user, $request->programIds());

            return $user;
        });

        $tujuan = in_array($user->role, [UserRole::Santri, UserRole::Pengajar], true)
            ? to_route('admin.lpq.pengguna.show', [$peran, $user])
            : to_route('admin.lpq.pengguna.index', $peran);

        return $tujuan->with('success', "Akun {$user->username} berhasil dibuat.");
    }

    public function show(string $peran, User $user): Response
    {
        $this->pastikanPeran($user, $peran);

        return Inertia::render('lpq/pengguna/show', [
            'peran' => $peran,
            'pengguna' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'no_hp' => $user->no_hp,
                'alamat' => $user->alamat,
                'angkatan' => $user->angkatan,
                'avatar' => $user->avatar_url,
                'created_at' => $user->created_at?->toDateString(),
            ],
            ...match ($user->role) {
                UserRole::Santri => $this->detailSantri($user),
                UserRole::Pengajar => $this->detailPengajar($user),
                default => [],
            },
        ]);
    }

    public function edit(string $peran, User $user): Response
    {
        $this->pastikanPeran($user, $peran);

        return $this->form($peran, $user->id, [
            'username' => $user->username,
            'email' => $user->email ?? '',
            'password' => '',
            'password_confirmation' => '',
            'no_hp' => $user->no_hp ?? '',
            'alamat' => $user->alamat ?? '',
            'angkatan' => $user->angkatan ?? '',
            'program_ids' => $user->pengajarPrograms()->pluck('program_id')->all(),
        ]);
    }

    public function update(PenggunaRequest $request, string $peran, User $user): RedirectResponse
    {
        $this->pastikanPeran($user, $peran);

        DB::transaction(function () use ($request, $user): void {
            $user->update($request->penggunaData());

            $this->sinkronkanProgramPengajar($user, $request->programIds());
        });

        $tujuan = in_array($user->role, [UserRole::Santri, UserRole::Pengajar], true)
            ? to_route('admin.lpq.pengguna.show', [$peran, $user])
            : to_route('admin.lpq.pengguna.index', $peran);

        return $tujuan->with('success', "Data {$user->username} berhasil diperbarui.");
    }

    /**
     * Akun yang sudah punya jejak (absensi, tagihan, kelas, pertemuan) ditolak agar
     * riwayat tidak ikut terhapus secara cascade.
     */
    public function destroy(Request $request, string $peran, User $user): RedirectResponse
    {
        $this->pastikanPeran($user, $peran);

        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->role === UserRole::Admin && User::query()->where('role', UserRole::Admin)->count() <= 1) {
            return back()->with('error', 'Minimal harus ada satu akun admin.');
        }

        $alasan = $this->alasanTidakBisaDihapus($user);

        if ($alasan !== null) {
            return back()->with('error', $alasan);
        }

        $response = $this->hapusDataInduk(
            $user,
            "Akun {$user->username} berhasil dihapus.",
            "Akun {$user->username} masih dipakai data lain sehingga tidak dapat dihapus.",
            function () use ($user): void {
                $user->tokens()->delete();
                RiwayatKelasSantri::query()->whereIn('santri_program_id', $user->santriPrograms()->select('id'))->delete();
            },
        );

        return $user->exists ? $response : to_route('admin.lpq.pengguna.index', $peran)->with('success', "Akun {$user->username} berhasil dihapus.");
    }

    private function alasanTidakBisaDihapus(User $user): ?string
    {
        if ($user->role === UserRole::Santri) {
            $adaTransaksi = $user->santriPrograms->contains(fn (SantriProgram $pendaftaran) => $pendaftaran->punyaRiwayatTransaksi());

            return $adaTransaksi ? "{$user->username} sudah memiliki absensi atau tagihan sehingga akunnya tidak dapat dihapus." : null;
        }

        if ($user->role === UserRole::Pengajar) {
            $pernahMengajar = $user->kelasDiajar()->exists()
                || SantriProgram::query()->where('pengajar_id', $user->id)->exists()
                || collect(AbsensiProgram::cases())->contains(
                    fn (AbsensiProgram $program) => $program->pertemuanModel()::query()->where('pengajar_id', $user->id)->exists(),
                );

            return $pernahMengajar ? "{$user->username} masih memegang kelas atau tercatat di pertemuan. Pindahkan kelasnya ke pengajar lain terlebih dahulu." : null;
        }

        return null;
    }

    /**
     * @param  list<string>  $programIds
     */
    private function sinkronkanProgramPengajar(User $user, array $programIds): void
    {
        if ($user->role !== UserRole::Pengajar) {
            return;
        }

        $user->pengajarPrograms()->whereNotIn('program_id', $programIds)->delete();

        $sudahAda = $user->pengajarPrograms()->pluck('program_id')->all();

        foreach (array_diff($programIds, $sudahAda) as $programId) {
            $user->pengajarPrograms()->create(['program_id' => $programId]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function detailSantri(User $santri): array
    {
        $pendaftaran = $santri->santriPrograms()
            ->with([
                'program:id,nama',
                'kelasType:id,nama',
                'kelasPengajaran:id,nama_kelas,aktif',
                'pengajar:id,username',
                'lembaga:id,nama_lembaga',
                'riwayatKelas' => fn ($query) => $query->with(['kelasAsal:id,nama_kelas', 'kelasTujuan:id,nama_kelas', 'pengubah:id,username'])->latest(),
            ])
            ->get()
            ->sortBy(fn (SantriProgram $item) => $item->program?->nama)
            ->values();

        return [
            'pendaftaran' => $pendaftaran->map(fn (SantriProgram $item) => [
                'id' => $item->id,
                'program_id' => $item->program_id,
                'program' => $item->program?->nama,
                'kelas_type_id' => $item->kelas_type_id,
                'jenis_kelas' => $item->kelasType?->nama,
                'jenis' => $item->kelasType?->jenis()?->value,
                'kelas_pengajaran_id' => $item->kelas_pengajaran_id,
                'kelas' => $item->kelasPengajaran?->nama_kelas,
                'pengajar' => $item->pengajar?->username,
                'lembaga_id' => $item->lembaga_id,
                'lembaga' => $item->lembaga?->nama_lembaga,
                'default_pbm' => $item->default_pbm,
                'terkunci' => $item->punyaRiwayatTransaksi(),
                'riwayat' => $item->riwayatKelas->map(fn (RiwayatKelasSantri $riwayat) => [
                    'id' => $riwayat->id,
                    'dari' => $riwayat->kelasAsal?->nama_kelas,
                    'ke' => $riwayat->kelasTujuan?->nama_kelas,
                    'berlaku_mulai' => $riwayat->berlaku_mulai?->toDateString(),
                    'alasan' => $riwayat->alasan,
                    'oleh' => $riwayat->pengubah?->username,
                ]),
            ]),
            'opsi' => [
                'program' => Program::query()->orderBy('nama')->get(['id', 'nama']),
                'jenis_kelas' => KelasType::query()->orderBy('nama')->get()->map(fn (KelasType $item) => [
                    'id' => $item->id,
                    'nama' => $item->nama,
                    'jenis' => $item->jenis()?->value,
                ]),
                'kelas' => KelasPengajaran::query()
                    ->with('pengajar:id,username')
                    ->orderBy('nama_kelas')
                    ->get()
                    ->map(fn (KelasPengajaran $item) => [
                        'id' => $item->id,
                        'nama' => $item->nama_kelas,
                        'program_id' => $item->program_id,
                        'kelas_type_id' => $item->kelas_type_id,
                        'lembaga_id' => $item->lembaga_id,
                        'pengajar' => $item->pengajar?->username,
                        'aktif' => $item->aktif,
                    ]),
                'lembaga' => Lembaga::query()->orderBy('nama_lembaga')->get(['id', 'nama_lembaga as nama', 'program_id', 'pbm_type']),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function detailPengajar(User $pengajar): array
    {
        $kelas = $pengajar->kelasDiajar()
            ->with(['program:id,nama', 'kelasType:id,nama', 'lembaga:id,nama_lembaga'])
            ->withCount('santriPrograms')
            ->orderByDesc('aktif')
            ->orderBy('nama_kelas')
            ->get();

        return [
            'program' => $pengajar->pengajarPrograms()->with('program:id,nama')->get()->pluck('program.nama')->filter()->sort()->values(),
            'kelas' => $kelas->map(fn (KelasPengajaran $item) => [
                'id' => $item->id,
                'nama_kelas' => $item->nama_kelas,
                'aktif' => $item->aktif,
                'program' => $item->program?->nama,
                'jenis_kelas' => $item->kelasType?->nama,
                'kelas_type_id' => $item->kelas_type_id,
                'lembaga' => $item->lembaga?->nama_lembaga,
                'jumlah_santri' => $item->santri_programs_count,
            ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function form(string $peran, ?string $userId, array $values): Response
    {
        return Inertia::render('lpq/pengguna/form', [
            'peran' => $peran,
            'userId' => $userId,
            'values' => $values,
            'opsi' => [
                'program' => $peran === UserRole::Pengajar->value ? Program::query()->orderBy('nama')->get(['id', 'nama']) : [],
            ],
        ]);
    }

    private function pastikanPeran(User $user, string $peran): void
    {
        abort_unless($user->role->value === $peran, 404);
    }
}
