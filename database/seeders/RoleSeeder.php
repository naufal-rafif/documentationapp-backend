<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Developer',
                'guard_name' => 'api',
                'description' => 'For Developer',
                'level' => 0,
                'permissions' => Permission::all()
            ],
            [
                'name' => 'Super Admin',
                'guard_name' => 'api',
                'description' => 'For Super Admin',
                'level' => 1,
                'permissions' => Permission::whereNotIn('level',[0])->get()
            ],
            [
                'name' => 'Admin',
                'guard_name' => 'api',
                'description' => 'For Admin',
                'level' => 2,
                'permissions' => Permission::whereNotIn('level',[0,1])->get()
            ],
            [
                'name' => 'Guest',
                'guard_name' => 'api',
                'description' => 'For Admin',
                'level' => 2,
                'permissions' => Permission::whereNotIn('level',[0,1,2,3,4,5])->get()
            ],
        ];

        foreach ($roles as $role) {
            $data = Role::where('name', $role['name'])->first();
            if ($data) {
                Role::where('name', $role['name'])->update([
                    'guard_name' => $role['guard_name'],
                    'description' => $role['description'],
                    'level' => $role['level'],
                    'updated_at' => Carbon::now(),
                ]);
            } else {
               $data = Role::create([
                    'name' => $role['name'],
                    'guard_name' => $role['guard_name'],
                    'description' => $role['description'],
                    'uuid' => Uuid::uuid4(),
                    'level' => $role['level'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
            $data->syncPermissions($role['permissions']);
        }
    }
}
