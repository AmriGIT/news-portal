<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create
        {--name= : Nama admin}
        {--email= : Email admin}
        {--password= : Password admin, gunakan hanya untuk testing atau automation tepercaya}
        {--force : Lewati konfirmasi interaktif}';

    protected $description = 'Membuat user Admin aktif untuk deployment production tanpa menjalankan seeder development.';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nama admin');
        $email = $this->option('email') ?: $this->ask('Email admin');
        $password = $this->option('password') ?: $this->secret('Password admin');

        if (! $this->option('password')) {
            $confirmation = $this->secret('Konfirmasi password admin');

            if ($password !== $confirmation) {
                $this->error('Konfirmasi password tidak cocok.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ], [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                Password::min(12)->mixedCase()->numbers()->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('Buat user Admin ini?', false)) {
            $this->warn('Pembuatan admin dibatalkan.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name,
            'email' => mb_strtolower((string) $email),
            'password' => $password,
            'role' => UserRole::Admin,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info('User Admin berhasil dibuat.');

        return self::SUCCESS;
    }
}
