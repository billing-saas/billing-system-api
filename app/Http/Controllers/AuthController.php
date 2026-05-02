<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\LoginResource;
use App\Http\Resources\UserProfileResource;
use App\Services\AuthService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $data = $this->authService->login(
                $request->input('email'),
                $request->input('password')
            );

            return response()->json([
                'success' => true,
                'data'    => new LoginResource($data),
                'message' => 'Login successful.',
                'errors'  => [],
            ], Response::HTTP_OK);
        } catch (Exception $th) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Error occurred while logging in.',
                'errors'  => [
                    'exception' => $th->getMessage()
                ],
            ], $th->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $userProfile = $this->authService->createUserProfile(
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'data'    => new UserProfileResource($userProfile),
                'message' => 'Account successfully created.',
                'errors'  => [],
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'data'    => null,
                'message' => 'Error occurred while creating the account.',
                'errors'  => [
                    'exception' => $e->getMessage()
                ],
            ], $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
