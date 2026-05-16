<?php

namespace Tests\Traits;

trait WithAuthUser
{
    // Données par défaut
    protected function getAuthUserData(array $overrides = []): array
    {
        return array_merge([
            'sub'   => 'user-uuid-123',
            'email' => 'test@example.com',
            'role'  => 'user',
        ], $overrides);
    }

    // Pour les Feature tests (avec requête HTTP)
    protected function callAsUser(
        string $method,
        string $uri,
        array $overrides = [],
        array $data = []
    ): \Illuminate\Testing\TestResponse {
        $userData = $this->getAuthUserData($overrides);

        // auth_user + données du body dans les mêmes paramètres
        $parameters = array_merge(['auth_user' => $userData], $data);

        return $this->withoutMiddleware()
            ->call($method, $uri, $parameters);
    }

    // Pour les Unit tests (sans requête HTTP)
    protected function setAuthUser(array $overrides = []): void
    {
        request()->merge([
            'auth_user' => $this->getAuthUserData($overrides)
        ]);
    }
}
