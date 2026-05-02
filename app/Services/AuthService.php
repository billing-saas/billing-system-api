<?php

namespace App\Services;

use App\Models\UserProfile;
use Illuminate\Support\Facades\Http;

class AuthService
{
    public function __construct(
        private AaaSClient            $aaasClient,
        private UserProfileService       $userProfileService,
    ) {}

    function login(string $email, string $password)
    {
        $response = $this->aaasClient->login($email, $password);

        if (!$response->successful()) {
            throw new \Exception($response->json('error')['message'], $response->status());
        }

        // Récupérer le cookie refreshToken envoyé par l'AaaS
        $cookies     = $response->cookies();
        $refreshToken = $cookies->getCookieByName('refreshToken')?->getValue();

        $userProfile = $this->userProfileService->getUserProfile(
            $response->json('data.user.userId')
        );

        return [
            'user'        => $response->json('data.user'),
            'accessToken' => $response->json('data.accessToken'),
            'sessionId'   => $response->json('data.sessionId'),
            'refreshToken' => $refreshToken,
            'profile'     => $userProfile->toArray(),
        ];
    }

    function createUserProfile(array $data)
    {
        // authenticate
        $registerResponse = $this->aaasClient->register(
            $data['email'],
            $data['password']
        );

        if (!$registerResponse->successful()) {
            throw new \Exception($registerResponse->json('error')['message'], $registerResponse->status());
        }

        $accessResponse = $this->aaasClient->grantAccess(
            $registerResponse->json('userId')
        );

        if (!$accessResponse->successful()) {
            throw new \Exception($accessResponse->json('error')['message'], $accessResponse->status());
        }

        return $this->userProfileService->createUser([
            'user_id' => $registerResponse->json('userId'),
            'first_name' => $data['firstName'],
            'last_name' => $data['lastName'],
        ]);
    }
}
