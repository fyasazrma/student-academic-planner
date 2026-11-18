<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Factory adalah alur internal yang tepercaya, sehingga atribut yang
     * sengaja tidak fillable (misalnya `role` dan `is_active`) tetap dapat
     * ditetapkan saat membuat model untuk pengujian.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function newModel(array $attributes = [])
    {
        $model = $this->modelName();

        return $model::unguarded(fn () => new $model($attributes));
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Mahasiswa,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function mahasiswa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Mahasiswa,
        ]);
    }

    public function dosen(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Dosen,
            'nim' => null,
            'nidn' => fake()->unique()->numerify('##########'),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
            'nim' => null,
            'nidn' => null,
        ]);
    }
}
