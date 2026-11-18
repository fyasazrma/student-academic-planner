<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Pembatasan khusus penggabungan kelas. Kunci memakai user yang sudah
        // terautentikasi dan IP, bukan kode gabung maupun data request lain.
        RateLimiter::for('gabung-kelas', function (Request $request): Limit {
            $kunci = self::kunciPembatasGabung($request);

            return Limit::perMinute(5)
                ->by($kunci)
                ->response(function (Request $request, array $headers) use ($kunci) {
                    $detik = RateLimiter::availableIn($kunci);

                    return redirect()
                        ->back()
                        ->withHeaders($headers)
                        ->withInput($request->except('kode_gabung', '_token', '_method'))
                        ->withErrors([
                            'kode_gabung' => "Terlalu banyak percobaan bergabung. Coba lagi dalam {$detik} detik.",
                        ]);
                });
        });
    }

    /**
     * Kunci pembatas: identitas akun + alamat IP.
     */
    private static function kunciPembatasGabung(Request $request): string
    {
        return 'gabung-kelas:'.($request->user()?->id ?? 'tamu').'|'.$request->ip();
    }
}
