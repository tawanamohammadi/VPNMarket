<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class RemnawaveService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $nodeHostname;
    protected ?string $accessToken = null;

    public function __construct(string $baseUrl, string $username, string $password, string $nodeHostname)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->nodeHostname = rtrim($nodeHostname, '/');
    }

    public function login(): bool
    {
        try {
            $response = Http::post($this->baseUrl . '/api/auth/login', [
                'username' => $this->username,
                'password' => $this->password,
            ]);

            if ($response->successful() && isset($response->json('response.accessToken'))) {
                $this->accessToken = $response->json('response.accessToken');
                return true;
            }
            return false;
        } catch (\Exception $e) {
            Log::error('Remnawave Login Exception:', ['message' => $e->getMessage()]);
            return false;
        }
    }

    public function createUser(array $userData): ?array
    {
        if (!$this->accessToken) {
            if (!$this->login()) {
                return ['detail' => 'Authentication failed'];
            }
        }

        try {
            // Convert timestamp to ISO 8601 string if expire is numeric
            $expireAt = null;
            if (isset($userData['expire']) && $userData['expire'] > 0) {
                $expireAt = Carbon::createFromTimestamp($userData['expire'])->toIso8601String();
            } else {
                // Remnawave typically requires expireAt for create, let's set a default far future if 0 (or adjust to project needs)
                $expireAt = Carbon::now()->addYears(100)->toIso8601String();
            }

            $payload = [
                'username' => $userData['username'],
                'expireAt' => $expireAt,
                'trafficLimitBytes' => $userData['data_limit'] ?? 0,
                'trafficLimitStrategy' => 'NO_RESET',
                'status' => 'ACTIVE'
            ];

            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($this->baseUrl . '/api/users', $payload);

            Log::info('Remnawave Create User Response:', $response->json() ?? ['raw' => $response->body()]);
            return $response->json('response') ?? $response->json();
        } catch (\Exception $e) {
            Log::error('Remnawave Create User Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function updateUser(string $username, array $userData): ?array
    {
        if (!$this->accessToken) {
            if (!$this->login()) return null;
        }

        try {
            $expireAt = null;
            if (isset($userData['expire']) && $userData['expire'] > 0) {
                $expireAt = Carbon::createFromTimestamp($userData['expire'])->toIso8601String();
            } else {
                $expireAt = Carbon::now()->addYears(100)->toIso8601String();
            }

            $payload = [
                'username' => $username,
                'expireAt' => $expireAt,
                'trafficLimitBytes' => $userData['data_limit'] ?? 0,
                'trafficLimitStrategy' => 'NO_RESET'
            ];

            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->patch($this->baseUrl . '/api/users', $payload);

            Log::info('Remnawave Update User Response:', $response->json() ?? ['raw' => $response->body()]);
            return $response->json('response') ?? $response->json();
        } catch (\Exception $e) {
            Log::error('Remnawave Update User Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function generateSubscriptionLink(array $userApiResponse): string
    {
        // Remnawave returns subscriptionUrl in the user response object
        $subscriptionUrl = $userApiResponse['subscriptionUrl'] ?? '';
        
        // Sometimes the link might just be a path for nodeHostname, or a full URL
        if ($subscriptionUrl && !str_starts_with($subscriptionUrl, 'http')) {
            $subscriptionUrl = $this->nodeHostname . $subscriptionUrl;
        }

        return "لینک سابسکریپشن شما (در تمام برنامه‌ها import کنید):\n" . $subscriptionUrl;
    }

    public function resetTraffic(string $identifier): ?array
    {
        if (!$this->accessToken) {
            if (!$this->login()) return null;
        }

        try {
            // Check if identifier is a UUID (basic check)
            $isUuid = preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/i', $identifier);
            $uuid = $identifier;

            if (!$isUuid) {
                $user = $this->getUser($identifier);
                if ($user && isset($user['id'])) {
                    $uuid = $user['id'];
                } else {
                    Log::error('Remnawave Reset Traffic User Not Found:', ['username' => $identifier]);
                    return null;
                }
            }

            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($this->baseUrl . "/api/users/{$uuid}/actions/reset-traffic");

            return $response->json('response') ?? $response->json();
        } catch (\Exception $e) {
            Log::error('Remnawave Reset Traffic Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }
    
    public function getUser(string $username): ?array
    {
        if (!$this->accessToken) {
            if (!$this->login()) return null;
        }

        try {
            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->get($this->baseUrl . "/api/users/by-username/{$username}");

            return $response->json('response') ?? $response->json();
        } catch (\Exception $e) {
            Log::error('Remnawave Get User Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function disableUser(string $identifier): ?array
    {
        if (!$this->accessToken) {
            if (!$this->login()) return null;
        }

        try {
            $isUuid = preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/i', $identifier);
            $uuid = $identifier;

            if (!$isUuid) {
                $user = $this->getUser($identifier);
                if ($user && isset($user['id'])) {
                    $uuid = $user['id'];
                } else return null;
            }

            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($this->baseUrl . "/api/users/{$uuid}/actions/disable");

            return $response->json('response') ?? $response->json();
        } catch (\Exception $e) {
            Log::error('Remnawave Disable User Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function enableUser(string $identifier): ?array
    {
        if (!$this->accessToken) {
            if (!$this->login()) return null;
        }

        try {
            $isUuid = preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/i', $identifier);
            $uuid = $identifier;

            if (!$isUuid) {
                $user = $this->getUser($identifier);
                if ($user && isset($user['id'])) {
                    $uuid = $user['id'];
                } else return null;
            }

            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($this->baseUrl . "/api/users/{$uuid}/actions/enable");

            return $response->json('response') ?? $response->json();
        } catch (\Exception $e) {
            Log::error('Remnawave Enable User Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    public function deleteUser(string $identifier): bool
    {
        if (!$this->accessToken) {
            if (!$this->login()) return false;
        }

        try {
            $isUuid = preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/i', $identifier);
            $uuid = $identifier;

            if (!$isUuid) {
                $user = $this->getUser($identifier);
                if ($user && isset($user['id'])) {
                    $uuid = $user['id'];
                } else return false;
            }

            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->delete($this->baseUrl . "/api/users/{$uuid}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Remnawave Delete User Exception:', ['message' => $e->getMessage()]);
            return false;
        }
    }
}
