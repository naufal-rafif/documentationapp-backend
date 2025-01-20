<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Developer',
                'role' => 'Developer',
                'email' => 'developer@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'company_id' => Company::where('name', 'Default')->first()->id,
                'details' => [
                    'full_name' => 'Developer',
                    'address' => null,
                    'avatar' => null,
                    'phone_number' => '08123456789',
                    'birth_date' => '1990-01-01',
                    'gender' => 'male',
                    'status_account' => 'active'
                ]
            ],
            [
                'name' => 'Admin',
                'role' => 'Super Admin',
                'email' => 'admin@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'company_id' => Company::where('name', 'Default')->first()->id,
                'details' => [
                    'full_name' => 'Admin',
                    'address' => null,
                    'avatar' => null,
                    'phone_number' => '08123456789',
                    'birth_date' => '1990-01-01',
                    'gender' => 'male',
                    'status_account' => 'active'
                ]
            ]
        ];

        foreach ($users as $user) {
            $data = User::where('email', $user['email'])->first();
            if ($data) {
                User::where('email', $user['email'])->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'email_verified_at' => $data['email_verified_at'],
                    'company_id' => $data['company_id'],
                    'updated_at' => Carbon::now(),
                ]);
                UserDetail::where('user_id', $data['id'])->update([
                    'full_name' => $user['details']['full_name'],
                    'address' => $user['details']['address'],
                    'avatar' => $user['details']['avatar'],
                    'phone_number' => $user['details']['phone_number'],
                    'birth_date' => $user['details']['birth_date'],
                    'gender' => $user['details']['gender'],
                    'status_account' => $user['details']['status_account'],
                    'updated_at' => Carbon::now(),
                ]);
            } else {
                $data = User::create([
                    'uuid' => Uuid::uuid4(),
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => $user['password'],
                    'email_verified_at' => $user['email_verified_at'],
                    'company_id' => $user['company_id'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
                UserDetail::create([
                    'user_id' => $data->id,
                    'full_name' => $user['details']['full_name'],
                    'address' => $user['details']['address'],
                    'avatar' => $user['details']['avatar'],
                    'phone_number' => $user['details']['phone_number'],
                    'birth_date' => $user['details']['birth_date'],
                    'gender' => $user['details']['gender'],
                    'status_account' => $user['details']['status_account'],
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
            $role = Role::where('name', $user['role'])->first();
            $data->assignRole($role);
        }
    }
}
