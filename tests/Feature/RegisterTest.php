<?php

namespace Tests\Feature;

use App\Models\UserProfile;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;
use Tests\Traits\WithFakeModels;

class RegisterTest extends TestCase
{
    use RefreshDatabase, WithFakeModels;

    private $authService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authService = Mockery::mock(AuthService::class);
        $this->app->instance(AuthService::class, $this->authService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'firstName' => 'John',
            'lastName'  => 'Doe',
            'email'      => 'john@example.com',
            'password'   => 'Password123!',
            'password_confirmation' => 'Password123!',
        ], $overrides);
    }


    // ==========================================
    // Happy path
    // ==========================================

    public function test_register_creates_account_successfully()
    {
        $profile = $this->makeUserProfile();

        $this->authService
            ->shouldReceive('createUserProfile')
            ->once()
            ->andReturn($profile);

        $response = $this->postJson('/api/v1/auth/register', $this->validPayload());

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Account successfully created.',
                     'errors'  => [],
                 ])
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'errors',
                     'data' => [
                         'first_name',
                         'last_name',
                         'email',
                     ],
                 ]);
    }

    // ==========================================
    // Validation
    // ==========================================

    public function test_register_fails_when_email_missing()
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload([
            'email' => '',
        ]));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_register_fails_when_email_invalid()
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload([
            'email' => 'pas-un-email',
        ]));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['email']);
    }

    public function test_register_fails_when_password_missing()
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload([
            'password' => '',
        ]));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['password']);
    }

    public function test_register_fails_when_first_name_missing()
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload([
            'firstName' => '',
        ]));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['firstName']);
    }

    public function test_register_fails_when_last_name_missing()
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload([
            'lastName' => '',
        ]));

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['lastName']);
    }

    public function test_register_fails_when_payload_empty()
    {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'firstName',
                     'lastName',
                     'email',
                     'password',
                 ]);
    }

    // ==========================================
    // Service errors
    // ==========================================

    public function test_register_returns_500_if_service_fails()
    {
        $this->authService
            ->shouldReceive('createUserProfile')
            ->once()
            ->andThrow(new \Exception('Unexpected error', 500));

        $response = $this->postJson('/api/v1/auth/register', $this->validPayload());

        $response->assertStatus(500)
                 ->assertJson([
                     'success' => false,
                     'message' => 'Error occurred while creating the account.',
                     'data'    => null,
                 ])
                 ->assertJsonStructure([
                     'errors' => ['exception'],
                 ]);
    }

    public function test_register_returns_service_error_code()
    {
        $this->authService
            ->shouldReceive('createUserProfile')
            ->once()
            ->andThrow(new \Exception('Conflict error', 409));

        $response = $this->postJson('/api/v1/auth/register', $this->validPayload());

        $response->assertStatus(409);
    }
}