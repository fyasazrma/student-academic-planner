<?php

namespace App\Http\Requests\Dosen;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;

class SimpanSesiAbsensiRequest extends AbsensiKelasRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pertemuan_ke' => ['required', 'integer', 'min:1', 'max:65535'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'topik' => ['nullable', 'string', 'max:200'],
            'waktu_mulai' => ['required', 'date_format:H:i'],
            'waktu_selesai' => ['required', 'date_format:H:i', 'different:waktu_mulai'],
        ];
    }

    protected function after(): array
    {
        return [
            function (Validator $validator): void {
                $mulai = $this->input('waktu_mulai');
                $selesai = $this->input('waktu_selesai');

                if (! is_string($mulai) || ! is_string($selesai)
                    || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $mulai) !== 1
                    || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $selesai) !== 1) {
                    return;
                }

                try {
                    $mulaiCarbon = Carbon::createFromFormat('H:i', $mulai, 'Asia/Jakarta');
                    $selesaiCarbon = Carbon::createFromFormat('H:i', $selesai, 'Asia/Jakarta');
                } catch (\Throwable) {
                    return;
                }

                if (! $mulaiCarbon instanceof Carbon
                    || ! $selesaiCarbon instanceof Carbon
                    || ! $selesaiCarbon->greaterThan($mulaiCarbon)) {
                    $validator->errors()->add('waktu_selesai', 'Waktu selesai harus lebih besar daripada waktu mulai.');
                }
            },
        ];
    }
}
