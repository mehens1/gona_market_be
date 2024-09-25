<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('states')->truncate();

        $states = [
            ['state' => 'Abia', 'added_by' => 1],
            ['state' => 'Adamawa', 'added_by' => 1],
            ['state' => 'Akwa Ibom', 'added_by' => 1],
            ['state' => 'Anambra', 'added_by' => 1],
            ['state' => 'Bauchi', 'added_by' => 1],
            ['state' => 'Bayelsa', 'added_by' => 1],
            ['state' => 'Benue', 'added_by' => 1],
            ['state' => 'Borno', 'added_by' => 1],
            ['state' => 'Cross River', 'added_by' => 1],
            ['state' => 'Delta', 'added_by' => 1],
            ['state' => 'Ebonyi', 'added_by' => 1],
            ['state' => 'Edo', 'added_by' => 1],
            ['state' => 'Ekiti', 'added_by' => 1],
            ['state' => 'Enugu', 'added_by' => 1],
            ['state' => 'Gombe', 'added_by' => 1],
            ['state' => 'Imo', 'added_by' => 1],
            ['state' => 'Jigawa', 'added_by' => 1],
            ['state' => 'Kaduna', 'added_by' => 1],
            ['state' => 'Kano', 'added_by' => 1],
            ['state' => 'Katsina', 'added_by' => 1],
            ['state' => 'Kebbi', 'added_by' => 1],
            ['state' => 'Kogi', 'added_by' => 1],
            ['state' => 'Kwara', 'added_by' => 1],
            ['state' => 'Lagos', 'added_by' => 1],
            ['state' => 'Nasarawa', 'added_by' => 1],
            ['state' => 'Niger', 'added_by' => 1],
            ['state' => 'Ogun', 'added_by' => 1],
            ['state' => 'Ondo', 'added_by' => 1],
            ['state' => 'Osun', 'added_by' => 1],
            ['state' => 'Oyo', 'added_by' => 1],
            ['state' => 'Plateau', 'added_by' => 1],
            ['state' => 'Rivers', 'added_by' => 1],
            ['state' => 'Sokoto', 'added_by' => 1],
            ['state' => 'Taraba', 'added_by' => 1],
            ['state' => 'Yobe', 'added_by' => 1],
            ['state' => 'Zamfara', 'added_by' => 1],
            ['state' => 'Federal Capital Territory', 'added_by' => 1],
        ];

        DB::table('states')->insert($states);

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    // Running the Seeder:
    // php artisan db:seed --class=StatesTableSeeder
    // to run all seeders
    // php artisan db:seed

}
