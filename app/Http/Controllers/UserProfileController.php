<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserProfileController extends Controller
{
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
            'message' => 'Profil créé avec succès.',
            'errors'  => [],
        ], 201);
    }
}
