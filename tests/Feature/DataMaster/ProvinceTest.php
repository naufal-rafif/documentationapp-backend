<?php

namespace Tests\Feature\DataMaster;

use App\Models\DataMaster\Province;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;
use Tests\DataTestCase;
use Illuminate\Support\Facades\Hash;

class ProvinceTest extends DataTestCase
{
    protected $admin;
    protected $base_url;
    protected $province;

    protected $provinceData;

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

        $this->province = Province::first();

        $this->provinceData = [
            'uuid' => Uuid::uuid4()->toString(),
            'id' => 912931939,
            'name' => 'Province Test',
            'alt_name' => 'Province Test',
            'latitude' => '0.0',
            'longitude' => '0.0',
        ];

        $this->base_url = '/api/v1/data-master/province';
    }

    public function test_can_get_province()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '?search=Y');

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
                        'alt_name',
                        'latitude',
                        'longitude',
                    ]
                ]
            ]);
    }

    public function test_can_get_details_province()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->province->uuid);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'alt_name',
                    'latitude',
                    'longitude',
                ]
            ]);
    }

    public function test_invalid_get_details_province()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->province->uuid . '1');

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status_code',
                'message'
            ]);
    }

    public function test_can_create_province()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->postJson($this->base_url, $this->provinceData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status_code',
                'message',
                'data' => [
                    'id',
                    'name',
                    'alt_name',
                    'latitude',
                    'longitude',
                ]
            ]);

        $this->assertDatabaseHas('provinces', [
            'name' => $this->provinceData['name'],
            'alt_name' => $this->provinceData['alt_name'],
            'longitude' => $this->provinceData['longitude'],
            'latitude' => $this->provinceData['latitude']
        ]);
    }
    public function test_forbidden_create_provinces()
    {
        $superAdmin = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'superAdmin',
            'email' => 'superAdmin1@example.com',
            'password' => Hash::make('password'),
        ]);
        $role = Role::where('name', 'Super Admin')->first();
        $superAdmin->assignRole($role);
        $response = $this->actingAs($superAdmin, 'api')
            ->postJson($this->base_url, $this->provinceData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_unauthorized_user_create_provinces()
    {
        $existingUser = User::factory()->create();
        $response = $this->actingAs($existingUser, 'api')
            ->postJson($this->base_url, $this->provinceData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_update_province()
    {
        $provinces = Province::create($this->provinceData);
        // dd('test');
        $provincesData = [
            'name' => $this->faker->name,
            'alt_name' => $this->faker->name,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/' . $provinces->uuid, $provincesData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'Province updated successfully',
                'data' => [
                    'id' => $provinces->uuid,
                    'name' => $provincesData['name'],
                    'alt_name' => $provincesData['alt_name'],
                    'latitude' => $provincesData['latitude'],
                    'longitude' => $provincesData['longitude'],
                ]
            ]);

        $this->assertDatabaseHas('provinces', [
            'name' => $provincesData['name'],
            'alt_name' => $provincesData['alt_name'],
            'latitude' => $provincesData['latitude'],
            'longitude' => $provincesData['longitude'],
        ]);
    }

    public function test_update_province_failed_not_found()
    {
        $provincesData = [
            'name' => $this->faker->name,
            'alt_name' => $this->faker->name,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/sdasdasdjkfadqlkdemakde', $provincesData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(404)
            ->assertJson([
                'status_code' => 404,
                'message' => 'Provinces not found'
            ]);
    }
    public function test_update_province_failed_unauthorized()
    {
        $userData = User::factory()->create();
        $provinces = Province::create($this->provinceData);
        $updatedData = [
            'name' => $this->faker->name,
            'alt_name' => $this->faker->name,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
        ];
        $response = $this->actingAs($userData, 'api')
            ->putJson($this->base_url . '/' . $provinces->uuid . '1', $updatedData);
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_unauthorized_user_delete_province()
    {
        $provinces = Province::create($this->provinceData);
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);
        $response = $this->actingAs($user, 'api')
            ->deleteJson("{$this->base_url}/{$provinces->uuid}");

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_delete_province()
    {
        $provinces = Province::create($this->provinceData);
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/{$provinces->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('provinces', [
            'id' => $provinces->id
        ]);
    }

    public function test_cannot_delete_non_existent_province()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/123123123");

        $response->assertStatus(404);
    }
}