<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// The only recovery path for a locked-out super admin, by design —
// email-based self-service reset is deliberately blocked for this
// role (see PasswordResetLinkController). Requires server/artisan
// access, which is the point: recovering the highest-privilege
// account should require more than knowing an email address.
class ResetSuperAdminPassword extends Command
{
    protected $signature = 'admin:reset-password {email}';

    protected $description = "Manually reset a super admin's password. Refuses to run on non-super-admin accounts.";

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user found with that email.');

            return self::FAILURE;
        }

        if (! $user->is_super_admin) {
            $this->error('This command is for super admin accounts only. Other users should use the forgot-password flow at /forgot-password.');

            return self::FAILURE;
        }

        $password = $this->secret('New password (leave blank to auto-generate)');
        $generated = blank($password);
        $password = $generated ? Str::password(16) : $password;

        $user->forceFill(['password' => Hash::make($password)])->save();

        $this->info("Password updated for {$user->email}.");

        if ($generated) {
            $this->warn("New password: {$password}");
            $this->line('Store this securely — it will not be shown again.');
        }

        return self::SUCCESS;
    }
}
