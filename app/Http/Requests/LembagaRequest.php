<?php

namespace App\Http\Requests;

use App\Models\Lembaga;
use App\Models\Program;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Lembaga mitra. Pengajarnya ditentukan lewat kelas Lembaga, bukan kolom lama `lembaga.pengajar_id`.
 */
class LembagaRequest extends FormRequest
{
    /**
     * Metode PBM (proses belajar mengajar).
     *
     * @var list<string>
     */
    public const PBM = ['Offline', 'Online'];

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
            'nama_lembaga' => ['required', 'string', 'min:3', 'max:255'],
            'pbm_type' => ['required', Rule::in(self::PBM)],
            'program_id' => ['required', 'uuid', Rule::exists(Program::class, 'id')],
            'alamat' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Program lembaga menjadi acuan kelas & pendaftaran santrinya, jadi dikunci setelah dipakai.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $lembaga = $this->route('lembaga');

                if ($lembaga instanceof Lembaga
                    && $lembaga->program_id !== $this->input('program_id')
                    && ($lembaga->kelasPengajaran()->exists() || $lembaga->santriPrograms()->exists())) {
                    $validator->errors()->add('program_id', 'Program tidak dapat diubah karena lembaga sudah memiliki kelas atau santri.');
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
            'nama_lembaga' => 'nama lembaga',
            'pbm_type' => 'metode PBM',
            'program_id' => 'program',
        ];
    }

    /**
     * @return array{nama_lembaga: string, pbm_type: string, program_id: string, alamat: ?string}
     */
    public function lembagaData(): array
    {
        return [
            'nama_lembaga' => trim($this->validated('nama_lembaga')),
            'pbm_type' => $this->validated('pbm_type'),
            'program_id' => $this->validated('program_id'),
            'alamat' => filled($this->validated('alamat')) ? trim($this->validated('alamat')) : null,
        ];
    }
}
