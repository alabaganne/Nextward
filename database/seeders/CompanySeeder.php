<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\City;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run()
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Demo data can only be seeded in local or testing environments.');
        }

        $password = bcrypt('password');
        foreach ([['Cedar Demo', 'cedar', 'Tunis'], ['Marina Demo', 'marina', 'Sousse'], ['Dune Demo', 'dune', 'Sfax']] as [$name, $key, $city]) {
            if (User::where('email', $key.'@example.com')->exists()) { continue; }
            $company = Company::create(['name' => $name, 'website' => 'https://'.$key.'.example.com', 'domain' => $key.'.example.com', 'about' => 'Fictional demo employer. No real company or vacancy is represented.', 'city_id' => City::firstOrCreate(['name' => $city])->id, 'country_code' => 'TN', 'location' => $city]);
            $company->user()->save(User::create(['name' => $name, 'email' => $key.'@example.com', 'password' => $password, 'email_verified_at' => now()]));
        }
    }
}
