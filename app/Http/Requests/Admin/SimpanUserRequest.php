<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Validation\Rule;

class SimpanUserRequest extends UserRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }
}
