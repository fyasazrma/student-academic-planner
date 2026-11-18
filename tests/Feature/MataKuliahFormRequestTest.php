<?php

namespace Tests\Feature;

use App\Http\Requests\Mahasiswa\CariKelasRequest;
use App\Http\Requests\Mahasiswa\GabungKelasRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MataKuliahFormRequestTest extends TestCase
{
    public function test_cari_kelas_request_menerima_kata_kunci_opsional(): void
    {
        $request = new CariKelasRequest();

        $kosong = Validator::make([], $request->rules(), $request->messages());
        $this->assertTrue($kosong->passes());
        $this->assertSame([], $kosong->validated());

        $terisi = Validator::make(['q' => 'Pemrograman'], $request->rules(), $request->messages());
        $this->assertTrue($terisi->passes());
        $this->assertSame(['q' => 'Pemrograman'], $terisi->validated());
    }

    public function test_cari_kelas_request_menolak_kata_kunci_lebih_dari_seratus_karakter(): void
    {
        $request = new CariKelasRequest();

        $validator = Validator::make(
            ['q' => str_repeat('a', 101)],
            $request->rules(),
            $request->messages(),
        );

        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('maksimal 100 karakter', $validator->errors()->first('q'));
    }

    public function test_gabung_kelas_request_mewajibkan_kunci_dan_membatasi_panjangnya(): void
    {
        $request = new GabungKelasRequest();

        $kosong = Validator::make([], $request->rules(), $request->messages());
        $this->assertTrue($kosong->fails());
        $this->assertStringContainsString('wajib diisi', $kosong->errors()->first('kode_gabung'));

        $terlaluPanjang = Validator::make(
            ['kode_gabung' => str_repeat('A', 33)],
            $request->rules(),
            $request->messages(),
        );
        $this->assertTrue($terlaluPanjang->fails());
        $this->assertStringContainsString('maksimal 32 karakter', $terlaluPanjang->errors()->first('kode_gabung'));

        $valid = Validator::make(
            ['kode_gabung' => str_repeat('A', 32)],
            $request->rules(),
            $request->messages(),
        );
        $this->assertTrue($valid->passes());
    }

    public function test_gabung_kelas_request_tidak_menerima_identitas_atau_status_peserta(): void
    {
        $request = new GabungKelasRequest();
        $rules = $request->rules();

        foreach (['mahasiswa_id', 'kelas_mata_kuliah_id', 'status'] as $field) {
            $this->assertArrayNotHasKey($field, $rules);
        }

        $validator = Validator::make([
            'kode_gabung' => str_repeat('B', 32),
            'mahasiswa_id' => 999,
            'kelas_mata_kuliah_id' => 888,
            'status' => 'aktif',
        ], $rules, $request->messages());

        $this->assertTrue($validator->passes());
        $this->assertSame(['kode_gabung' => str_repeat('B', 32)], $validator->validated());
    }
}
