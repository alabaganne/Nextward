<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InternshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(OpportunitySeeder::class);
    }
}
