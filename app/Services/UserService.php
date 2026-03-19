<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Validator;

class UserService
{
    // Crea usuario y valida datos antes.
    public function create(array $data): User
    {
        $validated = Validator::make($data, [
            'balance' => ['sometimes', 'numeric', 'min:0'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255'],
            'is_verified' => ['sometimes', 'boolean'],
            'sms_code' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return User::create($validated);
    }

    // Lo mismo pero con parámetros directos.
    public function createTyped(
        string $username,
        string $password,
        string $phoneNumber,
        float $balance = 0,
        bool $isVerified = false,
        ?string $smsCode = null
    ): User {
        return $this->create([
            'balance' => $balance,
            'username' => $username,
            'password' => $password,
            'phone_number' => $phoneNumber,
            'is_verified' => $isVerified,
            'sms_code' => $smsCode,
        ]);
    }

    // Borra usuario por id.
    public function deleteById(int $id): bool
    {
        return (bool) User::query()->whereKey($id)->delete();
    }
}
