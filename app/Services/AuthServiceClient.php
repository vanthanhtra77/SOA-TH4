<?php

namespace App\Services;

class AuthServiceClient
{
    protected string $authUrl;
    protected int $timeout;
    protected bool $mockEnabled;

    public function __construct()
    {
        $this->authUrl = env('AUTH_SERVICE_URL', 'http://127.0.0.1:8000/auth');
        $this->timeout = (int) env('AUTH_SERVICE_TIMEOUT', 5);
        $this->mockEnabled = (bool) env('AUTH_SERVICE_MOCK', false);
    }

    protected function isMock(): bool
    {
        return $this->mockEnabled || (bool) env('AUTH_SERVICE_MOCK', false) || app()->environment('testing');
    }

    /**
     * Xác thực Bearer Token qua Auth Service (Service 2 - Port 8000)
     *
     * @param string $token
     * @return array [
     *   'valid' => bool,
     *   'status_code' => int,
     *   'user' => array|null,
     *   'error' => string|null
     * ]
     */
    public function verifyToken(string $token): array
    {
        // 1. Chế độ Mock cho Unit Test hoặc môi trường dev biệt lập
        if ($this->isMock()) {
            if ($token === 'valid-test-token' || str_starts_with($token, 'mock-valid-')) {
                return [
                    'valid' => true,
                    'status_code' => 200,
                    'user' => [
                        'IdUser' => 1,
                        'UserName' => 'student',
                    ],
                    'error' => null,
                ];
            }
            if ($token === 'invalid-test-token') {
                return [
                    'valid' => false,
                    'status_code' => 401,
                    'user' => null,
                    'error' => 'Token không hợp lệ hoặc đã hết hạn (Mock).',
                ];
            }
        }

        // 2. Gọi HTTP tới Auth Service theo chuẩn SOA
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->authUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json',
                'User-Agent: OrderService/1.0',
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Trường hợp không kết nối được tới Auth Service
        if ($responseBody === false || !empty($curlError)) {
            $localVerify = $this->verifyLocallyFallback($token);
            if ($localVerify !== null) {
                return $localVerify;
            }

            return [
                'valid' => false,
                'status_code' => 503,
                'user' => null,
                'error' => 'Không thể kết nối đến Dịch vụ Xác thực (Auth Service) tại ' . $this->authUrl . '. Lỗi: ' . $curlError,
            ];
        }

        $data = json_decode($responseBody, true);

        if ($statusCode === 200 && is_array($data)) {
            return [
                'valid' => true,
                'status_code' => 200,
                'user' => $data['user'] ?? null,
                'error' => null,
            ];
        }

        $errorMessage = $data['message'] ?? 'Token không hợp lệ hoặc đã hết hạn.';
        return [
            'valid' => false,
            'status_code' => $statusCode ?: 401,
            'user' => null,
            'error' => $errorMessage,
        ];
    }

    /**
     * Fallback xác thực cục bộ nếu Auth Service tạm thời không phản hồi
     */
    protected function verifyLocallyFallback(string $token): ?array
    {
        $secret = env('JWT_SECRET');
        if (empty($secret) || !env('AUTH_FALLBACK_LOCAL', false)) {
            return null;
        }

        try {
            if (class_exists(\Firebase\JWT\JWT::class) && class_exists(\Firebase\JWT\Key::class)) {
                $decoded = \Firebase\JWT\JWT::decode($token, new \Firebase\JWT\Key($secret, 'HS256'));
                return [
                    'valid' => true,
                    'status_code' => 200,
                    'user' => [
                        'IdUser' => $decoded->sub ?? 1,
                        'UserName' => $decoded->userName ?? 'student',
                    ],
                    'error' => null,
                ];
            }
        } catch (\Throwable $e) {
            return [
                'valid' => false,
                'status_code' => 401,
                'user' => null,
                'error' => 'Xác thực fallback thất bại: ' . $e->getMessage(),
            ];
        }

        return null;
    }
}
