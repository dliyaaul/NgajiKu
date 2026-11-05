<?php

namespace App\Http\Requests;

use App\Services\Absensi\AbsensiProgram;
use App\Services\Absensi\DaftarSurat;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SimpanAbsensiRequest extends FormRequest
{
    /**
     * Akses role dicek middleware, akses kelas dicek di controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'absensi' => ['required', 'array', 'min:1'],
            'absensi.*.santri_program_id' => ['required', 'uuid', 'distinct'],
            'absensi.*.status' => [
                'required',
                Rule::in([...AbsensiProgram::STATUSES, AbsensiProgram::STATUS_BELUM_ABSEN]),
            ],
        ];

        // Santri yang hadir wajib menyetor hafalan (sama dengan check constraint Supabase).
        if ($this->program()->hasHafalan()) {
            $rules += [
                'absensi.*.jenis_tahfidz' => ['nullable', 'required_if:absensi.*.status,Hadir', Rule::in(AbsensiProgram::JENIS_TAHFIDZ)],
                'absensi.*.surat' => ['nullable', 'required_if:absensi.*.status,Hadir', Rule::in(DaftarSurat::names())],
                'absensi.*.ayat_mulai' => ['nullable', 'required_if:absensi.*.status,Hadir', 'integer', 'min:1'],
                'absensi.*.ayat_selesai' => ['nullable', 'required_if:absensi.*.status,Hadir', 'integer', 'gte:absensi.*.ayat_mulai'],
                'absensi.*.keterangan' => ['nullable', 'string', 'max:500'],
            ];
        }

        return $rules;
    }

    /**
     * Pastikan rentang ayat tidak melebihi jumlah ayat surat.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        if (! $this->program()->hasHafalan()) {
            return [];
        }

        return [
            function (Validator $validator): void {
                foreach ((array) $this->input('absensi', []) as $index => $row) {
                    $key = "absensi.{$index}";

                    if ($validator->errors()->hasAny(["{$key}.surat", "{$key}.ayat_mulai", "{$key}.ayat_selesai"])) {
                        continue;
                    }

                    $jumlahAyat = DaftarSurat::jumlahAyat((string) ($row['surat'] ?? ''));

                    if ($jumlahAyat !== null && (int) ($row['ayat_selesai'] ?? 0) > $jumlahAyat) {
                        $validator->errors()->add(
                            "{$key}.ayat_selesai",
                            "Surat {$row['surat']} hanya memiliki {$jumlahAyat} ayat."
                        );
                    }
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
            'absensi.*.status' => 'status',
            'absensi.*.jenis_tahfidz' => 'jenis hafalan',
            'absensi.*.surat' => 'surat',
            'absensi.*.ayat_mulai' => 'ayat mulai',
            'absensi.*.ayat_selesai' => 'ayat selesai',
            'absensi.*.keterangan' => 'keterangan',
        ];
    }

    /**
     * @return list<array{santri_program_id: string, status: string, jenis_tahfidz?: ?string, surat?: ?string, ayat_mulai?: ?int, ayat_selesai?: ?int, keterangan?: ?string}>
     */
    public function absensiRows(): array
    {
        return array_values($this->validated('absensi'));
    }

    private function program(): AbsensiProgram
    {
        $program = $this->route('program');

        return $program instanceof AbsensiProgram ? $program : AbsensiProgram::from((string) $program);
    }
}
