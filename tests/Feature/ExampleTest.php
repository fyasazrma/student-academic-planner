<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    /**
     * The application entry point serves the public landing page to guests.
     */
    public function test_the_application_serves_landing_page_to_guests(): void
    {
        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('Student Academic Planner')
            ->assertSee('Masuk')
            ->assertSee(route('login'), false);

        $this->assertTrue(Route::has('landing.index'));
        $this->assertNull($response->headers->get('Location'));
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
