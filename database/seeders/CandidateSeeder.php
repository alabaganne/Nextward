<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Candidate;
use App\Models\User;

class CandidateSeeder extends Seeder
{
    public function run()
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data can only be seeded in local or testing environments.');
        }

        $candidates = [
            ['name' => 'Ahmed Demo', 'email' => 'ahmed.demo@example.com', 'stage' => 'student'],
            ['name' => 'Salma Demo', 'email' => 'salma.demo@example.com', 'stage' => 'professional'],
            ['name' => 'Nour Demo', 'email' => 'nour.demo@example.com', 'stage' => 'graduate'],
        ];

        $password = bcrypt('password');

        foreach ($candidates as $data) {
            if (User::where('email', $data['email'])->exists()) { continue; }
            $factory = Candidate::factory();
            if ($data['stage'] === 'professional') { $factory = $factory->professional(); }
            if ($data['stage'] === 'graduate') { $factory = $factory->graduate(); }
            $candidate = $factory->create();
            $candidate->user()->save(User::create([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'password'          => $password,
                'phone_number'      => '+216' . rand(20000000, 99999999),
                'email_verified_at' => now(),
            ]));
        }
    }
}
