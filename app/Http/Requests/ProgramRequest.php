<?php

namespace App\Http\Requests;

use App\Models\Program;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Program santri (BBAQ, Tahfidz, ...). Nama program yang dipakai fitur absensi tidak boleh diubah.
 */
class ProgramRequest extends FormRequest
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
            'nama' => ['required', 'string', 'max:100', Rule::unique(Program::class, 'nama')->ignore($this->route('program'))],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $program = $this->route('program');

                if ($program instanceof Program && $program->terkunci() && trim((string) $this->input('nama')) !== $program->nama) {
                    $validator->errors()->add('nama', 'Nama program ini dipakai fitur absensi sehingga tidak dapat diubah.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['nama' => 'nama program'];
    }

    /**
     * @return array{nama: string, deskripsi: ?string}
     */
    public function programData(): array
    {
        return [
            'nama' => trim($this->validated('nama')),
            'deskripsi' => filled($this->validated('deskripsi')) ? trim($this->validated('deskripsi')) : null,
        ];
    }
}
