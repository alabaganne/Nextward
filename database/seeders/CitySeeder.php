<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    public function run()
    {
        $cities = [
            'Tunis', 'Sfax', 'Sousse', 'Kairouan', 'Bizerte',
            'Gabès', 'Ariana', 'Gafsa', 'Monastir', 'Ben Arous',
            'Nabeul', 'Mahdia', 'Médenine', 'Jendouba', 'Kasserine',
        ];

        foreach ($cities as $city) {
            DB::table('cities')->updateOrInsert(['name' => $city]);
        }
    }
}
