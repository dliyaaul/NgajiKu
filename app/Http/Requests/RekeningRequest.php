<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Rekening tujuan transfer. Akses admin dicek middleware `role:admin`.
 */
class RekeningRequest extends FormRequest
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
            'nama_bank' => ['required', 'string', 'max:100'],
            'nomor_rekening' => ['required', 'string', 'max:50'],
            'nama_pemilik' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['sometimes', 'boolean'],
            'urutan' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nama_bank' => 'nama bank',
            'nomor_rekening' => 'nomor rekening',
            'nama_pemilik' => 'nama pemilik',
        ];
    }

    /**
     * @return array{nama_bank: string, nomor_rekening: string, nama_pemilik: string, keterangan: ?string, aktif: bool, urutan: int}
     */
    public function rekeningData(): array
    {
        return [
            'nama_bank' => trim($this->validated('nama_bank')),
            'nomor_rekening' => trim($this->validated('nomor_rekening')),
            'nama_pemilik' => trim($this->validated('nama_pemilik')),
            'keterangan' => $this->validated('keterangan'),
            'aktif' => $this->boolean('aktif', true),
            'urutan' => (int) ($this->validated('urutan') ?? 0),
        ];
    }
}
