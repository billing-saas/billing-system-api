<?php

namespace App\Repositories;

use App\Models\UserProfile;

class UserProfileRepository
{
    public function __construct() {}

    public function create(array $data)
    {
        return UserProfile::create($data);
    }

    public function findByUserId(string $userId)
    {
        // return UserProfile::where('user_id', $userId)->first();
        return UserProfile::where('user_id', $userId)->first();

    }
}
