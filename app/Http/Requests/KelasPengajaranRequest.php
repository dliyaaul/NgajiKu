<?php

namespace App\Http\Requests;

use App\Enums\JenisLayanan;
use App\Enums\UserRole;
use App\Models\KelasPengajaran;
use App\Models\KelasType;
use App\Models\Lembaga;
use App\Models\Program;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Kelas pengajaran. Aturan jenis layanan mengikuti trigger Supabase `validasi_kelas_pengajaran`:
 * - Lembaga: wajib lembaga (satu lembaga satu kelas) yang programnya sama, dan wajib pengajar.
 * - Reguler/Privat: tanpa lembaga.
 */
class KelasPengajaranRequest extends FormRequest
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
        $kelas = $this->route('kelas');

        return [
            'nama_kelas' => ['required', 'string', 'max:255'],
            'program_id' => ['required', 'uuid', Rule::exists(Program::class, 'id')],
            'pengajar_id' => ['nullable', 'uuid', Rule::exists(User::class, 'id')->where('role', UserRole::Pengajar->value)],
            'lembaga_id' => [
                'nullable',
                'uuid',
                Rule::exists(Lembaga::class, 'id'),
                Rule::unique(KelasPengajaran::class, 'lembaga_id')->ignore($kelas instanceof KelasPengajaran ? $kelas->id : null),
            ],
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

                $jenis = $this->kelasType()->jenis();

                if ($jenis === null) {
                    $validator->errors()->add('nama_kelas', "Jenis kelas {$this->kelasType()->nama} belum memiliki aturan kelas.");

                    return;
                }

                if ($jenis === JenisLayanan::Lembaga) {
                    if (! $this->filled('lembaga_id')) {
                        $validator->errors()->add('lembaga_id', 'Kelas Lembaga wajib memiliki lembaga.');
                    } elseif (Lembaga::find($this->input('lembaga_id'))?->program_id !== $this->input('program_id')) {
                        $validator->errors()->add('lembaga_id', 'Program kelas harus sama dengan program lembaga.');
                    }

                    if (! $this->filled('pengajar_id')) {
                        $validator->errors()->add('pengajar_id', 'Kelas Lembaga wajib memiliki pengajar.');
                    }
                } elseif ($this->filled('lembaga_id')) {
                    $validator->errors()->add('lembaga_id', "Kelas {$jenis->label()} tidak boleh terikat pada lembaga.");
                }

                $kelas = $this->route('kelas');

                if ($kelas instanceof KelasPengajaran
                    && $kelas->program_id !== $this->input('program_id')
                    && $kelas->sudahDipakai()) {
                    $validator->errors()->add('program_id', 'Program tidak dapat diubah karena kelas sudah memiliki santri atau pertemuan.');
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
            'nama_kelas' => 'nama kelas',
            'program_id' => 'program',
            'pengajar_id' => 'pengajar',
            'lembaga_id' => 'lembaga',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['lembaga_id.unique' => 'Lembaga ini sudah memiliki kelas. Satu lembaga hanya boleh memiliki satu kelas.'];
    }

    public function kelasType(): KelasType
    {
        $kelas = $this->route('kelas');

        return $kelas instanceof KelasPengajaran ? $kelas->kelasType : $this->route('kelasType');
    }

    /**
     * @return array{nama_kelas: string, program_id: string, pengajar_id: ?string, lembaga_id: ?string, aktif: bool}
     */
    public function kelasData(): array
    {
        return [
            'nama_kelas' => trim($this->validated('nama_kelas')),
            'program_id' => $this->validated('program_id'),
            'pengajar_id' => $this->validated('pengajar_id') ?: null,
            'lembaga_id' => $this->validated('lembaga_id') ?: null,
            'aktif' => $this->boolean('aktif', true),
        ];
    }
}
