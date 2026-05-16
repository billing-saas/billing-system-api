<?php

namespace Tests\Traits;

use Illuminate\Http\Client\Response;
use Mockery;
use Mockery\MockInterface;

trait WithHttpResponses
{
    /**
     * Crée un mock de Response HTTP réussi
     */
    protected function makeSuccessResponse(array $data = []): MockInterface
    {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->andReturn(true);

        foreach ($data as $key => $value) {
            $response->shouldReceive('json')
                ->with($key)
                ->andReturn($value);
        }

        return $response;
    }

    /**
     * Crée un mock de Response HTTP échoué
     */
    protected function makeFailureResponse(
        string $message = 'Error',
        int $status = 400,
        array $extraData = []
    ): MockInterface {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->andReturn(false);
        $response->shouldReceive('status')->andReturn($status);
        $response->shouldReceive('json')
            ->with('error')
            ->andReturn(['message' => $message, ...$extraData]);

        return $response;
    }

    /**
     * Crée un mock de réponse de login avec token de rafraîchissement
     */
    protected function makeLoginResponse(
        string $userId = 'uuid-123',
        string $accessToken = 'fake-access-token',
        string $sessionId = 'fake-session-id',
        ?string $refreshToken = 'fake-refresh-token'
    ): MockInterface {
        $response = Mockery::mock(Response::class);
        $response->shouldReceive('successful')->andReturn(true);

        // Mock cookies
        $cookieJar = Mockery::mock();

        if ($refreshToken) {
            $cookie = Mockery::mock();
            $cookie->shouldReceive('getValue')->andReturn($refreshToken);
            $cookieJar->shouldReceive('getCookieByName')
                ->with('refreshToken')
                ->andReturn($cookie);
        } else {
            $cookieJar->shouldReceive('getCookieByName')
                ->with('refreshToken')
                ->andReturn(null);
        }

        $response->shouldReceive('cookies')->andReturn($cookieJar);

        // Mock JSON responses
        $response->shouldReceive('json')
            ->with('data.user.userId')
            ->andReturn($userId);

        $response->shouldReceive('json')
            ->with('data.user')
            ->andReturn(['userId' => $userId, 'email' => 'test@example.com']);

        $response->shouldReceive('json')
            ->with('data.accessToken')
            ->andReturn($accessToken);

        $response->shouldReceive('json')
            ->with('data.sessionId')
            ->andReturn($sessionId);

        return $response;
    }
}
