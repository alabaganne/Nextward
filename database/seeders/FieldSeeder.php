<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FieldSeeder extends Seeder
{
    public function run()
    {
        $fields = [
            'Software Engineering',
            'Data Science & AI',
            'Cybersecurity',
            'Network & Systems',
            'Web Development',
            'Mobile Development',
            'UI/UX Design',
            'DevOps & Cloud',
            'Embedded Systems',
            'Business Intelligence',
            'Digital Marketing',
            'Finance & Accounting',
        ];

        foreach ($fields as $field) {
            DB::table('fields')->updateOrInsert(['name' => $field]);
        }
    }
}
