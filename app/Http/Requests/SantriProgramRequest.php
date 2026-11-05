<?php

namespace App\Http\Requests;

use App\Enums\JenisLayanan;
use App\Models\KelasPengajaran;
use App\Models\KelasType;
use App\Models\Lembaga;
use App\Models\Program;
use App\Models\SantriProgram;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Pendaftaran santri pada program. Aturan mengikuti trigger Supabase
 * `validasi_kelas_pendaftaran_santri` dan foreign key komposit kelas:
 * - Lembaga: wajib lembaga; program = program lembaga; kelas (bila ada) = kelas lembaga itu.
 * - Reguler/Privat: tanpa lembaga.
 * - Kelas harus satu program & satu jenis dengan pendaftaran.
 * Pengajar pendaftaran selalu mengikuti pengajar kelasnya.
 */
class SantriProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $pendaftaran = $this->pendaftaran();

        return [
            'program_id' => [
                'required',
                'uuid',
                Rule::exists(Program::class, 'id'),
                Rule::unique(SantriProgram::class, 'program_id')
                    ->where('santri_id', $this->santriId())
                    ->ignore($pendaftaran?->id),
            ],
            'kelas_type_id' => ['required', 'uuid', Rule::exists(KelasType::class, 'id')],
            'kelas_pengajaran_id' => ['nullable', 'uuid', Rule::exists(KelasPengajaran::class, 'id')],
            'lembaga_id' => ['nullable', 'uuid', Rule::exists(Lembaga::class, 'id')],
            'default_pbm' => ['required', Rule::in(LembagaRequest::PBM)],
            'alasan' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $jenis = KelasType::find($this->input('kelas_type_id'))?->jenis();

                if ($jenis === null) {
                    $validator->errors()->add('kelas_type_id', 'Jenis kelas ini belum memiliki aturan pendaftaran.');

                    return;
                }

                if ($jenis === JenisLayanan::Lembaga) {
                    $lembaga = Lembaga::find($this->input('lembaga_id'));

                    if ($lembaga === null) {
                        $validator->errors()->add('lembaga_id', 'Pendaftaran layanan Lembaga wajib memilih lembaga.');

                        return;
                    }

                    if ($lembaga->program_id !== $this->input('program_id')) {
                        $validator->errors()->add('program_id', 'Program pendaftaran harus sesuai dengan program lembaga.');
                    }
                } elseif ($this->filled('lembaga_id')) {
                    $validator->errors()->add('lembaga_id', "Pendaftaran {$jenis->label()} tidak boleh terikat pada lembaga.");
                }

                $kelas = $this->kelas();
                $pendaftaran = $this->pendaftaran();

                if ($kelas !== null) {
                    if ($kelas->program_id !== $this->input('program_id') || $kelas->kelas_type_id !== $this->input('kelas_type_id')) {
                        $validator->errors()->add('kelas_pengajaran_id', 'Kelas harus berada pada program dan jenis kelas yang sama.');
                    } elseif ($jenis === JenisLayanan::Lembaga && $kelas->lembaga_id !== $this->input('lembaga_id')) {
                        $validator->errors()->add('kelas_pengajaran_id', 'Kelas harus milik lembaga yang dipilih.');
                    } elseif (! $kelas->aktif && $pendaftaran?->kelas_pengajaran_id !== $kelas->id) {
                        $validator->errors()->add('kelas_pengajaran_id', 'Kelas ini sudah nonaktif.');
                    }
                }

                if ($pendaftaran !== null
                    && $pendaftaran->program_id !== $this->input('program_id')
                    && $pendaftaran->punyaRiwayatTransaksi()) {
                    $validator->errors()->add('program_id', 'Program tidak dapat diubah karena pendaftaran ini sudah memiliki absensi atau tagihan. Daftarkan ke program baru saja.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'program_id' => 'program',
            'kelas_type_id' => 'jenis kelas',
            'kelas_pengajaran_id' => 'kelas',
            'lembaga_id' => 'lembaga',
            'default_pbm' => 'metode PBM',
            'alasan' => 'alasan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['program_id.unique' => 'Santri sudah terdaftar pada program ini.'];
    }

    /**
     * @return array{program_id: string, kelas_type_id: string, kelas_pengajaran_id: ?string, pengajar_id: ?string, lembaga_id: ?string, default_pbm: string}
     */
    public function pendaftaranData(): array
    {
        $kelas = $this->kelas();

        return [
            'program_id' => $this->validated('program_id'),
            'kelas_type_id' => $this->validated('kelas_type_id'),
            'kelas_pengajaran_id' => $kelas?->id,
            'pengajar_id' => $kelas?->pengajar_id,
            'lembaga_id' => $this->validated('lembaga_id') ?: null,
            'default_pbm' => $this->validated('default_pbm'),
        ];
    }

    public function alasan(): ?string
    {
        $alasan = trim((string) $this->validated('alasan'));

        return $alasan !== '' ? $alasan : null;
    }

    private function pendaftaran(): ?SantriProgram
    {
        $pendaftaran = $this->route('santriProgram');

        return $pendaftaran instanceof SantriProgram ? $pendaftaran : null;
    }

    private function santriId(): ?string
    {
        $santri = $this->route('user');

        return $santri instanceof User ? $santri->id : $this->pendaftaran()?->santri_id;
    }

    private function kelas(): ?KelasPengajaran
    {
        return $this->filled('kelas_pengajaran_id') ? KelasPengajaran::find($this->input('kelas_pengajaran_id')) : null;
    }
}
