<?php

namespace Tests\Feature\UserManagement;

use App\Models\Company;
use App\Models\UserDetail;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;
use Tests\DataTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserTest extends DataTestCase
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

        $this->base_url = '/api/v1/user/user';
    }

    public function test_can_get_user()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url.'?search=A');

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'meta' => [
                    'total_data',
                    'total_pages',
                    'current_page',
                    'per_page',
                ],
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'roles' => [
                            '*' => []
                        ]
                    ]
                ]
            ]);
    }
    public function test_can_get_details_user()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->admin->uuid);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'roles' => [
                        '*' => []
                    ]
                ]
            ]);
    }
    public function test_unauthorized_user_get_details_user()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'api')
            ->getJson($this->base_url . '/' . $this->admin->uuid);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }
    public function test_invalid_get_details_user()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->admin->uuid . '1');

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status_code',
                'message'
            ]);
    }

    public function test_unauthorized_user_get_user()
    {

        $existingUser = User::factory()->create();

        $response = $this->actingAs($existingUser, 'api')
            ->getJson($this->base_url);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_create_user()
    {
        $userData = [
            'name' => $this->faker->name,
            'full_name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->postJson($this->base_url, $userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status_code',
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'name' => $userData['name']
        ]);
    }
    public function test_can_create_user_with_company()
    {
        $company = Company::first();
        $userData = [
            'name' => $this->faker->name,
            'full_name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_id' => $company ? $company->uuid : null
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->postJson($this->base_url, $userData);
            

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status_code',
                'message',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $userData['email'],
            'name' => $userData['name']
        ]);
    }

    public function test_unauthorized_user_create_user()
    {
        $existingUser = User::factory()->create();
        $userData = [
            'name' => $this->faker->name,
            'full_name' => $this->faker->name,
            'email' => $this->faker->unique()->safeEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];
        $response = $this->actingAs($existingUser, 'api')
            ->postJson($this->base_url, $userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_cannot_create_user_with_existing_email()
    {
        $existingUser = User::factory()->create();

        $userData = [
            'name' => $this->faker->name,
            'email' => $existingUser->email,
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson($this->base_url, $userData);
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthorized_user_update_user()
    {
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);
        $updatedData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com'
        ];
        $response = $this->actingAs($user, 'api')
            ->putJson($this->base_url . '/' . $this->admin->uuid, $updatedData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_update_user()
    {
        $updatedData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/' . $this->admin->uuid, $updatedData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'User Successfully updated',
                'data' => [
                    'name' => $updatedData['name'],
                    'email' => $updatedData['email']
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $updatedData['email'],
            'name' => $updatedData['name']
        ]);
    }
    public function test_can_update_user_with_company()
    {
        $company = Company::first();
        $updatedData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'company_id' => $company->uuid
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/' . $this->admin->uuid, $updatedData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'User Successfully updated',
                'data' => [
                    'name' => $updatedData['name'],
                    'email' => $updatedData['email']
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => $updatedData['email'],
            'name' => $updatedData['name']
        ]);
    }

    public function test_update_user_failed_not_found()
    {
        $updatedData = [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/' . $this->admin->uuid . '1', $updatedData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(404)
            ->assertJson([
                'status_code' => 404,
                'message' => 'User Not Found'
            ]);
    }

    public function test_unauthorized_user_delete_user()
    {
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);
        $response = $this->actingAs($user, 'api')
            ->deleteJson("{$this->base_url}/{$user->uuid}");

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_delete_user()
    {
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/{$user->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id
        ]);
    }
    public function test_delete_user_not_found()
    {
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/{$user->uuid}12");

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status_code',
                'message',
            ]);
    }

    public function test_cannot_delete_non_existent_user()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson('/api/users/999');

        $response->assertStatus(404);
    }

    public function test_update_user_status()
    {
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);
        $dataStatus = [
            'status' => 'not_active'
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("{$this->base_url}/{$user->uuid}", $dataStatus);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'User update successfully'
            ]);

        $this->assertDatabaseHas('users', [
            ['deleted_at', '!=', null],
        ]);
        $this->assertDatabaseHas('user_details', [
            'user_id' => $user->id,
            'status_account' => $dataStatus['status']
        ]);
    }
    public function test_update_user_status_where_detail_not_exist()
    {
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);
        $dataStatus = [
            'status' => 'not_active'
        ];
        $user_detail = UserDetail::where('user_id', $user->id)->first();
        if(!$user_detail){
            UserDetail::create([
                'user_id' => $user->id
            ]);
        }
        $response = $this->actingAs($this->admin, 'api')
            ->postJson("{$this->base_url}/{$user->uuid}", $dataStatus);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'User update successfully'
            ]);

        $this->assertDatabaseHas('users', [
            ['deleted_at', '!=', null],
        ]);
        $this->assertDatabaseHas('user_details', [
            'user_id' => $user->id,
            'status_account' => $dataStatus['status']
        ]);
    }
    public function test_update_user_status_not_found()
    {
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);
        $dataStatus = [
            'status' => 'not_active'
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson("{$this->base_url}/{$user->uuid}123", $dataStatus);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(404)
            ->assertJsonStructure([
                'status_code',
                'message',
            ]);
    }

    public function test_unauthorized_user_update_user_status()
    {
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);
        $dataStatus = [
            'status' => 'active'
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson("{$this->base_url}/{$user->uuid}", $dataStatus);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }
}