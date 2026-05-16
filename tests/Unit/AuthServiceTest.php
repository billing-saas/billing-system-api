<?php

namespace Tests\Unit\Services;

use App\Models\UserProfile;
use App\Services\AaaSClient;
use App\Services\AuthService;
use App\Services\UserProfileService;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\WithMockeryCleanup;
use Tests\Traits\WithHttpResponses;
use Tests\Traits\WithFakeModels;

class AuthServiceTest extends TestCase
{
    use WithMockeryCleanup, WithHttpResponses, WithFakeModels;

    private AuthService $service;
    private MockInterface $aaasClient;
    private MockInterface $userProfileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->aaasClient         = Mockery::mock(AaaSClient::class);
        $this->userProfileService = Mockery::mock(UserProfileService::class);
        $this->service            = new AuthService($this->aaasClient, $this->userProfileService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ✅ Returns a real UserProfile instance — for attribute access only
    private function makeUserProfile(array $attributes = []): UserProfile
    {
        $profile = new UserProfile();

        $defaults = [
            'id'         => 1,
            'user_id'    => 'user-uuid-123',
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $profile->$key = $value;
        }

        return $profile;
    }

    // ✅ Returns a Mockery mock — for mocking methods like toArray()
    private function makeUserProfileMock(array $attributes = []): MockInterface
    {
        $defaults = [
            'id'         => 1,
            'user_id'    => 'user-uuid-123',
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
        ];

        $merged = array_merge($defaults, $attributes);

        $mock = Mockery::mock(UserProfile::class);

        // ✅ Mock setAttribute() to allow assignment
        $mock->shouldReceive('setAttribute')
            ->withAnyArgs()
            ->andReturnUsing(function ($key, $value) use ($mock) {
                // Store the value directly on the mock
                $mock->$key = $value;
            });

        // ✅ Mock getAttribute() to allow reads
        $mock->shouldReceive('getAttribute')
            ->withAnyArgs()
            ->andReturnUsing(function ($key) use (&$merged) {
                return $merged[$key] ?? null;
            });

        foreach ($merged as $key => $value) {
            $mock->$key = $value;
        }

        return $mock;
    }

    // ==========================================
    // createUserProfile()
    // ==========================================

    public function test_create_user_profile_successfully()
    {
        $data = [
            'email'     => 'test@example.com',
            'password'  => 'secret123',
            'firstName' => 'John',
            'lastName'  => 'Doe',
        ];

        $registerResponse = $this->makeSuccessResponse(['userId' => 'uuid-123']);
        $accessResponse   = $this->makeSuccessResponse([]);

        $this->aaasClient
            ->shouldReceive('register')
            ->once()
            ->with('test@example.com', 'secret123')
            ->andReturn($registerResponse);

        $this->aaasClient
            ->shouldReceive('grantAccess')
            ->once()
            ->with('uuid-123')
            ->andReturn($accessResponse);

        $profile = $this->makeUserProfileMock(['user_id' => 'uuid-123']);
        $profile->shouldReceive('toArray')
            ->andReturn(['id' => 1, 'user_id' => 'uuid-123']);

        $this->userProfileService
            ->shouldReceive('createUser')
            ->once()
            ->with([
                'user_id'    => 'uuid-123',
                'first_name' => 'John',
                'last_name'  => 'Doe',
            ])
            ->andReturn($profile->toArray());

        $result = $this->service->createUserProfile($data);

        $this->assertEquals('uuid-123', $result['user_id']);
    }

    public function test_create_user_profile_fails_if_registration_fails()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Registration failed');
        $this->expectExceptionCode(400);

        $registerResponse = $this->makeFailureResponse('Registration failed', 400);

        $this->aaasClient
            ->shouldReceive('register')
            ->once()
            ->andReturn($registerResponse);

        $this->aaasClient->shouldNotReceive('grantAccess');

        $this->service->createUserProfile([
            'email'     => 'fail@example.com',
            'password'  => 'password',
            'firstName' => 'John',
            'lastName'  => 'Doe',
        ]);
    }

    public function test_create_user_profile_fails_if_access_grant_fails()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access grant failed');
        $this->expectExceptionCode(400);

        $registerResponse = $this->makeSuccessResponse(['userId' => 'uuid-123']);
        $accessResponse   = $this->makeFailureResponse('Access grant failed', 400);

        $this->aaasClient
            ->shouldReceive('register')
            ->once()
            ->andReturn($registerResponse);

        $this->aaasClient
            ->shouldReceive('grantAccess')
            ->once()
            ->andReturn($accessResponse);

        $this->userProfileService->shouldNotReceive('createUser');

        $this->service->createUserProfile([
            'email'     => 'fail@example.com',
            'password'  => 'password',
            'firstName' => 'John',
            'lastName'  => 'Doe',
        ]);
    }

    // ==========================================
    // login()
    // ==========================================

    public function test_login_successfully()
    {
        $loginResponse = $this->makeLoginResponse(
            userId: 'uuid-123',
            accessToken: 'fake-access-token',
            sessionId: 'fake-session-id',
            refreshToken: 'fake-refresh-token'
        );

        $this->aaasClient
            ->shouldReceive('login')
            ->once()
            ->with('test@example.com', 'secret123')
            ->andReturn($loginResponse);

        // ✅ makeUserProfileMock() car on appelle toArray()
        $profile = $this->makeUserProfileMock(['user_id' => 'uuid-123']);
        $profile->shouldReceive('toArray')
            ->andReturn([
                'id'         => 1,
                'user_id'    => 'uuid-123',
                'first_name' => 'John',
                'last_name'  => 'Doe',
            ]);

        $this->userProfileService
            ->shouldReceive('getUserProfile')
            ->once()
            ->with('uuid-123')
            ->andReturn($profile);

        $result = $this->service->login('test@example.com', 'secret123');

        $this->assertEquals('uuid-123', $result['user']['userId']);
        $this->assertEquals('fake-access-token', $result['accessToken']);
        $this->assertEquals('fake-session-id', $result['sessionId']);
        $this->assertEquals('fake-refresh-token', $result['refreshToken']);
        $this->assertEquals('John', $result['profile']['first_name']);
    }

    public function test_login_fails_when_credentials_are_invalid()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');
        $this->expectExceptionCode(401);

        $loginResponse = $this->makeFailureResponse('Invalid credentials', 401);

        $this->aaasClient
            ->shouldReceive('login')
            ->once()
            ->with('test@example.com', 'wrongpassword')
            ->andReturn($loginResponse);

        $this->userProfileService->shouldNotReceive('getUserProfile');

        $this->service->login('test@example.com', 'wrongpassword');
    }

    public function test_login_returns_null_refresh_token_when_cookie_absent()
    {
        $loginResponse = $this->makeLoginResponse(
            userId: 'uuid-123',
            accessToken: 'fake-access-token',
            sessionId: 'fake-session-id',
            refreshToken: null
        );

        $this->aaasClient
            ->shouldReceive('login')
            ->once()
            ->andReturn($loginResponse);

        // ✅ makeUserProfileMock() car on appelle toArray()
        $profile = $this->makeUserProfileMock(['user_id' => 'uuid-123']);
        $profile->shouldReceive('toArray')
            ->andReturn(['id' => 1, 'user_id' => 'uuid-123']);

        $this->userProfileService
            ->shouldReceive('getUserProfile')
            ->once()
            ->andReturn($profile);

        $result = $this->service->login('test@example.com', 'secret123');

        $this->assertNull($result['refreshToken']);
    }

    public function test_login_fails_when_user_profile_not_found()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Profile not found');
        $this->expectExceptionCode(404);

        $loginResponse = $this->makeLoginResponse();

        $this->aaasClient
            ->shouldReceive('login')
            ->once()
            ->andReturn($loginResponse);

        $this->userProfileService
            ->shouldReceive('getUserProfile')
            ->once()
            ->with('uuid-123')
            ->andThrow(new \Exception('Profile not found', 404));

        $this->service->login('test@example.com', 'secret123');
    }

    public function test_login_fails_when_aaas_server_is_unavailable()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service unavailable');
        $this->expectExceptionCode(500);

        $loginResponse = $this->makeFailureResponse('Service unavailable', 500);

        $this->aaasClient
            ->shouldReceive('login')
            ->once()
            ->andReturn($loginResponse);

        $this->userProfileService->shouldNotReceive('getUserProfile');

        $this->service->login('test@example.com', 'secret123');
    }
}
