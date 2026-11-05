<?php

namespace App\Http\Requests;

use App\Enums\JenisLayanan;
use App\Models\KelasType;
use App\Models\Lembaga;
use App\Models\PembayaranTarif;
use App\Models\Program;
use App\Models\SantriProgram;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Tarif pembayaran. Target tarif bergantung pada jenis layanan (sama dengan check
 * constraint `pembayaran_tarif_target_check` Supabase):
 * - Reguler: umum (tanpa program/santri/lembaga), per bulan.
 * - Privat: untuk satu pendaftaran santri, per pertemuan.
 * - Lembaga: untuk satu lembaga + programnya, per pertemuan.
 */
class TarifRequest extends FormRequest
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
        return [
            'nama_tarif' => ['required', 'string', 'max:255'],
            'kelas_type_id' => ['required', 'uuid', Rule::exists(KelasType::class, 'id')],
            'program_id' => ['nullable', 'uuid', Rule::exists(Program::class, 'id')],
            'santri_program_id' => ['nullable', 'uuid', Rule::exists(SantriProgram::class, 'id')],
            'lembaga_id' => ['nullable', 'uuid', Rule::exists(Lembaga::class, 'id')],
            'nominal' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'satuan' => ['required', Rule::in(['bulan', 'pertemuan'])],
            'berlaku_mulai' => ['required', 'date_format:Y-m-d'],
            'berlaku_sampai' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:berlaku_mulai'],
            'aktif' => ['sometimes', 'boolean'],
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
                    $validator->errors()->add('kelas_type_id', 'Jenis layanan tarif tidak dikenali.');

                    return;
                }

                if ($this->input('satuan') !== $jenis->satuanTarif()) {
                    $validator->errors()->add('satuan', "Tarif {$jenis->label()} harus menggunakan satuan {$jenis->satuanTarif()}.");
                }

                [$wajib, $dilarang] = match ($jenis) {
                    JenisLayanan::Reguler => [[], ['program_id', 'santri_program_id', 'lembaga_id']],
                    JenisLayanan::Privat => [['santri_program_id'], ['program_id', 'lembaga_id']],
                    JenisLayanan::Lembaga => [['lembaga_id', 'program_id'], ['santri_program_id']],
                };
                $nama = $this->attributes();

                foreach ($wajib as $field) {
                    if (! $this->filled($field)) {
                        $validator->errors()->add($field, ucfirst($nama[$field])." wajib dipilih untuk tarif {$jenis->label()}.");
                    }
                }

                foreach ($dilarang as $field) {
                    if ($this->filled($field)) {
                        $validator->errors()->add($field, "Tarif {$jenis->label()} tidak boleh terikat pada {$nama[$field]} tertentu.");
                    }
                }

                if ($jenis === JenisLayanan::Lembaga
                    && $this->filled(['lembaga_id', 'program_id'])
                    && Lembaga::find($this->input('lembaga_id'))?->program_id !== $this->input('program_id')) {
                    $validator->errors()->add('program_id', 'Program harus sama dengan program lembaga.');
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
            'nama_tarif' => 'nama tarif',
            'kelas_type_id' => 'jenis layanan',
            'program_id' => 'program',
            'santri_program_id' => 'santri',
            'lembaga_id' => 'lembaga',
            'berlaku_mulai' => 'berlaku mulai',
            'berlaku_sampai' => 'berlaku sampai',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function tarifData(): array
    {
        return [
            ...$this->safe()->only((new PembayaranTarif)->getFillable()),
            'nama_tarif' => trim($this->validated('nama_tarif')),
            'aktif' => $this->boolean('aktif', true),
        ];
    }
}
