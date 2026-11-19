<?php

namespace App\Http\Requests\Profil;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerbaruiProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()?->getKey()),
            ],
            'no_telepon' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+()\-\s]+$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.string' => 'Nama harus berupa teks.',
            'name.max' => 'Nama maksimal :max karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal :max karakter.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'no_telepon.string' => 'Nomor telepon harus berupa teks.',
            'no_telepon.max' => 'Nomor telepon maksimal :max karakter.',
            'no_telepon.regex' => 'Nomor telepon hanya boleh berisi angka dan karakter telepon umum.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalisasi = [];

        if (is_string($this->input('name'))) {
            $normalisasi['name'] = trim($this->input('name'));
        }

        if (is_string($this->input('email'))) {
            $normalisasi['email'] = mb_strtolower(trim($this->input('email')));
        }

        if ($this->input('no_telepon') === '') {
            $normalisasi['no_telepon'] = null;
        }

        if ($normalisasi !== []) {
            $this->merge($normalisasi);
        }
    }
}
