<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Http\Requests\UpdateUserProfileRequest;
use App\Http\Resources\UserProfileResource;
use App\Models\UserProfile;
use App\Services\UserProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserProfileController extends Controller
{
    public function __construct(
        private UserProfileService $userProfileService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['required', 'string', 'max:100'],
        ]);

        $profile = UserProfile::create([
            ...$validated,
            'user_id' => AuthHelper::id(),
        ]);

        return response()->json([
            'success' => true,
            'data'    => $profile,
            'message' => 'Profile created successfully.',
            'errors'  => [],
        ], 201);
    }

    public function show(): JsonResponse
    {
        $profile = $this->userProfileService->getProfile();

        return response()->json([
            'success' => true,
            'data'    => new UserProfileResource($profile),
            'message' => 'Profile retrieved successfully.',
            'errors'  => [],
        ]);
    }

    public function update(UpdateUserProfileRequest $request): JsonResponse
    {
        $profile = $this->userProfileService->updateProfile(
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'data'    => new UserProfileResource($profile),
            'message' => 'Profile updated successfully.',
            'errors'  => [],
        ]);
    }
}
