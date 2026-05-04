<?php

namespace App\Services;

use App\Helpers\AuthHelper;
use App\Models\UserProfile;
use App\Repositories\UserProfileRepository;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

class UserProfileService
{
    public function __construct(
        private UserProfileRepository $userProfileRepository,
    ) {}

    public function getProfile(): UserProfile
    {
        $profile = $this->userProfileRepository->findByUserId(
            AuthHelper::id()
        );

        if (!$profile) {
            throw new HttpResponseException(
                response()->json([
                    'success' => false,
                    'message' => 'Profile not found.',
                    'data'    => null,
                    'errors'  => [],
                ], Response::HTTP_NOT_FOUND)
            );
        }

        return $profile;
    }

    public function getUserProfile(string $userId)
    {
        return $this->userProfileRepository->findByUserId($userId);
    }

    public function createUser(array $data)
    {
        return $this->userProfileRepository ->create([
            ...$data,
            'id' => AuthHelper::id(),
        ]);
    }

    public function updateProfile(array $data): UserProfile
    {
        $profile = $this->getProfile();
        return $this->userProfileRepository->update($profile, $data);
    }
}
