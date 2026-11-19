<?php

namespace App\Http\Requests\Dosen;

class SimpanTugasRequest extends TugasRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return array_merge($this->aturanUmum(), $this->aturanLampiran());
    }
}
