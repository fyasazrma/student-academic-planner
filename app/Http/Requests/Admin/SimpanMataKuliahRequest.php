<?php

namespace App\Http\Requests\Admin;

class SimpanMataKuliahRequest extends MataKuliahRequest
{
    /**
     * Data baru belum memiliki primary key yang perlu diabaikan.
     */
    protected function kodeDiabaikan(): mixed
    {
        return null;
    }
}
