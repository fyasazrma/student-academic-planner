<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The application entry point sends guests to the login page.
     */
    public function test_the_application_redirects_guests_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }

    /**
     * Authenticated users land on the dashboard for their own role.
     */
    public function test_the_application_redirects_each_role_to_its_dashboard(): void
    {
        foreach ([
            'admin' => 'admin.dashboard',
            'dosen' => 'dosen.dashboard',
            'mahasiswa' => 'mahasiswa.dashboard',
        ] as $role => $route) {
            $user = User::factory()->{$role}()->create();

            $this->actingAs($user)
                ->get('/')
                ->assertRedirect(route($route));
        }
    }
}
