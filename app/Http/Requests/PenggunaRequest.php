<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Program;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Akun admin, staff, pengajar, atau santri. Peran diambil dari URL, bukan dari isian.
 */
class PenggunaRequest extends FormRequest
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
        $user = $this->route('user');
        $isEdit = $user instanceof User;

        return [
            'username' => ['required', 'string', 'min:3', 'max:255', Rule::unique(User::class, 'username')->ignore($user)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')->ignore($user)],
            'password' => [$isEdit ? 'nullable' : 'required', 'string', 'confirmed', Password::min(8)],
            'no_hp' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9\s\-]{8,}$/'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'angkatan' => [Rule::excludeIf($this->peran() !== UserRole::Santri), 'nullable', 'string', 'max:32'],
            'program_ids' => [Rule::excludeIf($this->peran() !== UserRole::Pengajar), 'array'],
            'program_ids.*' => ['uuid', 'distinct', Rule::exists(Program::class, 'id')],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'username' => 'nama lengkap',
            'email' => 'email',
            'password' => 'password',
            'no_hp' => 'nomor HP',
            'alamat' => 'alamat',
            'angkatan' => 'angkatan',
            'program_ids' => 'program',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.unique' => 'Nama ini sudah dipakai akun lain. Tambahkan pembeda, mis. nama ayah atau kota.',
            'email.unique' => 'Email ini sudah terdaftar pada akun lain.',
            'no_hp.regex' => 'Nomor HP hanya boleh berisi angka (minimal 8 digit).',
        ];
    }

    public function peran(): UserRole
    {
        return UserRole::from((string) $this->route('peran'));
    }

    /**
     * Data kolom `users`; password hanya ikut bila diisi.
     *
     * @return array<string, mixed>
     */
    public function penggunaData(): array
    {
        $data = [
            'username' => trim($this->validated('username')),
            'email' => $this->validated('email'),
            'no_hp' => filled($this->validated('no_hp')) ? trim($this->validated('no_hp')) : null,
            'alamat' => filled($this->validated('alamat')) ? trim($this->validated('alamat')) : null,
        ];

        if ($this->peran() === UserRole::Santri) {
            $data['angkatan'] = filled($this->validated('angkatan')) ? trim($this->validated('angkatan')) : null;
        }

        if (filled($this->validated('password'))) {
            $data['password'] = $this->validated('password');
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    public function programIds(): array
    {
        return array_values($this->validated('program_ids', []));
    }
}
