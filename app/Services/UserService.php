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
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'phone_number' => ['required', 'string', 'max:255'],
            'two_factor_secret' => ['nullable', 'string', 'max:255'],
        ])->validate();

        return User::create($validated);
    }

    // Lo mismo pero con parámetros directos.
    public function createTyped(
        string $name,
        string $username,
        string $password,
        string $phoneNumber,
        float $balance = 0,
        ?string $twoFactorSecret = null
    ): User {
        return $this->create([
            'balance' => $balance,
            'name' => $name,
            'username' => $username,
            'password' => $password,
            'phone_number' => $phoneNumber,
            'two_factor_secret' => $twoFactorSecret,
        ]);
    }

    // Borra usuario por id.
    public function deleteById(int $id): bool
    {
        return (bool) User::query()->whereKey($id)->delete();
    }
}
