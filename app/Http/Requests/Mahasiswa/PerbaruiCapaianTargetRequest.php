<?php

namespace App\Http\Requests\Mahasiswa;

use App\Models\TargetBelajar;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Capaian target hanya dapat diubah oleh pemiliknya dan tidak boleh melebihi
 * nilai target yang ditetapkan server.
 */
class PerbaruiCapaianTargetRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->user()?->isMahasiswa() !== true) {
            return false;
        }

        $targetId = $this->route('target');

        return TargetBelajar::query()
            ->where('mahasiswa_id', $this->user()->getAuthIdentifier())
            ->whereKey(is_numeric($targetId) ? (int) $targetId : 0)
            ->exists();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'capaian' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'capaian.required' => 'Capaian wajib diisi.',
            'capaian.numeric' => 'Capaian harus berupa angka.',
            'capaian.min' => 'Capaian tidak boleh negatif.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function (Validator $validator): void {
            $targetId = $this->route('target');

            $target = TargetBelajar::query()
                ->where('mahasiswa_id', $this->user()->getAuthIdentifier())
                ->whereKey(is_numeric($targetId) ? (int) $targetId : 0)
                ->first();

            $capaian = $this->input('capaian');

            if ($target !== null && $target->target_nilai !== null && is_numeric($capaian) && (float) $capaian > $target->target_nilai) {
                $validator->errors()->add('capaian', 'Capaian tidak boleh melebihi nilai target.');
            }
        });
    }
}
