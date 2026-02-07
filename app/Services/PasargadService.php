<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * سرویس ارتباط با پنل پاسارگاد (PasarGuard)
 * 
 * این سرویس برای مدیریت کاربران در پنل پاسارگاد استفاده می‌شود.
 * API پاسارگاد مشابه Marzban است با تفاوت‌های جزئی.
 */
class PasargadService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected ?string $nodeHostname;
    protected ?string $accessToken = null;

    public function __construct(string $baseUrl, string $username, string $password, ?string $nodeHostname = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->username = $username;
        $this->password = $password;
        $this->nodeHostname = $nodeHostname;
    }

    /**
     * لاگین به پنل و دریافت توکن JWT
     */
    public function login(): bool
    {
        try {
            // پاسارگاد از Form Data برای لاگین استفاده می‌کند (مثل Marzban)
            $response = Http::withOptions(['verify' => false])
                ->asForm()
                ->post($this->baseUrl . '/api/admin/token', [
                    'username' => $this->username,
                    'password' => $this->password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // استخراج توکن - پاسارگاد ممکن است در ساختارهای مختلف برگرداند
                $token = $data['access_token'] ?? null;
                if (!$token && isset($data['data'])) {
                    $token = $data['data']['token'] ?? $data['data']['access_token'] ?? null;
                }

                if ($token) {
                    $this->accessToken = $token;
                    Log::info('Pasargad Login Successful');
                    return true;
                }
            }

            Log::error('Pasargad Login Failed:', ['status' => $response->status(), 'body' => $response->body()]);
            return false;

        } catch (\Exception $e) {
            Log::error('Pasargad Login Exception:', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * ساخت کاربر جدید در پنل
     */
    public function createUser(array $userData): ?array
    {
        if (!$this->accessToken) {
            if (!$this->login()) {
                return ['detail' => 'Authentication failed'];
            }
        }

        try {
            // ساختار درخواست برای پاسارگاد
            $payload = [
                'username' => $userData['username'],
                'proxies' => [
                    'vmess' => new \stdClass(),
                    'vless' => ['flow' => ''],
                    'trojan' => new \stdClass(),
                    'shadowsocks' => ['method' => 'chacha20-ietf-poly1305'],
                ],
                'inbounds' => new \stdClass(),
                'expire' => $userData['expire'] ?? 0,
                'data_limit' => $userData['data_limit'] ?? 0,
                'data_limit_reset_strategy' => 'no_reset',
                'status' => 'active',
                'note' => $userData['note'] ?? 'Created by VPNMarket',
                // 🔥 نکته مهم: پاسارگاد نیاز به group_ids دارد
                'group_ids' => $userData['group_ids'] ?? [1],
            ];

            $response = Http::withOptions(['verify' => false])
                ->withToken($this->accessToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->post($this->baseUrl . '/api/user', $payload);

            $result = $response->json();
            
            Log::info('Pasargad Create User Response:', $result ?? ['raw' => $response->body()]);

            if ($response->successful() && isset($result['username'])) {
                // ساخت لینک سابسکریپشن
                $result['subscription_url'] = $this->generateSubscriptionLink($result['username']);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Pasargad Create User Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * آپدیت اطلاعات کاربر
     */
    public function updateUser(string $username, array $userData): ?array
    {
        if (!$this->accessToken) {
            if (!$this->login()) return null;
        }

        try {
            $payload = [];
            
            if (isset($userData['expire'])) {
                $payload['expire'] = $userData['expire'];
            }
            if (isset($userData['data_limit'])) {
                $payload['data_limit'] = $userData['data_limit'];
            }
            if (isset($userData['status'])) {
                $payload['status'] = $userData['status'];
            }

            $response = Http::withOptions(['verify' => false])
                ->withToken($this->accessToken)
                ->withHeaders(['Accept' => 'application/json'])
                ->put($this->baseUrl . "/api/user/{$username}", $payload);

            Log::info('Pasargad Update User Response:', $response->json() ?? ['raw' => $response->body()]);
            return $response->json();

        } catch (\Exception $e) {
            Log::error('Pasargad Update User Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * حذف کاربر
     */
    public function deleteUser(string $username): bool
    {
        if (!$this->accessToken) {
            if (!$this->login()) return false;
        }

        try {
            $response = Http::withOptions(['verify' => false])
                ->withToken($this->accessToken)
                ->delete($this->baseUrl . "/api/user/{$username}");

            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Pasargad Delete User Exception:', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * دریافت اطلاعات کاربر
     */
    public function getUser(string $username): ?array
    {
        if (!$this->accessToken) {
            if (!$this->login()) return null;
        }

        try {
            $response = Http::withOptions(['verify' => false])
                ->withToken($this->accessToken)
                ->get($this->baseUrl . "/api/user/{$username}");

            if ($response->successful()) {
                return $response->json();
            }
            return null;

        } catch (\Exception $e) {
            Log::error('Pasargad Get User Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ریست کردن ترافیک مصرفی کاربر
     */
    public function resetUserTraffic(string $username): bool
    {
        if (!$this->accessToken) {
            if (!$this->login()) return false;
        }

        try {
            $response = Http::withOptions(['verify' => false])
                ->withToken($this->accessToken)
                ->post($this->baseUrl . "/api/user/{$username}/reset");

            Log::info('Pasargad Reset Traffic Response:', ['username' => $username, 'status' => $response->status()]);
            return $response->successful();

        } catch (\Exception $e) {
            Log::error('Pasargad Reset Traffic Exception:', ['message' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * دریافت لیست گروه‌ها
     */
    public function getGroups(): array
    {
        if (!$this->accessToken) {
            if (!$this->login()) return [];
        }

        try {
            $response = Http::withOptions(['verify' => false])
                ->withToken($this->accessToken)
                ->get($this->baseUrl . '/api/groups');

            if ($response->successful()) {
                $data = $response->json();
                // پاسارگاد گروه‌ها را در کلید groups برمی‌گرداند
                return $data['groups'] ?? $data['data'] ?? (is_array($data) ? $data : []);
            }
            return [];

        } catch (\Exception $e) {
            Log::error('Pasargad Get Groups Exception:', ['message' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * ساخت لینک سابسکریپشن
     * در پاسارگاد لینک به صورت /sub/{username} است
     */
    public function generateSubscriptionLink(string $username): string
    {
        $baseUrl = $this->baseUrl;
        
        // اگر آدرس نود (سابسکریپشن) جداگانه تنظیم شده است
        if ($this->nodeHostname) {
            $nodeHost = trim($this->nodeHostname);
            
            // اگر با http شروع شده، یعنی یک URL کامل است
            if (str_starts_with($nodeHost, 'http')) {
                return rtrim($nodeHost, '/') . "/sub/{$username}";
            }
            
            // در غیر این صورت، پروتکل را از baseUrl می‌گیریم اما پورت را حذف می‌کنیم (فرض بر CDN یا استاندارد)
            $parsedBase = parse_url($baseUrl);
            $scheme = $parsedBase['scheme'] ?? 'https';
            return "{$scheme}://{$nodeHost}/sub/{$username}";
        }
        
        return "{$baseUrl}/sub/{$username}";
    }

    /**
     * آمار سیستم
     */
    public function getSystemStats(): ?array
    {
        if (!$this->accessToken) {
            if (!$this->login()) return null;
        }

        try {
            $response = Http::withOptions(['verify' => false])
                ->withToken($this->accessToken)
                ->get($this->baseUrl . '/api/system');

            if ($response->successful()) {
                return $response->json();
            }
            return null;

        } catch (\Exception $e) {
            Log::error('Pasargad System Stats Exception:', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
