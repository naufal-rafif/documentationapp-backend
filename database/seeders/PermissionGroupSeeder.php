<?php

namespace Database\Seeders;

use App\Models\PermissionGroup;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PermissionGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissionGroup = [
            ['name' => 'User', 'description' => 'for User Purpose'],
            ['name' => 'Role', 'description' => 'for Role Purpose'],
            ['name' => 'Permission', 'description' => 'for Permission Purpose'],
        ];

        foreach ($permissionGroup as $group) {
            $data = PermissionGroup::where('name', $group['name'])->first();
            if($data){
                $data->description = $group['description'];
                $data->save();
            } else {
                PermissionGroup::create([
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}
