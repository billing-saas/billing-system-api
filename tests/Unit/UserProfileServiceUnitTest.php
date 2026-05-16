<?php

namespace Tests\Unit;

use App\Models\UserProfile;
use App\Repositories\UserProfileRepository;
use App\Services\UserProfileService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Traits\WithMockeryCleanup;
use Tests\Traits\WithFakeModels;

class UserProfileServiceUnitTest extends TestCase
{
    use WithMockeryCleanup, WithFakeModels;

    private UserProfileService $service;
    private MockInterface $userProfileRepository;

    protected function setUp(): void
    {
        parent::setUp();

        // Set authenticated user
        request()->merge([
            'auth_user' => [
                'sub'   => 'user-uuid-123',
                'email' => 'test@example.com',
            ]
        ]);

        $this->userProfileRepository = Mockery::mock(UserProfileRepository::class);
        $this->service               = new UserProfileService($this->userProfileRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==========================================
    // getProfile()
    // ==========================================

    public function test_get_profile_returns_profile()
    {
        $profile = $this->makeUserProfile([
            'user_id'    => 'user-uuid-123',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        $this->userProfileRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with('user-uuid-123')
            ->andReturn($profile);

        // Execute
        $result = $this->service->getProfile();

        // Assertions
        $this->assertInstanceOf(UserProfile::class, $result);
        $this->assertEquals('John', $result->first_name);
    }

    public function test_get_profile_throws_exception_if_not_found()
    {
        $this->userProfileRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with('user-uuid-123')
            ->andReturn(null);

        $this->expectException(HttpResponseException::class);

        $this->service->getProfile();
    }

    public function test_get_profile_uses_auth_user_id()
    {
        // Override authenticated user
        request()->merge([
            'auth_user' => [
                'sub' => 'autre-uuid-456',
            ]
        ]);

        $profile = $this->makeUserProfile(['user_id' => 'autre-uuid-456']);

        $this->userProfileRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with('autre-uuid-456')
            ->andReturn($profile);

        // Execute
        $result = $this->service->getProfile();

        // Assertions
        $this->assertNotNull($result);
    }

    // ==========================================
    // getUserProfile()
    // ==========================================

    public function test_get_user_profile_by_user_id()
    {
        $userId  = 'specific-uuid-789';
        $profile = $this->makeUserProfile(['user_id' => $userId]);

        $this->userProfileRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId)
            ->andReturn($profile);

        // Execute
        $result = $this->service->getUserProfile($userId);

        // Assertions
        $this->assertInstanceOf(UserProfile::class, $result);
        $this->assertEquals($userId, $result->user_id);
    }

    public function test_get_user_profile_returns_null_if_not_found()
    {
        $this->userProfileRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with('nonexistent-uuid')
            ->andReturn(null);

        // Execute
        $result = $this->service->getUserProfile('nonexistent-uuid');

        // Assertions
        $this->assertNull($result);
    }

    // ==========================================
    // createUser()
    // ==========================================

    public function test_create_user_injects_user_id_from_auth()
    {
        $data = [
            'first_name' => 'Jane',
            'last_name'  => 'Smith',
        ];

        $profile = $this->makeUserProfile([
            'user_id'    => 'user-uuid-123',
            'first_name' => 'Jane',
            'last_name'  => 'Smith',
        ]);

        $this->userProfileRepository
            ->shouldReceive('create')
            ->once()
            ->with([
                'id'         => 'user-uuid-123', // injected from AuthHelper::id()
                'first_name' => 'Jane',
                'last_name'  => 'Smith',
            ])
            ->andReturn($profile);

        // Execute
        $result = $this->service->createUser($data);

        // Assertions
        $this->assertInstanceOf(UserProfile::class, $result);
        $this->assertEquals('Jane', $result->first_name);
    }

    public function test_create_user_with_different_auth_user()
    {
        // Override authenticated user
        request()->merge([
            'auth_user' => [
                'sub' => 'autre-uuid-456',
            ]
        ]);

        $this->service = new UserProfileService($this->userProfileRepository);

        $data = [
            'first_name' => 'Alice',
            'last_name'  => 'Johnson',
        ];

        $profile = $this->makeUserProfile([
            'user_id'    => 'autre-uuid-456',
            'first_name' => 'Alice',
            'last_name'  => 'Johnson',
        ]);

        $this->userProfileRepository
            ->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $data) {
                return $data['id'] === 'autre-uuid-456' &&
                    $data['first_name'] === 'Alice' &&
                    $data['last_name'] === 'Johnson';
            }))
            ->andReturn($profile);

        // Execute
        $result = $this->service->createUser($data);

        // Assertions
        $this->assertNotNull($result);
    }

    // ==========================================
    // updateProfile()
    // ==========================================

    public function test_update_profile_updates_and_returns_profile()
    {
        $originalProfile = $this->makeUserProfile([
            'user_id'    => 'user-uuid-123',
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        $updatedProfile = $this->makeUserProfile([
            'user_id'    => 'user-uuid-123',
            'first_name' => 'Johnny',
            'last_name'  => 'Doe',
        ]);

        $this->userProfileRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with('user-uuid-123')
            ->andReturn($originalProfile);

        $this->userProfileRepository
            ->shouldReceive('update')
            ->once()
            ->with($originalProfile, ['first_name' => 'Johnny'])
            ->andReturn($updatedProfile);

        // Execute
        $result = $this->service->updateProfile(['first_name' => 'Johnny']);

        // Assertions
        $this->assertEquals('Johnny', $result->first_name);
    }

    public function test_update_profile_throws_exception_if_profile_not_found()
    {
        $this->userProfileRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with('user-uuid-123')
            ->andReturn(null);

        // update must never be called
        $this->userProfileRepository->shouldNotReceive('update');

        $this->expectException(HttpResponseException::class);

        $this->service->updateProfile(['first_name' => 'Test']);
    }

    public function test_update_profile_passes_all_data_to_repository()
    {
        $profile = $this->makeUserProfile();

        $this->userProfileRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->andReturn($profile);

        $updateData = [
            'first_name' => 'Updated First',
            'last_name'  => 'Updated Last',
        ];

        $this->userProfileRepository
            ->shouldReceive('update')
            ->once()
            ->with($profile, $updateData)
            ->andReturn($profile);

        // Execute
        $this->service->updateProfile($updateData);

        // Assertions handled by Mockery
        $this->assertTrue(true);
    }

    public function test_update_profile_can_update_partial_data()
    {
        $profile = $this->makeUserProfile([
            'first_name' => 'John',
            'last_name'  => 'Doe',
        ]);

        $this->userProfileRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->andReturn($profile);

        // Partial update
        $partialData = ['first_name' => 'Jane'];

        $this->userProfileRepository
            ->shouldReceive('update')
            ->once()
            ->with($profile, $partialData)
            ->andReturn($profile);

        // Execute
        $this->service->updateProfile($partialData);

        $this->assertTrue(true);
    }

    public function test_get_profile_returns_correct_response_code()
    {
        $this->userProfileRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->andReturn(null);

        try {
            $this->service->getProfile();
            $this->fail('HttpResponseException expected but not thrown');
        } catch (HttpResponseException $e) {
            // ✅ getResponse() instead of $e->response
            $this->assertEquals(Response::HTTP_NOT_FOUND, $e->getResponse()->status());
        }
    }
}
