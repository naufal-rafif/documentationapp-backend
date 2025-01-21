<?php

namespace Tests\Feature;

use App\Http\Controllers\Documentation\Auth as AuthController;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\DataTestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DocumentationTest extends DataTestCase
{
    protected $admin;

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
    }

    public function test_redirect_when_user_is_not_authenticated()
    {
        
        $response = $this->call('GET', '/api/documentation');
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(302);
    }

    public function test_accept_to_access_documentation_if_authorized()
    {
        $cookie = encrypt($this->admin->id);

        $response = $this->call('GET', '/api/documentation', [], ['authentication_id' => $cookie], [], []);
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200);
    }

    public function test_index_redirects_to_documentation_if_user_is_developer()
    {
        $cookie = encrypt($this->admin->id);

        $response = $this->call('GET', '/documentation', [], ['authentication_id' => $cookie], [], []);
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(302);
    }

    public function test_index_go_to_login_page_if_user_is_not_developer()
    {
        $cookie = encrypt($this->admin->id.'1');

        $response = $this->call('GET', '/documentation', [], ['authentication_id' => $cookie], [], []);
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(200);
    }

    public function test_proccesing_login_success()
    {
        $data = [
            'email' => $this->admin->email,
            'password' => 'password'
        ];
        $response = $this->call('POST', '/documentation/login', $data);
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(302);
    }

    public function test_proccesing_login_failed()
    {
        $data = [
            'email' => $this->admin->email,
            'password' => 'wrongpassword'
        ];
        $response = $this->call('POST', '/documentation/login', $data);
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(302);
    }


    public function test_logout()
    {
        $response = $this->call('GET', '/logout');
        $this->output->writeln(substr($response->getContent(), 0, 1000));

        $response->assertStatus(302);
    }
}
