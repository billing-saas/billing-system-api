<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class AaaSClient
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('services.aaas.url');
        $this->apiKey  = config('services.aaas.api_key');
    }

    // ─────────────────────────────────────────
    // Instance HTTP préconfigurée
    // ─────────────────────────────────────────
    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->timeout(5)
            ->withHeaders([
                'x-api-key'    => $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ]);
    }

    // ─────────────────────────────────────────
    // Auth endpoints
    // ─────────────────────────────────────────
    public function register(string $email, string $password): Response
    {
        return $this->client()->post('/auth/register', [
            'email'    => $email,
            'password' => $password,
        ]);
    }

    public function login(string $email, string $password): Response
    {
        return $this->client()->post('/auth/login', [
            'email'    => $email,
            'password' => $password,
        ]);
    }

    public function verify(string $token): Response
    {
        return $this->client()
            ->withToken($token)
            ->post('/auth/verify');
    }

    public function logout(string $token): Response
    {
        return $this->client()
            ->withToken($token)
            ->post('/auth/logout');
    }

    public function refreshToken(string $refreshToken): Response
    {
        return $this->client()->post('/auth/refresh-token', [
            'refreshToken' => $refreshToken,
        ]);
    }

    public function grantAccess(string $userId): Response
    {
        return $this->client()->post('/auth/grant-access', [
            'userId' => $userId,
        ]);
    }
}
