<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Profil pengguna (nama field sama dengan `profiles` Supabase lama).
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'role' => $this->role->value,
            'alamat' => $this->alamat,
            'no_hp' => $this->no_hp,
            'angkatan' => $this->angkatan,
            'avatar_url' => $this->avatar_url,
            'lembaga_id' => $this->lembaga_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
