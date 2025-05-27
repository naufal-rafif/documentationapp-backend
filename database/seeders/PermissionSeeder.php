<?php

namespace Database\Seeders;

use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // User Management
            ['name' => 'access-users', 'guard_name' => 'api', 'group_id' => 'User', 'label' => 'Access User', 'description' => 'For Access User', 'level' => 5],
            ['name' => 'view-users', 'guard_name' => 'api', 'group_id' => 'User', 'label' => 'View User', 'description' => 'For View User', 'level' => 5],
            ['name' => 'create-users', 'guard_name' => 'api', 'group_id' => 'User', 'label' => 'Create User', 'description' => 'For Create User', 'level' => 5],
            ['name' => 'edit-users', 'guard_name' => 'api', 'group_id' => 'User', 'label' => 'Edit User', 'description' => 'For Edit User', 'level' => 5],
            ['name' => 'delete-users', 'guard_name' => 'api', 'group_id' => 'User', 'label' => 'Delete User', 'description' => 'For Delete User', 'level' => 5],
            
            // Role Management
            ['name' => 'access-roles', 'guard_name' => 'api', 'group_id' => 'Role', 'label' => 'Access Role', 'description' => 'For Access Role', 'level' => 5],
            ['name' => 'view-roles', 'guard_name' => 'api', 'group_id' => 'Role', 'label' => 'View Role', 'description' => 'For View Role', 'level' => 2],
            ['name' => 'create-roles', 'guard_name' => 'api', 'group_id' => 'Role', 'label' => 'Create Role', 'description' => 'For Create Role', 'level' => 2],
            ['name' => 'edit-roles', 'guard_name' => 'api', 'group_id' => 'Role', 'label' => 'Edit Role', 'description' => 'For Edit Role', 'level' => 2],
            ['name' => 'delete-roles', 'guard_name' => 'api', 'group_id' => 'Role', 'label' => 'Delete Role', 'description' => 'For Delete Role', 'level' => 2],

            // Permission Management
            ['name' => 'access-permissions', 'guard_name' => 'api', 'group_id' => 'Permission', 'label' => 'Access Permission', 'description' => 'For Access Permission', 'level' => 2],
            ['name' => 'view-permissions', 'guard_name' => 'api', 'group_id' => 'Permission', 'label' => 'View Permission', 'description' => 'For View Permission', 'level' => 2],
            ['name' => 'create-permissions', 'guard_name' => 'api', 'group_id' => 'Permission', 'label' => 'Create Permission', 'description' => 'For Create Permission', 'level' => 0],
            ['name' => 'edit-permissions', 'guard_name' => 'api', 'group_id' => 'Permission', 'label' => 'Edit Permission', 'description' => 'For Edit Permission', 'level' => 0],
            ['name' => 'delete-permissions', 'guard_name' => 'api', 'group_id' => 'Permission', 'label' => 'Delete Permission', 'description' => 'For Delete Permission', 'level' => 0],
        ];

        foreach ($permissions as $permission) {
            $permission_group = PermissionGroup::where('name', $permission['group_id'])->select('id')->first();
            $data = Permission::where('name', $permission['name'])->first();
            if ($data) {
                Permission::where('name', $permission['name'])->update([
                    'guard_name' => $permission['guard_name'],
                    'group_id' => $permission_group->id,
                    'label' => $permission['label'],
                    'description' => $permission['description'],
                    'level' => $permission['level'],
                    'updated_at' => Carbon::now(),
                ]);
            } else {
               Permission::create([
                    'name' => $permission['name'],
                    'guard_name' => $permission['guard_name'],
                    'group_id' => $permission_group->id,
                    'label' => $permission['label'],
                    'description' => $permission['description'],
                    'uuid' => Uuid::uuid4(),
                    'level' => $permission['level'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }
}