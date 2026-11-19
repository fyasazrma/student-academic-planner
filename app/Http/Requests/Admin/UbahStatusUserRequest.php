<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UbahStatusUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()?->isAdmin() !== true) {
            return false;
        }

        $target = $this->route('user');

        if ($target instanceof User) {
            if ($this->routeIs('admin.mahasiswa.*') && $target->role !== UserRole::Mahasiswa) {
                abort(404);
            }

            if ($this->routeIs('admin.dosen.*') && $target->role !== UserRole::Dosen) {
                abort(404);
            }

            if ($target->isAdmin()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'aktif' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'aktif.required' => 'Status akun wajib ditentukan.',
            'aktif.boolean' => 'Status akun tidak valid.',
        ];
    }
}
