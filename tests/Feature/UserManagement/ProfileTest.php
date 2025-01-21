<?php

namespace Tests\Feature\UserManagement;

use App\Models\Company;
use App\Models\UserDetail;
use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;
use Tests\DataTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ProfileTest extends DataTestCase
{
    protected $admin;
    protected $base_url;

    protected function setUp(): void
    {
        parent::setUp();

        // Create admin user for protected routes
        $this->admin = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'admin',
            'email' => 'admin1@example.com',
            'password' => Hash::make('password'),
        ]);
        $role = Role::where('name', 'Developer')->first();
        $this->admin->assignRole($role);
        
        UserDetail::create([
            'user_id' => $this->admin->id,
            'full_name' => $this->faker->name,
            'address' => $this->faker->address,
            'avatar' => UploadedFile::fake()->image('file1.png', 600, 600),
            'phone_number' => $this->faker->phoneNumber,
            'birth_date' => $this->faker->date('Y-m-d'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'status_account' => $this->faker->randomElement(['active', 'inactive'])
        ]);

        $this->base_url = '/api/v1/user/profile';
    }

    public function test_can_get_profile()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'details' => [
                        'full_name',
                        'address',
                        'avatar',
                        'phone_number',
                        'birth_date',
                        'gender',
                        'status_account'
                    ]
                ]
            ]);
    }

    public function test_can_update_profile()
    {
        $userData = [
            'name' => $this->faker->name,
            'full_name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'avatar' => UploadedFile::fake()->image('file1.png', 600, 600),
            'phone_number' => $this->faker->phoneNumber,
            'birth_date' => $this->faker->date('Y-m-d'),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'status_account' => $this->faker->randomElement(['active', 'inactive'])
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url, $userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'details' => [
                        'full_name',
                        'address',
                        'avatar',
                        'phone_number',
                        'birth_date',
                        'gender',
                        'status_account'
                    ]
                ]
            ]);
    }
}