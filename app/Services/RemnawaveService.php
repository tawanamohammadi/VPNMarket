<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class RemnawaveService
{
    protected string $baseUrl;
    protected string $nodeHostname;
    protected ?string $accessToken = null;

    /**
     * @param string $baseUrl       - آدرس پنل Remnawave مثال: https://panel.example.com
     * @param string $apiToken      - API Token که از داشبورد Remnawave ساخته می‌شه
     * @param string $nodeHostname  - آدرس سابسکریپشن مثال: https://sub.example.com
     */
    public function __construct(string $baseUrl, string $apiToken, string $nodeHostname)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->accessToken = trim($apiToken, '"\'\ ');
        $this->nodeHostname = rtrim($nodeHostname, '/');
    }

    public function createUser(array $userData): ?array
    {
        if (!$this->accessToken) {
            Log::error('Remnawave: API Token is not set.');
            return ['detail' => 'API Token not configured'];
        }

        try {
            $expireAt = null;
            if (isset($userData['expire']) && $userData['expire'] > 0) {
                $expireAt = Carbon::createFromTimestamp($userData['expire'])->toIso8601String();
            } else {
                $expireAt = Carbon::now()->addYears(100)->toIso8601String();
            }

            $payload = [
                'username' => $userData['username'],
                'expireAt' => $expireAt,
                'trafficLimitBytes' => $userData['data_limit'] ?? 0,
                'trafficLimitStrategy' => 'NO_RESET',
                'status' => 'ACTIVE'
            ];

            // اگر inbound UUID تعریف شده، اضافه کن
            if (!empty($userData['active_user_inbounds'])) {
                $payload['activeUserInbounds'] = $userData['active_user_inbounds'];
            } elseif (!empty($userData['squad_uuid'])) {
                // Remnawave API: activeUserInbounds آرایه می‌گیره
                $payload['activeUserInbounds'] = [['inboundUuid' => $userData['squad_uuid']]];
            }

            Log::info('Remnawave Create User Payload:', $payload);

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
        if (!$this->accessToken) return null;

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
        $subscriptionUrl = $userApiResponse['subscriptionUrl'] ?? '';

        if ($subscriptionUrl && !str_starts_with($subscriptionUrl, 'http')) {
            $subscriptionUrl = $this->nodeHostname . $subscriptionUrl;
        }

        return "لینک سابسکریپشن شما (در تمام برنامه‌ها import کنید):\n" . $subscriptionUrl;
    }

    public function resetTraffic(string $identifier): ?array
    {
        if (!$this->accessToken) return null;

        try {
            $uuid = $this->resolveUuid($identifier);
            if (!$uuid) return null;

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
        if (!$this->accessToken) return null;

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
        if (!$this->accessToken) return null;

        try {
            $uuid = $this->resolveUuid($identifier);
            if (!$uuid) return null;

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
        if (!$this->accessToken) return null;

        try {
            $uuid = $this->resolveUuid($identifier);
            if (!$uuid) return null;

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
        if (!$this->accessToken) return false;

        try {
            $uuid = $this->resolveUuid($identifier);
            if (!$uuid) return false;

            $response = Http::withToken($this->accessToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->delete($this->baseUrl . "/api/users/{$uuid}");

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Remnawave Delete User Exception:', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * اگر identifier یه UUID هست همونو برمی‌گردونه
     * اگر username هست، ابتدا User رو می‌گیره و UUID اونو برمی‌گردونه
     */
    protected function resolveUuid(string $identifier): ?string
    {
        $isUuid = preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){3}-[a-f\d]{12}$/i', $identifier);
        if ($isUuid) {
            return $identifier;
        }

        $user = $this->getUser($identifier);
        if ($user && isset($user['id'])) {
            return $user['id'];
        }

        Log::error('Remnawave: Could not resolve UUID for identifier:', ['identifier' => $identifier]);
        return null;
    }
}
