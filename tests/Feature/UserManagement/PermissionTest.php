<?php

namespace Tests\Feature\UserManagement;

use App\Models\Company;
use App\Models\PermissionGroup;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\DataTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PermissionTest extends DataTestCase
{
    protected $admin;
    protected $base_url;

    protected $permission;

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
        $this->permission = Permission::where('name', 'edit-permissions')->first();

        $this->base_url = '/api/v1/user/permission';
    }

    public function test_can_get_permission()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '?search=View');

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    '*' => [
                        'name',
                        'description',
                        'permissions' => [
                            '*' => [
                                'id',
                                'label',
                                'description',
                            ]
                        ]
                    ]
                ]
            ]);
    }

    public function test_unauthorized_user_get_permission()
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

    public function test_can_get_details_permission()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->permission->uuid);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'label',
                    'description',
                ]
            ]);
    }

    public function test_unauthorized_user_get_details_permissions()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user, 'api')
            ->getJson($this->base_url . '/' . $this->permission->uuid);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_invalid_get_details_permissions()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->permission->uuid . '1');

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status_code',
                'message'
            ]);
    }

    public function test_can_create_permission()
    {
        $userData = [
            'name' => 'view-tester',
            'group_id' => PermissionGroup::first()->id,
            'level' => 1,
            'label' => 'View Tester',
            'description' => $this->faker->text(50)
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

        $this->assertDatabaseHas('permissions', [
            'name' => $userData['name'],
            'group_id' => $userData['group_id'],
            'level' => $userData['level'],
            'label' => $userData['label'],
            'description' => $userData['description']
        ]);
    }

    public function test_missing_permission_group_id_create_permission()
    {
        $userData = [
            'name' => 'view-tester',
            'level' => 1,
            'label' => 'View Tester',
            'description' => $this->faker->text(50)
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->postJson($this->base_url, $userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(400)
            ->assertJsonStructure([
                'status_code',
                'message',
            ]);
    }
    public function test_not_found_permission_group_id_create_permission()
    {
        $userData = [
            'name' => 'view-tester',
            'level' => 1,
            'group_id' => 1231231,
            'label' => 'View Tester',
            'description' => $this->faker->text(50)
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->postJson($this->base_url, $userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status_code',
                'message',
            ]);
    }

    public function test_forbidden_create_permission()
    {
        $superAdmin = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'superAdmin',
            'email' => 'superAdmin1@example.com',
            'password' => Hash::make('password'),
        ]);
        $role = Role::where('name', 'Super Admin')->first();
        $superAdmin->assignRole($role);
        $userData = [
            'name' => 'view-tester',
            'group_id' => PermissionGroup::first()->id,
            'level' => 1,
            'label' => 'View Tester',
            'description' => $this->faker->text(50)
        ];
        $response = $this->actingAs($superAdmin, 'api')
            ->postJson($this->base_url, $userData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_update_permission()
    {
        $updatedData = [
            'name' => 'view-tester-edit',
            'level' => 1,
            'label' => 'View Tester Edit'
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/' . $this->permission->uuid, $updatedData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'Permission updated successfully',
                'data' => [
                    'id' => $this->permission->uuid,
                    'name' => $updatedData['name'],
                    'level' => $updatedData['level'],
                    'label' => $updatedData['label'],
                    'description' => $this->permission->description,
                ]
            ]);

        $this->assertDatabaseHas('permissions', [
            'uuid' => $this->permission->uuid,
            'group_id' => $this->permission->group_id,
            'name' => $updatedData['name'],
            'label' => $updatedData['label'],
            'level' => $updatedData['level'],
            'description' => $this->permission->description,
        ]);
    }

    public function test_update_permission_failed_not_found()
    {
        $updatedData = [
            'name' => 'view-tester-edit',
            'level' => 1,
            'label' => 'View Tester Edit'
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/' . $this->permission->uuid . '1', $updatedData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(404)
            ->assertJson([
                'status_code' => 404,
                'message' => 'Permission Not Found'
            ]);
    }

    public function test_update_permission_failed_unauthorized()
    {
        $userData = User::factory()->create();
        $updatedData = [
            'name' => 'view-tester-edit',
            'level' => 1,
            'label' => 'View Tester Edit'
        ];
        $response = $this->actingAs($userData, 'api')
            ->putJson($this->base_url . '/' . $this->permission->uuid, $updatedData);
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_unauthorized_user_delete_permission()
    {
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);
        $response = $this->actingAs($user, 'api')
            ->deleteJson("{$this->base_url}/{$this->permission->uuid}");

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_delete_permission()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/{$this->permission->uuid}");
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200);

        $this->assertDatabaseMissing('permissions', [
            'id' => $this->permission->id
        ]);
    }

    public function test_cannot_delete_non_existent_permission()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/123123123");
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(404)
            ->assertJson([
                'status_code' => 404,
                'message' => 'Permission Not Found'
            ]);
    }
}