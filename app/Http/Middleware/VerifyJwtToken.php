<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class VerifyJwtToken
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->extractToken($request);

        if (!$token) {
            return $this->unauthorizedResponse('Missing Token.');
        }

        $payload = $this->verifyTokenWithAaaS($token);

        if (!$payload) {
            return $this->unauthorizedResponse('Invalid or expired token.');
        }

        $request->merge(['auth_user' => $payload]);

        return $next($request);
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->header('Authorization');

        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }

    private function verifyTokenWithAaaS(string $token): ?array
    {
        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'x-api-key' => env('X_API_KEY')
                ])
                ->post(config('services.aaas.url') . '/auth/verify');

            if ($response->successful()) {
                return $response->json('data');
            }

            return null;
        } catch (\Exception $e) {
            report($e);
            return null;
        }
    }

    private function unauthorizedResponse(string $message): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'errors'  => [],
        ], Response::HTTP_UNAUTHORIZED);
    }
}
