<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Aturan bersama akun pengguna. Role dan status sengaja tidak pernah
 * diterima sebagai data masuk yang dipercaya: role ditetapkan oleh subclass
 * untuk halaman mahasiswa/dosen atau oleh admin pada halaman umum, sedangkan
 * status hanya diubah lewat endpoint terpisah.
 */
abstract class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $id = $this->kunciDariRoute();
        $peran = $this->peranValidasi();

        $emailUnik = Rule::unique('users', 'email');
        $nimUnik = Rule::unique('users', 'nim');
        $nidnUnik = Rule::unique('users', 'nidn');

        if ($id !== null) {
            $emailUnik->ignore($id);
            $nimUnik->ignore($id);
            $nidnUnik->ignore($id);
        }

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email:rfc', 'max:255', $emailUnik],
            'nim' => [
                Rule::requiredIf($peran === UserRole::Mahasiswa),
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9._-]+$/',
                $nimUnik,
            ],
            'nidn' => [
                Rule::requiredIf($peran === UserRole::Dosen),
                'nullable',
                'string',
                'max:20',
                'regex:/^[A-Za-z0-9._-]+$/',
                $nidnUnik,
            ],
            'program_studi' => ['nullable', 'string', 'max:100'],
            'angkatan' => [
                Rule::requiredIf($peran === UserRole::Mahasiswa),
                'nullable',
                'integer',
                'digits:4',
                'between:1900,2100',
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
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.string' => 'Password harus berupa teks.',
            'password.min' => 'Password minimal :min karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'nim.required' => 'NIM wajib diisi untuk mahasiswa.',
            'nim.string' => 'NIM harus berupa teks.',
            'nim.max' => 'NIM maksimal :max karakter.',
            'nim.regex' => 'NIM hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.',
            'nim.unique' => 'NIM sudah digunakan akun lain.',
            'nidn.required' => 'NIDN wajib diisi untuk dosen.',
            'nidn.string' => 'NIDN harus berupa teks.',
            'nidn.max' => 'NIDN maksimal :max karakter.',
            'nidn.regex' => 'NIDN hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung.',
            'nidn.unique' => 'NIDN sudah digunakan akun lain.',
            'program_studi.string' => 'Program studi harus berupa teks.',
            'program_studi.max' => 'Program studi maksimal :max karakter.',
            'angkatan.required' => 'Angkatan wajib diisi untuk mahasiswa.',
            'angkatan.integer' => 'Angkatan harus berupa bilangan bulat.',
            'angkatan.digits' => 'Angkatan harus terdiri dari :digits angka.',
            'angkatan.between' => 'Angkatan harus berada antara :min sampai :max.',
            'no_telepon.string' => 'Nomor telepon harus berupa teks.',
            'no_telepon.max' => 'Nomor telepon maksimal :max karakter.',
            'no_telepon.regex' => 'Nomor telepon hanya boleh berisi angka dan karakter telepon umum.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $normalisasi = [];

        $peranTetap = $this->peranTetap();
        if ($peranTetap !== null) {
            $normalisasi['role'] = $peranTetap->value;
        }

        if (is_string($this->input('email'))) {
            $normalisasi['email'] = mb_strtolower(trim($this->input('email')));
        }

        if (is_string($this->input('name'))) {
            $normalisasi['name'] = trim($this->input('name'));
        }

        foreach (['nim', 'nidn'] as $field) {
            if (is_string($this->input($field))) {
                $nilai = trim($this->input($field));
                $normalisasi[$field] = $nilai === '' ? null : Str::upper($nilai);
            }
        }

        foreach (['program_studi', 'angkatan', 'no_telepon'] as $field) {
            if ($this->input($field) === '') {
                $normalisasi[$field] = null;
            }
        }

        if ($normalisasi !== []) {
            $this->merge($normalisasi);
        }
    }

    /**
     * Halaman mahasiswa/dosen menetapkan role dari nama route, sehingga
     * nilai `role` yang dikirim browser tidak pernah dipercaya.
     */
    protected function peranTetap(): ?UserRole
    {
        if ($this->routeIs('admin.mahasiswa.*')) {
            return UserRole::Mahasiswa;
        }

        if ($this->routeIs('admin.dosen.*')) {
            return UserRole::Dosen;
        }

        return null;
    }

    protected function peranValidasi(): ?UserRole
    {
        // Saat mengubah akun yang sudah ada, role target dari database yang
        // menentukan aturan profil. Input `role` dari browser tidak dipakai.
        $target = $this->route('user');
        if ($target instanceof User) {
            return $target->role;
        }

        $peranTetap = $this->peranTetap();
        if ($peranTetap !== null) {
            return $peranTetap;
        }

        return UserRole::tryFrom((string) $this->input('role'));
    }

    protected function kunciDariRoute(): mixed
    {
        try {
            $parameter = $this->route('user');
        } catch (\Throwable) {
            return null;
        }

        if ($parameter instanceof User) {
            return $parameter->getKey();
        }

        return is_numeric($parameter) ? (int) $parameter : null;
    }
}
