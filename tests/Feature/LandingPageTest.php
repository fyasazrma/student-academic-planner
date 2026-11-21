<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_landing_route_is_named(): void
    {
        $this->assertTrue(Route::has('landing.index'));
        $this->assertSame(url('/'), route('landing.index'));
    }

    public function test_guest_sees_landing_skeleton(): void
    {
        $this->get(route('landing.index'))
            ->assertOk()
            ->assertSee('Student Academic Planner')
            ->assertSee('Masuk')
            ->assertSee(route('login'), false)
            ->assertSee('id="fitur"', false)
            ->assertSee('id="kalender"', false)
            ->assertSee('id="untuk-siapa"', false)
            ->assertSee('id="faq"', false);
    }

    public function test_landing_contains_all_ten_sections(): void
    {
        $html = $this->get(route('landing.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<header', $html);
        $this->assertStringContainsString('<footer', $html);
        $this->assertSame(8, substr_count($html, '<section'));

        foreach ([
            'beranda',
            'masalah',
            'kalender',
            'fitur',
            'untuk-siapa',
            'cara-kerja',
            'faq',
            'mulai',
        ] as $id) {
            $this->assertStringContainsString('id="'.$id.'"', $html);
        }
    }

    public function test_landing_has_editorial_hero_and_cta(): void
    {
        $this->get(route('landing.index'))
            ->assertOk()
            ->assertSee('Atur Aktivitas Kuliah. Kendalikan Progres Akademik.')
            ->assertSee('Satu tempat untuk mengelola tugas, jadwal akademik, materi pembelajaran, ujian, dan progres kuliah agar aktivitas perkuliahan lebih terorganisir.')
            ->assertSee('Masuk ke Aplikasi')
            ->assertSee('Jelajahi Fitur')
            ->assertSee('Mulai Kelola Aktivitas Akademik dengan Lebih Terarah.')
            ->assertSee('Masuk ke Akun Anda')
            ->assertSee('Akun pengguna disiapkan oleh administrator.');
    }

    public function test_landing_integrates_original_editorial_images(): void
    {
        $images = [
            'hero-student.png' => 'Ilustrasi mahasiswa mengatur tugas dan kalender akademik di laptop',
            'problem-scattered-tasks.png' => 'Ilustrasi tugas dan catatan yang tersebar di berbagai tempat',
            'problem-deadline.png' => 'Ilustrasi mahasiswa menghadapi tugas yang menumpuk dan deadline',
            'problem-academic-schedule.png' => 'Ilustrasi jadwal akademik yang bertumpuk dan sulit dipantau',
            'role-student.png' => 'Ilustrasi mahasiswa dengan daftar aktivitas akademik',
            'role-lecturer.png' => 'Ilustrasi dosen yang mengelola kelas dan tugas',
            'role-admin.png' => 'Ilustrasi administrator yang mengelola data akademik',
            'how-it-works.png' => 'Ilustrasi alur penggunaan aplikasi dari penyiapan akun hingga aktivitas mahasiswa',
            'closing-academic.png' => 'Ilustrasi mahasiswa menyelesaikan rencana akademik',
        ];

        $html = $this->get(route('landing.index'))->assertOk()->getContent();

        foreach ($images as $name => $alt) {
            $this->assertFileExists(public_path('images/landing/'.$name));
            $this->assertStringContainsString(asset('images/landing/'.$name), $html);
            $this->assertStringContainsString('alt="'.$alt.'"', $html);
        }

        $this->assertSame(9, substr_count($html, 'images/landing/'));
        $this->assertSame(9, substr_count($html, 'width="2160"'));
        $this->assertSame(9, substr_count($html, 'height="2160"'));
        $this->assertSame(9, substr_count($html, 'object-contain'));
        $this->assertSame(9, substr_count($html, 'decoding="async"'));
        $this->assertSame(1, substr_count($html, 'loading="eager"'));
        $this->assertSame(8, substr_count($html, 'loading="lazy"'));
        $this->assertStringContainsString('class="mx-auto w-full max-w-[640px] object-contain"', $html);
        $this->assertStringContainsString('Ilustrasi antarmuka — contoh tampilan', $html);
        $this->assertStringNotContainsString('viewBox="0 0 480 340"', $html);
        $this->assertStringNotContainsString('Data pada pratinjau bersifat ilustratif', $html);
        $this->assertStringNotContainsString('Ilustrasi pengingat deadline', $html);
        $this->assertStringNotContainsString('Ilustrasi penyiapan akun oleh admin', $html);
        $this->assertStringNotContainsString('Ilustrasi tugas yang tersebar', $html);
        $this->assertStringNotContainsString('Ilustrasi jadwal yang bertumpuk', $html);
    }

    public function test_landing_keeps_html_mockups_after_image_integration(): void
    {
        $this->get(route('landing.index'))
            ->assertOk()
            ->assertSee('Contoh tampilan')
            ->assertSee('Calendar View')
            ->assertSee('Timeline View')
            ->assertSee('Titik publikasi')
            ->assertSee('Titik deadline');
    }

    public function test_landing_has_calendar_and_timeline_toggle(): void
    {
        $html = $this->get(route('landing.index'))->assertOk()->getContent();

        $this->assertStringContainsString('role="group" aria-label="Pilih tampilan kalender"', $html);
        $this->assertStringContainsString('id="landing-view-calendar"', $html);
        $this->assertStringContainsString('id="landing-view-timeline"', $html);
        $this->assertStringContainsString('for="landing-view-calendar"', $html);
        $this->assertStringContainsString('for="landing-view-timeline"', $html);
        $this->assertStringContainsString('id="landing-panel-calendar"', $html);
        $this->assertStringContainsString('id="landing-panel-timeline"', $html);
        $this->assertStringContainsString('#landing-view-timeline:checked ~ .landing-panels #landing-panel-timeline', $html);
        $this->assertStringContainsString('Calendar View', $html);
        $this->assertStringContainsString('Timeline View', $html);
    }

    public function test_landing_role_tabs_are_present_and_accessible(): void
    {
        $html = $this->get(route('landing.index'))->assertOk()->getContent();

        $this->assertStringContainsString('role="group" aria-label="Pilih peran pengguna"', $html);

        foreach (['mahasiswa', 'dosen', 'admin'] as $role) {
            $this->assertStringContainsString('id="landing-role-'.$role.'"', $html);
            $this->assertStringContainsString('for="landing-role-'.$role.'"', $html);
            $this->assertStringContainsString('id="landing-panel-'.$role.'"', $html);
        }

        $this->assertStringContainsString('#landing-role-admin:checked ~ .landing-role-panels #landing-panel-admin', $html);
        $this->assertStringContainsString('Bergabung ke kelas menggunakan kunci kelas', $html);
        $this->assertStringContainsString('Memperbaiki data absensi sesuai ketentuan', $html);
        $this->assertStringContainsString('Mengelola pengumuman institusi', $html);
    }

    public function test_landing_faq_uses_native_disclosure(): void
    {
        $html = $this->get(route('landing.index'))->assertOk()->getContent();

        $this->assertSame(8, substr_count($html, '<details'));
        $this->assertSame(8, substr_count($html, '<summary'));
        $this->assertStringContainsString('Bagaimana jika saya lupa password?', $html);
        $this->assertStringContainsString('Hubungi administrator untuk mendapatkan bantuan terkait akses akun. Saat ini belum tersedia fitur pemulihan password mandiri melalui email.', $html);
    }

    public function test_landing_mockups_are_labeled_as_examples(): void
    {
        $this->get(route('landing.index'))
            ->assertOk()
            ->assertSee('Contoh tampilan')
            ->assertSee('Titik publikasi')
            ->assertSee('Titik deadline')
            ->assertSee('Dipublikasikan', false)
            ->assertSee('Deadline', false)
            ->assertSee('UTS', false)
            ->assertSee('UAS', false);
    }

    public function test_landing_has_no_public_registration_or_stale_placeholder(): void
    {
        $this->get(route('landing.index'))
            ->assertOk()
            ->assertDontSee('Daftar Akun')
            ->assertDontSee('Buat Akun')
            ->assertDontSee('Lorem ipsum')
            ->assertDontSee('Coming soon')
            ->assertDontSee('Segera hadir');
    }

    public function test_landing_does_not_redirect_guests(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $this->assertFalse($response->isRedirect());
        $this->assertNull($response->headers->get('Location'));
    }

    public function test_landing_exposes_motion_hooks_without_hiding_content_statically(): void
    {
        $html = $this->get(route('landing.index'))->assertOk()->getContent();

        $this->assertStringContainsString('<body data-landing', $html);
        $this->assertStringContainsString('data-progress', $html);
        $this->assertStringContainsString('data-reveal-group', $html);
        $this->assertStringContainsString('data-reveal-item', $html);
        $this->assertStringContainsString('data-parallax="12"', $html);
        $this->assertStringContainsString('data-landing-float', $html);
        $this->assertStringContainsString('data-landing-hover="button"', $html);
        $this->assertSame(2, substr_count($html, 'data-motion="panel-switch"'));

        // Motion hanya diaktifkan dari JavaScript setelah inisialisasi berhasil.
        $this->assertStringNotContainsString('data-motion-ready', $html);
        $this->assertStringNotContainsString('opacity: 0', $html);
    }

    public function test_landing_motion_foundation_is_progressive_and_scoped(): void
    {
        $js = file_get_contents(resource_path('js/app.js'));
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('initLandingMotion', $js);
        $this->assertStringContainsString("document.querySelector('[data-landing]')", $js);
        $this->assertStringContainsString('IntersectionObserver', $js);
        $this->assertStringContainsString('prefers-reduced-motion', $js);
        $this->assertStringContainsString('data-motion-ready', $js);
        $this->assertStringContainsString('{ passive: true }', $js);
        $this->assertStringNotContainsString('setInterval', $js);

        $this->assertStringContainsString('--landing-duration-micro', $css);
        $this->assertStringContainsString('--landing-duration-reveal', $css);
        $this->assertStringContainsString('--landing-ease', $css);
        $this->assertStringContainsString('@media (hover: hover) and (pointer: fine)', $css);
        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('[data-landing] [data-progress]', $css);
    }

    public function test_authenticated_users_are_redirected_from_landing_to_their_dashboard(): void
    {
        foreach ([
            'admin' => 'admin.dashboard',
            'dosen' => 'dosen.dashboard',
            'mahasiswa' => 'mahasiswa.dashboard',
        ] as $role => $route) {
            $user = User::factory()->{$role}()->create();

            $response = $this->actingAs($user)->get('/');

            $response->assertRedirect(route($route));
            $this->assertNotSame(route('landing.index'), $response->headers->get('Location'));
        }
    }
}
