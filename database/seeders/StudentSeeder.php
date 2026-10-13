<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Student;
use App\Models\User;

class StudentSeeder extends Seeder
{
    public function run()
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data can only be seeded in local or testing environments.');
        }

        $students = [
            ['name' => 'Ahmed Demo', 'email' => 'ahmed.demo@example.com', 'stage' => 'student'],
            ['name' => 'Salma Demo', 'email' => 'salma.demo@example.com', 'stage' => 'professional'],
            ['name' => 'Nour Demo', 'email' => 'nour.demo@example.com', 'stage' => 'graduate'],
        ];

        $password = bcrypt('password');

        foreach ($students as $data) {
            if (User::where('email', $data['email'])->exists()) { continue; }
            $factory = Student::factory();
            if ($data['stage'] === 'professional') { $factory = $factory->professional(); }
            if ($data['stage'] === 'graduate') { $factory = $factory->graduate(); }
            $student = $factory->create();
            $student->user()->save(User::create([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'password'          => $password,
                'phone_number'      => '+216' . rand(20000000, 99999999),
                'email_verified_at' => now(),
            ]));
        }
    }
}
