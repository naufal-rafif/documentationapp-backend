<?php

namespace Tests\Feature\Authentication;

use App\Models\Company;
use App\Models\PermissionGroup;
use App\Models\UserDetail;
use Illuminate\Http\UploadedFile;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\DataTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class BasicTest extends DataTestCase
{
    protected $base_url;

    protected $userData;

    protected function setUp(): void
    {
        parent::setUp();

        $this->base_url = '/api/v1/auth/basic';
        $this->userData = [
            'name' => 'admin',
            'email' => 'admin1@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'full_name' => 'Admin Testing',
            'address' => 'Jl. Test, Jakarta',
            'avatar' => UploadedFile::fake()->image('file1.png', 600, 600),
            'phone_number' => '0812345678',
            'birth_date' => '2000-01-01',
            'gender' => 'male',
            'status_account' => 'active'
        ];
    }

    public function test_can_login()
    {
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => $this->userData['name'],
            'email' => $this->userData['email'],
            'password' => Hash::make($this->userData['password'])
        ]);
        UserDetail::create([
            'user_id' => $user->id,
            'full_name' => $this->userData['full_name'],
            'address' => $this->userData['address'],
            'avatar' => $this->userData['avatar'],
            'phone_number' => $this->userData['phone_number'],
            'birth_date' => $this->userData['birth_date'],
            'gender' => $this->userData['gender'],
            'status_account' => $this->userData['status_account']
        ]);
        $response = $this->postJson($this->base_url . '/login', $this->userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'user' => [
                        'name',
                        'email',
                        'company_id',
                        'permissions' => [],
                        'details' => [
                            'full_name',
                            'address',
                            'avatar',
                            'phone_number',
                            'birth_date',
                            'gender',
                            'status_account',
                        ]
                    ],
                    'token',
                    'token_type',
                ]
            ]);
    }
    public function test_user_not_found_when_login()
    {
        $response = $this->postJson($this->base_url . '/login', ['email' => 'admin2@example.com', 'password' => 'password']);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status_code',
                'message',
            ]);
    }

    public function test_can_register()
    {
        $response = $this->postJson($this->base_url . '/register', $this->userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status_code',
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $this->userData['email'],
            'name' => $this->userData['name']
        ]);
        $this->assertDatabaseHas('user_details', [
            'full_name' => $this->userData['full_name'],
            'address' => $this->userData['address'],
            'phone_number' => $this->userData['phone_number'],
            'birth_date' => $this->userData['birth_date'],
            'gender' => $this->userData['gender'],
            'status_account' => $this->userData['status_account']
        ]);
    }
    public function test_can_register_with_company()
    {

        $this->userData['company_id'] = Company::create([
            'uuid' => Uuid::uuid4(),
            'name' => 'PT. Test',
            'description' => 'PT. Test',
            'logo' => UploadedFile::fake()->image('file1.png', 600, 600),
            'address' => 'Jl. Test, Jakarta',
            'phone_number' => '0812345678',
            'status' => 'active',
            'default_role' => 'Guest'
        ])->uuid;
        $response = $this->postJson($this->base_url . '/register', $this->userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status_code',
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $this->userData['email'],
            'name' => $this->userData['name']
        ]);
        $this->assertDatabaseHas('user_details', [
            'full_name' => $this->userData['full_name'],
            'address' => $this->userData['address'],
            'phone_number' => $this->userData['phone_number'],
            'birth_date' => $this->userData['birth_date'],
            'gender' => $this->userData['gender'],
            'status_account' => $this->userData['status_account']
        ]);
    }
}