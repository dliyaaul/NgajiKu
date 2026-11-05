<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Login dengan email & password, dipakai web (session) dan API mobile (token).
 */
class LoginRequest extends FormRequest
{
    private const MAKS_PERCOBAAN = 5;

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
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Memeriksa kredensial dengan pembatasan percobaan per email + IP.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $user = User::query()->where('email', $this->string('email')->lower()->toString())->first();

        if (! $user || ! Hash::check($this->string('password')->toString(), (string) $user->password)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Login gagal. Periksa email dan password Anda.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::MAKS_PERCOBAAN)) {
            return;
        }

        event(new Lockout($this));

        $detik = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$detik} detik.",
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate($this->string('email')->lower().'|'.$this->ip());
    }
}
