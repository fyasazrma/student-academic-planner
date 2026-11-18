<?php

namespace App\Http\Requests\Admin;

class SimpanTahunAkademikRequest extends TahunAkademikRequest
{
    /**
     * Data baru belum memiliki primary key yang perlu diabaikan.
     */
    protected function periodeDiabaikan(): mixed
    {
        return null;
    }
}
