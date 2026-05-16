<?php

namespace Tests\Feature;

use App\Services\AuthService;
use Mockery;
use Tests\TestCase;

class LoginTest extends TestCase
{
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
            'email'    => 'john@example.com',
            'password' => 'Password123!',
        ], $overrides);
    }

    private function fakeLoginData(array $overrides = []): array
    {
        return array_merge([
            'user' => [
                'userId' => 'user-uuid-123',
                'email'  => 'john@example.com',
            ],
            'accessToken'  => 'fake-access-token-123',
            'sessionId'    => 'fake-session-id-123',
            'profile'      => [
                'id'         => 1,
                'first_name' => 'John',
                'last_name'  => 'Doe',
                'phone'     => '1234567890',
                'email'      => 'john@example.com',
                'company_name' => 'John\'s Company',
                'currency'   => 'USD',
            ],
        ], $overrides);
    }

    // ==========================================
    // Happy path
    // ==========================================

    public function test_login_succeeds()
    {
        $this->authService
            ->shouldReceive('login')
            ->once()
            ->with('john@example.com', 'Password123!')
            ->andReturn($this->fakeLoginData());

        $response = $this->postJson('/api/v1/auth/login', $this->validPayload());

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
                'errors'  => [],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
                'data' => [
                    'accessToken',
                    'sessionId',
                    'user',
                    'profile',
                ],
            ]);
    }

    // ==========================================
    // Validation
    // ==========================================

    public function test_login_fails_when_email_missing()
    {
        $response = $this->postJson('/api/v1/auth/login', $this->validPayload([
            'email' => '',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_when_email_invalid()
    {
        $response = $this->postJson('/api/v1/auth/login', $this->validPayload([
            'email' => 'pas-un-email',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_fails_when_password_missing()
    {
        $response = $this->postJson('/api/v1/auth/login', $this->validPayload([
            'password' => '',
        ]));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_fails_when_payload_empty()
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    // ==========================================
    // Service errors
    // ==========================================

    public function test_login_returns_401_if_credentials_invalid()
    {
        $this->authService
            ->shouldReceive('login')
            ->once()
            ->andThrow(new \Exception('Invalid credentials', 401));

        $response = $this->postJson('/api/v1/auth/login', $this->validPayload([
            'password' => 'mauvais-password',
        ]));

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Error occurred while logging in.',
                'data'    => null,
            ])
            ->assertJsonStructure([
                'errors' => ['exception'],
            ]);
    }

    public function test_login_returns_500_if_service_fails()
    {
        $this->authService
            ->shouldReceive('login')
            ->once()
            ->andThrow(new \Exception('Unexpected error', 500));

        $response = $this->postJson('/api/v1/auth/login', $this->validPayload());

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Error occurred while logging in.',
                'data'    => null,
            ]);
    }

    public function test_login_returns_service_error_code()
    {
        $this->authService
            ->shouldReceive('login')
            ->once()
            ->andThrow(new \Exception('Service unavailable', 503));

        $response = $this->postJson('/api/v1/auth/login', $this->validPayload());

        $response->assertStatus(503);
    }
}
