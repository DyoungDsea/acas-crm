<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

// Bootstraps the very first super admin — before one exists, nobody
// can log in to create another via the UserController, so this has
// to run at the console instead. Run once when setting up the
// project, and again later only if you need to add another super
// admin outside the normal (currently nonexistent) UI flow for it.
class CreateSuperAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Create a super admin account (bootstraps the very first one, or adds another).';

    public function handle(): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            ['name' => 'required|string|max:255', 'email' => 'required|email|unique:users,email']
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = $this->secret('Password (leave blank to auto-generate)');
        $generated = blank($password);
        $password = $generated ? Str::password(16) : $password;

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_super_admin' => true,
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Super admin created: {$user->email}");

        if ($generated) {
            $this->warn("Generated password: {$password}");
            $this->line('Store this securely — it will not be shown again.');
        }

        return self::SUCCESS;
    }
}
