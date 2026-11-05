<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Periode tagihan Reguler. Akses admin dicek middleware `role:admin`.
 */
class GenerateTagihanRequest extends FormRequest
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
            'periode_mulai' => ['required', 'date_format:Y-m-d'],
            'periode_selesai' => ['required', 'date_format:Y-m-d', 'after_or_equal:periode_mulai'],
            'tanggal_jatuh_tempo' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:periode_mulai'],
            'konfirmasi' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'periode_mulai' => 'periode mulai',
            'periode_selesai' => 'periode selesai',
            'tanggal_jatuh_tempo' => 'tanggal jatuh tempo',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periode_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'tanggal_jatuh_tempo.after_or_equal' => 'Tanggal jatuh tempo tidak boleh sebelum periode tagihan dimulai.',
        ];
    }

    public function mulai(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('periode_mulai'));
    }

    public function selesai(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->validated('periode_selesai'));
    }

    public function jatuhTempo(): ?CarbonImmutable
    {
        $tanggal = $this->validated('tanggal_jatuh_tempo');

        return $tanggal ? CarbonImmutable::parse($tanggal) : null;
    }
}
