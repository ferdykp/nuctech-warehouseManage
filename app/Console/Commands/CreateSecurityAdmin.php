<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateSecurityAdmin extends Command
{
    protected $signature = 'security:create-admin';

    protected $description = 'Create an admin with a private, interactive password; never overwrite existing users';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Use an interactive terminal.');

            return self::FAILURE;
        }
        $data = [
            'name' => $this->ask('Nama admin'),
            'username' => $this->ask('Username'),
            'email' => $this->ask('Email'),
            'password' => $this->secret('Password (minimal 12 karakter, huruf besar/kecil, angka, simbol)'),
            'password_confirmation' => $this->secret('Ulangi password'),
        ];
        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }
        User::create([
            'name' => $data['name'], 'username' => $data['username'], 'email' => $data['email'],
            'password' => Hash::make($data['password']), 'role' => 'superadmin', 'site_id' => null,
        ]);
        $this->info('Admin dibuat.');

        return self::SUCCESS;
    }
}
