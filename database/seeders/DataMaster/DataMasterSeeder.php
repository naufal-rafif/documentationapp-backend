<?php

namespace Database\Seeders\DataMaster;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DataMasterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ProvinceSeeder::class,
            RegencySeeder::class,
            DistrictSeeder::class
        ]);
    }
}
