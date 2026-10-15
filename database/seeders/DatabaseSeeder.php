<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([CitySeeder::class, FieldSeeder::class, OpportunitySeeder::class, ImportSourceSeeder::class]);
    }
}
