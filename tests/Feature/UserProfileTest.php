<?php

namespace Tests\Feature;

use App\Models\UserProfile;
use App\Services\UserProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\HttpResponseException;
use Mockery;
use Tests\TestCase;
use Tests\Traits\WithAuthUser;

class UserProfileTest extends TestCase
{
    use WithAuthUser, RefreshDatabase;

    private UserProfileService $userProfileService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userProfileService = Mockery::mock(UserProfileService::class);
        $this->app->instance(UserProfileService::class, $this->userProfileService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockExistsRule(): void
    {
        $presenceVerifier = Mockery::mock(\Illuminate\Validation\DatabasePresenceVerifierInterface::class);

        // Always return 1 (exists) for any validation check
        $presenceVerifier->shouldReceive('getCount')->andReturn(1);
        $presenceVerifier->shouldReceive('getMultiCount')->andReturn(1);
        $presenceVerifier->shouldReceive('setConnection')->andReturnNull();

        $this->app->make('validator')->setPresenceVerifier($presenceVerifier);
    }

    // ==========================================
    // Helpers
    // ==========================================

    private function makeUserProfile(array $attributes = []): UserProfile
    {
        $profile = new UserProfile();

        $defaults = [
            'id'           => 1,
            'user_id'      => 'user-uuid-123',
            'first_name'   => 'John',
            'last_name'    => 'Doe',
            'email'        => 'john@example.com',
            'phone'        => '+1234567890',
            'company_name' => 'Acme Corp',
            'tax_number'   => 'FR12345678901',
            'address'      => '123 Main Street',
            'city'         => 'Paris',
            'postal_code'  => '75001',
            'country'      => 'FR',
            'currency'     => 'EUR',
            'logo_path'    => null,
            'created_at'   => now(),
            'updated_at'   => now(),
        ];

        foreach (array_merge($defaults, $attributes) as $key => $value) {
            $profile->$key = $value;
        }

        return $profile;
    }

    private function notFoundException(): HttpResponseException
    {
        return new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Profile not found.',
                'data'    => null,
                'errors'  => [],
            ], 404)
        );
    }

    // ==========================================
    // POST /api/v1/user-profiles — store()
    // ==========================================

    public function test_store_creates_profile_successfully()
    {
        $response = $this->callAsUser('POST', '/api/v1/user-profiles', [], [
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Profile created successfully.',
            ]);
    }

    public function test_store_fails_when_first_name_missing()
    {
        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->postJson('/api/v1/user-profiles', [
                'last_name' => 'Doe',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name']);
    }

    public function test_store_fails_when_last_name_missing()
    {
        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->postJson('/api/v1/user-profiles', [
                'first_name' => 'John',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['last_name']);
    }

    public function test_store_fails_when_payload_empty()
    {
        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->postJson('/api/v1/user-profiles', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    public function test_store_returns_401_when_unauthenticated()
    {
        $response = $this->postJson('/api/v1/user-profiles', [
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        $response->assertStatus(401);
    }

    // ==========================================
    // GET /api/v1/profile — show()
    // ==========================================

    public function test_show_returns_profile()
    {
        $profile = $this->makeUserProfile();

        $this->userProfileService
            ->shouldReceive('getProfile')
            ->once()
            ->andReturn($profile);

        $response = $this->callAsUser('GET', '/api/v1/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile retrieved successfully.',
                'errors'  => [],
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'errors',
                'data' => [
                    'id',
                    'user_id',
                    'first_name',
                    'last_name',
                    'email',
                    'phone',
                    'company_name',
                    'city',
                    'country',
                    'currency',
                ],
            ]);
    }

    public function test_show_returns_404_if_profile_not_found()
    {
        $this->userProfileService
            ->shouldReceive('getProfile')
            ->once()
            ->andThrow($this->notFoundException());

        $response = $this->callAsUser('GET', '/api/v1/profile');

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Profile not found.',
            ]);
    }

    public function test_show_returns_401_when_unauthenticated()
    {
        $response = $this->getJson('/api/v1/profile');

        $response->assertStatus(401);
    }

    // ==========================================
    // PUT /api/v1/profile — update()
    // ==========================================

    public function test_update_updates_profile()
    {
        $profile = $this->makeUserProfile([
            'first_name' => 'Jane',
            'city'       => 'Lyon',
        ]);

        $this->userProfileService
            ->shouldReceive('updateProfile')
            ->once()
            ->andReturn($profile);

        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->putJson('/api/v1/profile', [
                'first_name' => 'Jane',
                'city'       => 'Lyon',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profile updated successfully.',
                'errors'  => [],
            ]);
    }

    public function test_update_returns_404_if_profile_not_found()
    {
        $this->userProfileService
            ->shouldReceive('updateProfile')
            ->once()
            ->andThrow($this->notFoundException());

        $this->setAuthUser();

        $response = $this->withoutMiddleware()
            ->putJson('/api/v1/profile', [
                'first_name' => 'Jane',
            ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'Profile not found.',
            ]);
    }

    public function test_update_returns_401_when_unauthenticated()
    {
        $response = $this->putJson('/api/v1/profile', [
            'first_name' => 'Jane',
        ]);

        $response->assertStatus(401);
    }
}
