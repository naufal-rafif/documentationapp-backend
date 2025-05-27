<?php

namespace Tests\Feature\UserManagement;

use App\Models\Company;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\DataTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleTest extends DataTestCase
{
    protected $admin;
    protected $base_url;
    protected $role;

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
        $this->role = Role::where('name', 'Developer')->first();
        $this->admin->assignRole($this->role);

        $this->base_url = '/api/v1/user/role';
    }

    public function test_can_get_role()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '?search=D');

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
                        'description',
                        'permissions' => [
                            '*' => [
                                'id',
                                'label',
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function test_unauthorized_user_get_role()
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

    public function test_can_get_details_role()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->role->uuid);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                    'permissions' => [
                        '*' => [
                            'id',
                            'label',
                        ]
                    ]
                ]
            ]);
    }

    public function test_unauthorized_user_get_details_role()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'api')
            ->getJson($this->base_url . '/' . $this->role->uuid);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }
    public function test_invalid_get_details_role()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->role->uuid . '1');

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status_code',
                'message'
            ]);
    }

    public function test_can_create_role()
    {
        $user_level = $this->admin->roles[0]->level;
        $permissions = Permission::where('level','>', $user_level)->pluck('uuid')->toArray();
        $userData = [
            'name' => 'Tester',
            'level' => 6, 
            'description' => $this->faker->text(50),
            'permissions' => $permissions,
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->postJson($this->base_url, $userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'level',
                    'description'
                ]
            ]);

        $this->assertDatabaseHas('roles', [
            'name' => $userData['name'],
            'level' => $userData['level'],
            'description' => $userData['description']
        ]);
    }
    public function test_forbidden_create_role()
    {
        $superAdmin = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'superAdmin',
            'email' => 'superAdmin1@example.com',
            'password' => Hash::make('password'),
        ]);
        $role = Role::where('name', 'Super Admin')->first();
        $superAdmin->assignRole($role);
        $user_level = $superAdmin->roles[0]->level;
        $permissions = Permission::where('level','>', $user_level)->pluck('uuid')->toArray();
        $userData = [
            'name' => 'Tester',
            'level' => 1, 
            'description' => $this->faker->text(50),
            'permissions' => $permissions,
        ];
        $response = $this->actingAs($superAdmin, 'api')
            ->postJson($this->base_url, $userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'status_code',
                'message',
            ]);
    }

    public function test_unauthorized_user_create_role()
    {
        $existingUser = User::factory()->create();
        $userData = [
            'name' => $this->faker->name,
        ];
        $response = $this->actingAs($existingUser, 'api')
            ->postJson($this->base_url, $userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_update_role()
    {
        $user_level = $this->admin->roles[0]->level;
        $permissions = Permission::where('level', '>', $user_level)->pluck('uuid')->toArray();
        $roles = Role::where('name', 'Guest')->first();
        $updatedData = [
            'name' => 'Test Role',
            'description' => $this->faker->text(50),
            'permissions' => $permissions
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/' . $roles->uuid, $updatedData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'Role updated successfully',
                'data' => [
                    'id' => $roles->uuid,
                    'name' => $updatedData['name'],
                    'description' => $updatedData['description'],
                ]
            ]);

        $this->assertDatabaseHas('roles', [
            'name' => $updatedData['name'],
            'description' => $updatedData['description'],
        ]);
    }

    public function test_update_role_failed_not_found()
    {
        $user_level = $this->admin->roles[0]->level;
        $permissions = Permission::where('level', '>', $user_level)->pluck('uuid')->toArray();
        $roles = Role::where('name', 'Guest')->first();
        $updatedData = [
            'name' => 'Test Role',
            'description' => $this->faker->text(50),
            'permissions' => $permissions
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/' . $roles->uuid . '1', $updatedData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(404)
            ->assertJson([
                'status_code' => 404,
                'message' => 'Role Not Found'
            ]);
    }
    public function test_update_role_failed_unauthorized()
    {
        $userData = User::factory()->create();
        $user_level = $this->admin->roles[0]->level;
        $permissions = Permission::where('level', '>', $user_level)->pluck('uuid')->toArray();
        $roles = Role::where('name', 'Guest')->first();
        $updatedData = [
            'name' => 'Test Role',
            'description' => $this->faker->text(50),
            'permissions' => $permissions
        ];
        $response = $this->actingAs($userData, 'api')
            ->putJson($this->base_url . '/' . $roles->uuid . '1', $updatedData);
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_unauthorized_user_delete_role()
    {
        $roles = Role::where('name', 'Guest')->first();
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);
        $response = $this->actingAs($user, 'api')
            ->deleteJson("{$this->base_url}/{$roles->uuid}");

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_delete_roler()
    {
        $roles = Role::where('name', 'Guest')->first();
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/{$roles->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('roles', [
            'id' => $roles->id
        ]);
    }

    public function test_cannot_delete_non_existent_role()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/123123123");

        $response->assertStatus(404);
    }
}