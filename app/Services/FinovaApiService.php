<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\JsonResponse;

class FinovaApiService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(
            config('services.finova.base_url', 'https://core.finova.ng/api/v1'),
            '/'
        );
    }

    /**
     * Forward a GET request to Finova.
     */
    public function get(
        string $path,
        ?string $token,
        array $query = []
    ): JsonResponse {
        return $this->send('GET', $path, $token, $query);
    }

    /**
     * Forward a POST request to Finova.
     */
    public function post(
        string $path,
        ?string $token,
        array $payload = []
    ): JsonResponse {
        return $this->send('POST', $path, $token, $payload);
    }

    /**
     * Send the request and relay Finova's response.
     */
    private function send(
        string $method,
        string $path,
        ?string $token,
        array $data = []
    ): JsonResponse {
        if (empty($token)) {
            return response()->json([
                'status' => false,
                'message' => 'Finova authentication token is missing.',
                'data' => [],
            ], 401);
        }

        $url = $this->baseUrl . '/' . ltrim($path, '/');

        try {
            $http = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout(15)
                ->timeout(90);

            /** @var Response $response */
            $response = match (strtoupper($method)) {
                'GET' => $http->get($url, $data),
                'POST' => $http->post($url, $data),
                'PUT' => $http->put($url, $data),
                default => throw new \InvalidArgumentException(
                    'Unsupported Finova HTTP method.'
                ),
            };

            $body = $response->json();

            if (!is_array($body)) {
                $body = [
                    'status' => $response->successful(),
                    'message' => $response->successful()
                        ? 'Request completed.'
                        : 'Finova request failed.',
                    'data' => [],
                ];
            }

            return response()->json($body, $response->status());

        } catch (ConnectionException $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Unable to connect to Finova Core Banking. Please try again later.',
                'data' => [],
            ], 503);
        }
    }

    public function put(
    string $path,
    ?string $token,
    array $payload = []
): JsonResponse {
    return $this->send('PUT', $path, $token, $payload);
}
}
