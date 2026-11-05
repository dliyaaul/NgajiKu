<?php

namespace App\Http\Requests;

use App\Enums\JenisLayanan;
use App\Models\KelasType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Jenis kelas baru. Aturan kelas, pendaftaran, dan tarif bergantung pada nama jenis
 * (lihat JenisLayanan), jadi hanya Reguler, Privat, atau Lembaga yang dapat ditambahkan.
 */
class KelasTypeRequest extends FormRequest
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
            'nama' => ['required', 'string', Rule::in(array_map(fn (JenisLayanan $jenis) => $jenis->label(), JenisLayanan::cases()))],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $jenis = JenisLayanan::dariNama($this->input('nama'));

                if ($jenis !== null && KelasType::query()->berjenis($jenis)->exists()) {
                    $validator->errors()->add('nama', "Jenis kelas {$jenis->label()} sudah ada.");
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['nama.in' => 'Jenis kelas harus Reguler, Privat, atau Lembaga.'];
    }
}
