<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_login_from_all_dashboard_routes(): void
    {
        foreach (['admin', 'dosen', 'mahasiswa'] as $role) {
            $this->get("/{$role}/dashboard")->assertRedirect(route('login'));
        }
    }

    public function test_login_page_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Selamat datang kembali')
            ->assertSee('Gunakan email akademik dan password yang diberikan administrator.')
            ->assertSee('Kembali ke beranda')
            ->assertSee(route('landing.index'), false);
    }

    public function test_login_page_preserves_form_integration_hooks(): void
    {
        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('action="'.route('login.store').'"', $html);
        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringContainsString('data-auth-form', $html);
        $this->assertStringContainsString('data-submit-button', $html);
        $this->assertStringContainsString('data-submit-label', $html);
        $this->assertStringContainsString('data-submit-spinner', $html);
        $this->assertStringContainsString('data-submit-arrow', $html);
        $this->assertStringContainsString('data-password-toggle="password"', $html);
        $this->assertStringContainsString('data-eye-icon', $html);
        $this->assertStringContainsString('data-eye-off-icon', $html);
        $this->assertStringContainsString('aria-pressed="false"', $html);
        $this->assertStringContainsString('autocomplete="username"', $html);
        $this->assertStringContainsString('autocomplete="current-password"', $html);
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="password"', $html);
    }

    public function test_login_page_has_no_registration_or_password_recovery_links(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertDontSee('Daftar Akun')
            ->assertDontSee('Buat Akun')
            ->assertDontSee('Lupa password')
            ->assertDontSee('Masuk dengan Google');
    }

    public function test_login_validation_errors_are_accessible_and_keep_old_input(): void
    {
        $this->from('/login')->post('/login', [
            'email' => 'bukan-email',
            'password' => '',
        ])->assertRedirect(route('login'));

        $html = $this->get('/login')->assertOk()->getContent();

        $this->assertStringContainsString('id="email-error"', $html);
        $this->assertStringContainsString('id="password-error"', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('aria-live="assertive"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="email-error"', $html);
        $this->assertStringContainsString('value="bukan-email"', $html);
    }

    public function test_admin_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->admin()->create(['password' => 'password']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_dosen_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->dosen()->create(['password' => 'password']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dosen.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_mahasiswa_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->mahasiswa()->create(['password' => 'password']);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('mahasiswa.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->mahasiswa()->create();

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password-yang-salah',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_unregistered_email_is_rejected(): void
    {
        $response = $this->from('/login')->post('/login', [
            'email' => 'tidak-terdaftar@example.test',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->dosen()->create([
            'password' => 'password',
            'is_active' => false,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_only_access_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
        $this->actingAs($admin)->get('/dosen/dashboard')->assertStatus(403);
        $this->actingAs($admin)->get('/mahasiswa/dashboard')->assertStatus(403);
    }

    public function test_dosen_can_only_access_dosen_dashboard(): void
    {
        $dosen = User::factory()->dosen()->create();

        $this->actingAs($dosen)->get('/dosen/dashboard')->assertOk();
        $this->actingAs($dosen)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($dosen)->get('/mahasiswa/dashboard')->assertStatus(403);
    }

    public function test_mahasiswa_can_only_access_mahasiswa_dashboard(): void
    {
        $mahasiswa = User::factory()->mahasiswa()->create();

        $this->actingAs($mahasiswa)->get('/mahasiswa/dashboard')->assertOk();
        $this->actingAs($mahasiswa)->get('/admin/dashboard')->assertStatus(403);
        $this->actingAs($mahasiswa)->get('/dosen/dashboard')->assertStatus(403);
    }

    public function test_authenticated_user_is_redirected_from_login_to_own_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/login')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_logout_ends_the_authenticated_session(): void
    {
        $user = User::factory()->mahasiswa()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_cannot_be_performed_with_get(): void
    {
        $user = User::factory()->admin()->create();

        $this->actingAs($user)
            ->get('/logout')
            ->assertStatus(405);
    }

    public function test_inactive_authenticated_user_is_logged_out_by_active_middleware(): void
    {
        $user = User::factory()->dosen()->create(['is_active' => false]);

        $response = $this->actingAs($user)->get('/dosen/dashboard');

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_too_many_failed_attempts(): void
    {
        $user = User::factory()->mahasiswa()->create();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->from('/login')->post('/login', [
                'email' => $user->email,
                'password' => 'password-salah',
            ]);
        }

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password-salah',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            (string) session('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_public_registration_route_does_not_exist(): void
    {
        $this->assertFalse(Route::has('register'));

        $this->get('/register')->assertNotFound();
    }
}
