<?php

namespace App\Http\Requests;

use App\Enums\StatusPembayaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Keputusan admin atas pembayaran. Akses admin dicek middleware `role:admin`.
 */
class VerifikasiPembayaranRequest extends FormRequest
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
            'status' => ['required', Rule::in([StatusPembayaran::Diterima->value, StatusPembayaran::Ditolak->value])],
            'catatan' => ['nullable', 'required_if:status,ditolak', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'catatan.required_if' => 'Alasan penolakan wajib diisi.',
        ];
    }

    public function statusBaru(): StatusPembayaran
    {
        return StatusPembayaran::from($this->validated('status'));
    }
}
