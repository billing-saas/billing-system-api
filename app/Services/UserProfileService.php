<?php

namespace App\Services;

use App\Helpers\AuthHelper;
use App\Repositories\UserProfileRepository;

class UserProfileService
{
    public function __construct(
        private UserProfileRepository $repository,
    ) {}

    public function getUserProfile(string $userId)
    {
        return $this->repository->findByUserId($userId);
    }

    public function createUser(array $data)
    {
        return $this->repository->create([
            ...$data,
            'id' => AuthHelper::id(),
        ]);
    }
}
