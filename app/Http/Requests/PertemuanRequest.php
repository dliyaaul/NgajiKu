<?php

namespace App\Http\Requests;

use App\Services\Absensi\AbsensiProgram;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PertemuanRequest extends FormRequest
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
        return [
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'pengajar_id' => ['nullable', 'uuid'],
            'pengganti_id' => ['nullable', 'uuid'],
            'materi_pembelajaran' => ['nullable', 'string', 'max:1000'],
            'metode' => ['required', Rule::in(AbsensiProgram::METODE)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pengajar_id' => 'pengajar',
            'pengganti_id' => 'pengajar pengganti',
            'materi_pembelajaran' => 'materi pembelajaran',
        ];
    }

    /**
     * @return array{tanggal: string, pengajar_id: ?string, pengganti_id: ?string, materi_pembelajaran: ?string, metode: string}
     */
    public function pertemuanData(): array
    {
        return [
            'tanggal' => $this->validated('tanggal'),
            'pengajar_id' => $this->validated('pengajar_id'),
            'pengganti_id' => $this->validated('pengganti_id'),
            'materi_pembelajaran' => $this->validated('materi_pembelajaran'),
            'metode' => $this->validated('metode'),
        ];
    }
}
