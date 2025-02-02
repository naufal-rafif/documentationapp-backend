<?php

namespace Tests\Feature\DataMaster;

use App\Models\DataMaster\District;
use App\Models\DataMaster\Province;
use App\Models\DataMaster\Regency;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;
use Tests\DataTestCase;
use Illuminate\Support\Facades\Hash;

class DistrictTest extends DataTestCase
{
    protected $admin;
    protected $base_url;
    protected $district;

    protected $districtData;

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

        $this->district = District::first();

        $this->districtData = [
            'uuid' => Uuid::uuid4()->toString(),
            'province_id' => Province::first()->id,
            'name' => 'District Test',
            'alt_name' => 'District Test',
            'latitude' => '0.0',
            'longitude' => '0.0',
        ];

        $this->base_url = '/api/v1/data-master/district';
    }

    public function test_can_get_districts()
    {
        $regency = Regency::first();
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '?search=a&regency_id='.$regency->uuid);

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

    public function test_can_get_details_districts()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->district->uuid);

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

    public function test_invalid_get_details_districts()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->district->uuid . '1');

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status_code',
                'message'
            ]);
    }

    public function test_can_create_districts()
    {
        $districtData = $this->districtData;
        $districtData['regency_id'] = Regency::first()->uuid;
        $response = $this->actingAs($this->admin, 'api')
            ->postJson($this->base_url, $districtData);

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

        $this->assertDatabaseHas('districts', [
            'name' => $this->districtData['name'],
            'alt_name' => $this->districtData['alt_name'],
            'longitude' => $this->districtData['longitude'],
            'latitude' => $this->districtData['latitude']
        ]);
    }

    public function test_forbidden_create_districts()
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
            ->postJson($this->base_url, $this->districtData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_unauthorized_user_create_districts()
    {
        $existingUser = User::factory()->create();
        $response = $this->actingAs($existingUser, 'api')
            ->postJson($this->base_url, $this->districtData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_update_districts()
    {
        $districts = District::create($this->districtData);
        $districtsData = [
            'name' => $this->faker->name,
            'regency_id' => Regency::first()->uuid,
            'alt_name' => $this->faker->name,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/' . $districts->uuid, $districtsData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'District updated successfully',
                'data' => [
                    'id' => $districts->uuid,
                    'name' => $districtsData['name'],
                    'alt_name' => $districtsData['alt_name'],
                    'latitude' => $districtsData['latitude'],
                    'longitude' => $districtsData['longitude'],
                ]
            ]);

        $this->assertDatabaseHas('districts', [
            'name' => $districtsData['name'],
            'alt_name' => $districtsData['alt_name'],
            'latitude' => $districtsData['latitude'],
            'longitude' => $districtsData['longitude'],
        ]);
    }

    public function test_update_districts_failed_not_found()
    {
        $districtsData = [
            'name' => $this->faker->name,
            'alt_name' => $this->faker->name,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/sdasdasdjkfadqlkdemakde', $districtsData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(404)
            ->assertJson([
                'status_code' => 404,
                'message' => 'District not found'
            ]);
    }
    public function test_update_districts_failed_unauthorized()
    {
        $userData = User::factory()->create();
        $districts = District::create($this->districtData);
        $updatedData = [
            'name' => $this->faker->name,
            'alt_name' => $this->faker->name,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
        ];
        $response = $this->actingAs($userData, 'api')
            ->putJson($this->base_url . '/' . $districts->uuid . '1', $updatedData);
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_unauthorized_user_delete_district()
    {
        $districts = District::create($this->districtData);
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);
        $response = $this->actingAs($user, 'api')
            ->deleteJson("{$this->base_url}/{$districts->uuid}");

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_delete_district()
    {
        $districts = District::create($this->districtData);
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/{$districts->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('districts', [
            'id' => $districts->id
        ]);
    }

    public function test_cannot_delete_non_existent_district()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/123123123");

        $response->assertStatus(404);
    }
}