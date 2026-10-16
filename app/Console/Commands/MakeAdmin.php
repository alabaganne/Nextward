<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeAdmin extends Command
{
    protected $signature = 'nextward:make-admin {email : Email of the admin account}';
    protected $description = 'Make an existing account an admin, or create a new admin account';

    public function handle(): int
    {
        $email = trim($this->argument('email'));

        if (Validator::make(['email' => $email], ['email' => 'required|email|max:255'])->fails()) {
            $this->error('Enter a valid email address.');
            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->userable_type || $user->userable_id) {
                $this->error('This account has a candidate or company profile. Use another email for the admin account.');
                return self::FAILURE;
            }

            $user->forceFill(['is_admin' => true, 'email_verified_at' => $user->email_verified_at ?? now()])->save();
            $this->info("{$email} is now an admin.");
            return self::SUCCESS;
        }

        $this->line("No account uses {$email} yet. Let's create one.");
        $data = [
            'name' => trim((string) $this->ask('Name')),
            'password' => (string) $this->secret('Password (at least 8 characters)'),
        ];
        $data['password_confirmation'] = (string) $this->secret('Type the password again');

        $validator = Validator::make($data, [
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }
            return self::FAILURE;
        }

        User::forceCreate([
            'name' => $data['name'],
            'email' => $email,
            'password' => Hash::make($data['password']),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        $this->info("Admin account created for {$email}.");
        return self::SUCCESS;
    }
}
