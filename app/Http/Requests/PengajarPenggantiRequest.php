<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengajar pengganti dari luar (bukan akun pengguna).
 */
class PengajarPenggantiRequest extends FormRequest
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
            'nama' => ['required', 'string', 'min:3', 'max:255'],
            'no_hp' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9\s\-]{8,}$/'],
            'aktif' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['nama' => 'nama', 'no_hp' => 'nomor HP'];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['no_hp.regex' => 'Nomor HP hanya boleh berisi angka (minimal 8 digit).'];
    }

    /**
     * @return array{nama: string, no_hp: ?string, aktif: bool}
     */
    public function penggantiData(): array
    {
        return [
            'nama' => trim($this->validated('nama')),
            'no_hp' => filled($this->validated('no_hp')) ? trim($this->validated('no_hp')) : null,
            'aktif' => $this->boolean('aktif', true),
        ];
    }
}
