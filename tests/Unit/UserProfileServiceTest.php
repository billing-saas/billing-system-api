<?php

namespace Tests\Unit;

use App\Services\AuthService;
use PHPUnit\Framework\TestCase;
use Mockery;
use Mockery\MockInterface;
use Illuminate\Http\Client\Response;

class UserProfileServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** REGISTER TESTS */

    public function test_create_user_profile_successfully()
    {
        // Data preparation
        $data = [
            'email' => 'test@example.com',
            'password' => 'secret123',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ];

        // Mock AaaS client
        $aaasClient = Mockery::mock('App\Services\AaasClient', function (MockInterface $mock) {
            // Simulate register()
            $registerResponse = Mockery::mock(Response::class);
            $registerResponse->shouldReceive('successful')->andReturn(true);
            $registerResponse->shouldReceive('json')->with('userId')->andReturn('uuid-123');

            $mock->shouldReceive('register')
                ->once()
                ->with('test@example.com', 'secret123')
                ->andReturn($registerResponse);

            // Simulate grantAccess()
            $accessResponse = Mockery::mock(Response::class);
            $accessResponse->shouldReceive('successful')->andReturn(true);

            $mock->shouldReceive('grantAccess')
                ->once()
                ->with('uuid-123')
                ->andReturn($accessResponse);
        });

        // Mock profile service
        $userProfileService = Mockery::mock('App\Services\UserProfileService', function (MockInterface $mock) {
            $mock->shouldReceive('createUser')
                ->once()
                ->with([
                    'user_id' => 'uuid-123',
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ])
                ->andReturn(['id' => 1, 'user_id' => 'uuid-123']);
        });

        // Execute test
        $service = new AuthService($aaasClient, $userProfileService);
        $result = $service->createUserProfile($data);

        // Assertions
        $this->assertEquals('uuid-123', $result['user_id']);
    }

    public function test_it_throws_exception_if_registration_fails(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Registration failed');

        $aaasClient = Mockery::mock('App\Services\AaasClient', function (MockInterface $mock) {

            $registerResponse = Mockery::mock(Response::class);
            $registerResponse->shouldReceive('successful')->andReturn(false);
            $registerResponse->shouldReceive('json')->with('error')->andReturn(['message' => 'Registration failed']);
            $registerResponse->shouldReceive('status')->andReturn(400);

            $mock->shouldReceive('register')->andReturn($registerResponse);
        });

        $service = new AuthService($aaasClient, Mockery::mock('App\Services\UserProfileService'));

        $service->createUserProfile([
            'email' => 'fail@example.com',
            'password' => 'password',
            'firstName' => 'John',
            'lastName' => 'Doe'
        ]);
    }

    public function test_it_throws_exception_if_access_grant_fails(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Access grant failed');

        $aaasClient = Mockery::mock('App\Services\AaasClient', function (MockInterface $mock) {

            $registerResponse = Mockery::mock(Response::class);
            $registerResponse->shouldReceive('successful')->andReturn(true);
            $registerResponse->shouldReceive('json')->with('userId')->andReturn('uuid-123');

            $accessResponse = Mockery::mock(Response::class);
            $accessResponse->shouldReceive('successful')->andReturn(false);
            $accessResponse->shouldReceive('json')->with('error')->andReturn(['message' => 'Access grant failed']);
            $accessResponse->shouldReceive('status')->andReturn(400);

            $mock->shouldReceive('register')->andReturn($registerResponse);
            $mock->shouldReceive('grantAccess')->andReturn($accessResponse);
        });

        $service = new AuthService($aaasClient, Mockery::mock('App\Services\UserProfileService'));

        $service->createUserProfile([
            'email' => 'fail@example.com',
            'password' => 'password',
            'firstName' => 'John',
            'lastName' => 'Doe'
        ]);
    }

    /** LOGIN TESTS */

    public function test_login_successfully()
    {
        $email    = 'test@example.com';
        $password = 'secret123';

        // 1. Mock refreshToken cookie
        $cookieMock = Mockery::mock();
        $cookieMock->shouldReceive('getValue')
            ->once()
            ->andReturn('fake-refresh-token-123');

        // 2. Mock CookieJar
        $cookieJarMock = Mockery::mock();
        $cookieJarMock->shouldReceive('getCookieByName')
            ->with('refreshToken')
            ->once()
            ->andReturn($cookieMock);

        // 3. Mock HTTP response
        $loginMock = Mockery::mock(\Illuminate\Http\Client\Response::class);

        $loginMock->shouldReceive('successful')
            ->once()
            ->andReturn(true);

        $loginMock->shouldReceive('cookies')
            ->once()
            ->andReturn($cookieJarMock);

        // Each json('xxx') call must be mocked separately
        $loginMock->shouldReceive('json')
            ->with('data.user.userId')
            ->andReturn('uuid-123');

        $loginMock->shouldReceive('json')
            ->with('data.user')
            ->andReturn([
                'userId' => 'uuid-123',
                'email'  => 'test@example.com',
            ]);

        $loginMock->shouldReceive('json')
            ->with('data.accessToken')
            ->andReturn('fake-access-token-123');

        $loginMock->shouldReceive('json')
            ->with('data.sessionId')
            ->andReturn('fake-session-id-123');

        // 4. Mock AaasClient
        $aaasClient = Mockery::mock(\App\Services\AaasClient::class);
        $aaasClient->shouldReceive('login')
            ->once()
            ->with($email, $password)
            ->andReturn($loginMock);

        // 5. Mock UserProfileService
        $profileMock = Mockery::mock(\App\Models\UserProfile::class);
        $profileMock->shouldReceive('toArray')
            ->once()
            ->andReturn([
                'id'         => 1,
                'user_id'    => 'uuid-123',
                'first_name' => 'John',
                'last_name'  => 'Doe',
            ]);

        $userProfileService = Mockery::mock(\App\Services\UserProfileService::class);
        $userProfileService->shouldReceive('getUserProfile')
            ->once()
            ->with('uuid-123')
            ->andReturn($profileMock);

        // 6. Execute
        $service = new \App\Services\AuthService($aaasClient, $userProfileService);
        $result  = $service->login($email, $password);

        // 7. Assertions
        $this->assertEquals('uuid-123', $result['user']['userId']);
        $this->assertEquals('fake-access-token-123', $result['accessToken']);
        $this->assertEquals('fake-session-id-123', $result['sessionId']);
        $this->assertEquals('fake-refresh-token-123', $result['refreshToken']);
        $this->assertEquals('John', $result['profile']['first_name']);
    }

    // 1. Login failure (invalid password)
    public function test_login_fails_when_credentials_are_invalid()
    {
        $responseMock = Mockery::mock(\Illuminate\Http\Client\Response::class);
        $responseMock->shouldReceive('successful')
            ->once()
            ->andReturn(false);
        $responseMock->shouldReceive('json')
            ->with('error')
            ->andReturn(['message' => 'Invalid credentials']);
        $responseMock->shouldReceive('status')
            ->once()
            ->andReturn(401);

        $aaasClient = Mockery::mock(\App\Services\AaasClient::class);
        $aaasClient->shouldReceive('login')
            ->once()
            ->with('test@example.com', 'wrongpassword')
            ->andReturn($responseMock);

        $userProfileService = Mockery::mock(\App\Services\UserProfileService::class);
        // getUserProfile must NEVER be called
        $userProfileService->shouldNotReceive('getUserProfile');

        $service = new \App\Services\AuthService($aaasClient, $userProfileService);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');
        $this->expectExceptionCode(401);

        $service->login('test@example.com', 'wrongpassword');
    }

    // 2. refreshToken is missing from cookies
    public function test_login_returns_null_refresh_token_when_cookie_absent()
    {
        $cookieJarMock = Mockery::mock();
        $cookieJarMock->shouldReceive('getCookieByName')
            ->with('refreshToken')
            ->once()
            ->andReturn(null); // no cookie

        $loginMock = Mockery::mock(\Illuminate\Http\Client\Response::class);
        $loginMock->shouldReceive('successful')->once()->andReturn(true);
        $loginMock->shouldReceive('cookies')->once()->andReturn($cookieJarMock);
        $loginMock->shouldReceive('json')->with('data.user.userId')->andReturn('uuid-123');
        $loginMock->shouldReceive('json')->with('data.user')->andReturn(['userId' => 'uuid-123', 'email' => 'test@example.com']);
        $loginMock->shouldReceive('json')->with('data.accessToken')->andReturn('fake-access-token-123');
        $loginMock->shouldReceive('json')->with('data.sessionId')->andReturn('fake-session-id-123');

        $aaasClient = Mockery::mock(\App\Services\AaasClient::class);
        $aaasClient->shouldReceive('login')->once()->andReturn($loginMock);

        $profileMock = Mockery::mock(\App\Models\UserProfile::class);
        $profileMock->shouldReceive('toArray')->once()->andReturn(['id' => 1, 'user_id' => 'uuid-123']);

        $userProfileService = Mockery::mock(\App\Services\UserProfileService::class);
        $userProfileService->shouldReceive('getUserProfile')->once()->with('uuid-123')->andReturn($profileMock);

        $service = new \App\Services\AuthService($aaasClient, $userProfileService);
        $result  = $service->login('test@example.com', 'secret123');

        // refreshToken must be null because cookie is missing
        $this->assertNull($result['refreshToken']);
    }

    // 3. User profile not found
    public function test_login_fails_when_user_profile_not_found()
    {
        $cookieMock = Mockery::mock();
        $cookieMock->shouldReceive('getValue')->andReturn('fake-refresh-token');

        $cookieJarMock = Mockery::mock();
        $cookieJarMock->shouldReceive('getCookieByName')->with('refreshToken')->andReturn($cookieMock);

        $loginMock = Mockery::mock(\Illuminate\Http\Client\Response::class);
        $loginMock->shouldReceive('successful')->once()->andReturn(true);
        $loginMock->shouldReceive('cookies')->once()->andReturn($cookieJarMock);
        $loginMock->shouldReceive('json')->with('data.user.userId')->andReturn('uuid-123');

        $aaasClient = Mockery::mock(\App\Services\AaasClient::class);
        $aaasClient->shouldReceive('login')->once()->andReturn($loginMock);

        $userProfileService = Mockery::mock(\App\Services\UserProfileService::class);
        $userProfileService->shouldReceive('getUserProfile')
            ->once()
            ->with('uuid-123')
            ->andThrow(new \Exception('Profile not found', 404));

        $service = new \App\Services\AuthService($aaasClient, $userProfileService);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Profile not found');
        $this->expectExceptionCode(404);

        $service->login('test@example.com', 'secret123');
    }

    // 4. AaaS server unavailable (500 error)
    public function test_login_fails_when_aaas_server_is_unavailable()
    {
        $responseMock = Mockery::mock(\Illuminate\Http\Client\Response::class);
        $responseMock->shouldReceive('successful')->once()->andReturn(false);
        $responseMock->shouldReceive('json')
            ->with('error')
            ->andReturn(['message' => 'Service unavailable']);
        $responseMock->shouldReceive('status')->once()->andReturn(500);

        $aaasClient = Mockery::mock(\App\Services\AaasClient::class);
        $aaasClient->shouldReceive('login')->once()->andReturn($responseMock);

        $userProfileService = Mockery::mock(\App\Services\UserProfileService::class);
        $userProfileService->shouldNotReceive('getUserProfile');

        $service = new \App\Services\AuthService($aaasClient, $userProfileService);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Service unavailable');
        $this->expectExceptionCode(500);

        $service->login('test@example.com', 'secret123');
    }
}
