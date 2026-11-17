<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:create
                            {--name= : Nama admin (fallback ke ADMIN_NAME)}
                            {--email= : Email admin (fallback ke ADMIN_EMAIL)}
                            {--force : Tetap buat admin baru walaupun akun admin sudah ada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat akun admin pertama dari environment variable atau input interaktif yang aman';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->bolehMembuatAdmin()) {
            return self::FAILURE;
        }

        $name = $this->option('name') ?: config('services.admin.name') ?: null;
        $email = $this->option('email') ?: config('services.admin.email') ?: null;
        $password = config('services.admin.password') ?: null;

        if ($name === null) {
            if (! $this->input->isInteractive()) {
                $this->error('Nama admin belum tersedia. Isi ADMIN_NAME atau jalankan command secara interaktif.');

                return self::FAILURE;
            }

            $name = $this->ask('Nama admin');
        }

        if ($email === null) {
            if (! $this->input->isInteractive()) {
                $this->error('Email admin belum tersedia. Isi ADMIN_EMAIL atau jalankan command secara interaktif.');

                return self::FAILURE;
            }

            $email = $this->ask('Email admin');
        }

        if ($password === null) {
            if (! $this->input->isInteractive()) {
                $this->error('Password admin belum tersedia. Isi ADMIN_PASSWORD atau jalankan command secara interaktif.');

                return self::FAILURE;
            }

            $password = $this->secret('Password admin (minimal 12 karakter)');
            $konfirmasi = $this->secret('Ulangi password admin');

            if ($password !== $konfirmasi) {
                $this->error('Konfirmasi password tidak sama.');

                return self::FAILURE;
            }
        }

        $data = [
            'name' => is_string($name) ? trim($name) : $name,
            'email' => is_string($email) ? strtolower(trim($email)) : $email,
            'password' => $password,
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $pesan) {
                $this->error($pesan);
            }

            return self::FAILURE;
        }

        $admin = new User();
        $admin->forceFill([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        $this->info("Akun admin {$admin->email} berhasil dibuat.");

        if (config('services.admin.password')) {
            $this->warn('Hapus ADMIN_PASSWORD dari environment setelah akun admin dibuat.');
        }

        return self::SUCCESS;
    }

    /**
     * Cegah pembuatan admin tambahan tanpa konfirmasi eksplisit.
     */
    protected function bolehMembuatAdmin(): bool
    {
        $sudahAdaAdmin = User::query()
            ->where('role', UserRole::Admin->value)
            ->exists();

        if (! $sudahAdaAdmin || $this->option('force')) {
            return true;
        }

        if (! $this->input->isInteractive()) {
            $this->error('Akun admin sudah tersedia. Gunakan --force hanya jika memang ingin menambah admin lain.');

            return false;
        }

        if (! $this->confirm('Akun admin sudah tersedia. Tetap buat admin baru?', false)) {
            $this->info('Dibatalkan. Tidak ada akun yang dibuat.');

            return false;
        }

        return true;
    }
}
