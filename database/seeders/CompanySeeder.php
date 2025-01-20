<?php

namespace Database\Seeders;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Ramsey\Uuid\Uuid;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'name' => 'Default', 
                'description' => 'Default Company',
                'logo' => null,
                'phone_number' => null,
                'address' => null,
                'default_role' => 'Guest'
            ],
            
        ];

        foreach ($companies as $group) {
            $data = Company::where('name', $group['name'])->first();
            if($data){
                $data->description = $group['description'];
                $data->logo = $group['logo'];
                $data->phone_number = $group['phone_number'];
                $data->address = $group['address'];
                $data->default_role = $group['default_role'];
                $data->save();
            } else {
                Company::create([
                    'uuid' => Uuid::uuid4(),
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'logo' => $group['logo'],
                    'phone_number' => $group['phone_number'],
                    'address' => $group['address'],
                    'default_role' => $group['default_role'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
