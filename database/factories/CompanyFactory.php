<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition()
    {
        $name = $this->faker->randomElement(['Atlas Demo', 'Cedar Demo', 'Harbor Demo', 'Orbit Demo', 'Lumen Demo']);
        $domain = Str::slug($name).'.example.com';
        $city = City::where('name', '!=', 'Remote')->inRandomOrder()->first();

        return [
            'name' => $name,
            'website' => 'https://'.$domain,
            'domain' => $domain,
            'about' => 'Fictional demo employer building software and data products. No real vacancy or employer affiliation is implied.',
            'city_id' => $city?->id,
            'country_code' => 'TN',
            'location' => $city?->name,
        ];
    }
}
