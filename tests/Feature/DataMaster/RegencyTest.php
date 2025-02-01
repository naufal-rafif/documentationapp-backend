<?php

namespace Tests\Feature\DataMaster;

use App\Models\DataMaster\Province;
use App\Models\DataMaster\Regency;
use App\Models\User;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Role;
use Tests\DataTestCase;
use Illuminate\Support\Facades\Hash;

class RegencyTest extends DataTestCase
{
    protected $admin;
    protected $base_url;
    protected $regency;

    protected $regencyData;

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

        $this->regency = Regency::first();

        $this->regencyData = [
            'uuid' => Uuid::uuid4()->toString(),
            'province_id' => Province::first()->id,
            'name' => 'Regency Test',
            'alt_name' => 'Regency Test',
            'latitude' => '0.0',
            'longitude' => '0.0',
        ];

        $this->base_url = '/api/v1/data-master/regency';
    }

    public function test_can_get_regencies()
    {
        $province = Province::first();
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '?search=Y&province_id='.$province->uuid);

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

    public function test_can_get_details_regencies()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->regency->uuid);

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

    public function test_invalid_get_details_regencies()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->getJson($this->base_url . '/' . $this->regency->uuid . '1');

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(404)
            ->assertJsonStructure([
                'status_code',
                'message'
            ]);
    }

    public function test_can_create_regencies()
    {
        $regencyData = $this->regencyData;
        $regencyData['province_id'] = Province::first()->uuid;
        $response = $this->actingAs($this->admin, 'api')
            ->postJson($this->base_url, $regencyData);

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

        $this->assertDatabaseHas('regencies', [
            'name' => $this->regencyData['name'],
            'alt_name' => $this->regencyData['alt_name'],
            'longitude' => $this->regencyData['longitude'],
            'latitude' => $this->regencyData['latitude']
        ]);
    }

    public function test_forbidden_create_regencies()
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
            ->postJson($this->base_url, $this->regencyData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_unauthorized_user_create_regencies()
    {
        $existingUser = User::factory()->create();
        $response = $this->actingAs($existingUser, 'api')
            ->postJson($this->base_url, $this->regencyData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_update_regencies()
    {
        $regencies = Regency::create($this->regencyData);
        $regenciesData = [
            'name' => $this->faker->name,
            'province_id' => Province::first()->uuid,
            'alt_name' => $this->faker->name,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/' . $regencies->uuid, $regenciesData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(200)
            ->assertJson([
                'status_code' => 200,
                'message' => 'Regency updated successfully',
                'data' => [
                    'id' => $regencies->uuid,
                    'name' => $regenciesData['name'],
                    'alt_name' => $regenciesData['alt_name'],
                    'latitude' => $regenciesData['latitude'],
                    'longitude' => $regenciesData['longitude'],
                ]
            ]);

        $this->assertDatabaseHas('regencies', [
            'name' => $regenciesData['name'],
            'alt_name' => $regenciesData['alt_name'],
            'latitude' => $regenciesData['latitude'],
            'longitude' => $regenciesData['longitude'],
        ]);
    }

    public function test_update_regencies_failed_not_found()
    {
        $regenciesData = [
            'name' => $this->faker->name,
            'alt_name' => $this->faker->name,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
        ];
        $response = $this->actingAs($this->admin, 'api')
            ->putJson($this->base_url . '/sdasdasdjkfadqlkdemakde', $regenciesData);

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(404)
            ->assertJson([
                'status_code' => 404,
                'message' => 'Regency not found'
            ]);
    }
    public function test_update_regencies_failed_unauthorized()
    {
        $userData = User::factory()->create();
        $regencies = Regency::create($this->regencyData);
        $updatedData = [
            'name' => $this->faker->name,
            'alt_name' => $this->faker->name,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
        ];
        $response = $this->actingAs($userData, 'api')
            ->putJson($this->base_url . '/' . $regencies->uuid . '1', $updatedData);
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_unauthorized_user_delete_regency()
    {
        $regencies = Regency::create($this->regencyData);
        $user = User::factory()->create([
            'uuid' => Uuid::uuid4(),
            'name' => 'user',
            'email' => 'user1@example.com',
            'password' => Hash::make('password')
        ]);
        $response = $this->actingAs($user, 'api')
            ->deleteJson("{$this->base_url}/{$regencies->uuid}");

        $this->output->writeln(substr($response->getContent(), 0, 1000));
        $response->assertStatus(403)
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_can_delete_regency()
    {
        $regencies = Regency::create($this->regencyData);
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/{$regencies->uuid}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('regencies', [
            'id' => $regencies->id
        ]);
    }

    public function test_cannot_delete_non_existent_regency()
    {
        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("{$this->base_url}/123123123");

        $response->assertStatus(404);
    }
}