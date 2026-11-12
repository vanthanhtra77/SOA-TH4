<?php

namespace App\Http\Middleware;

use App\Services\AuthServiceClient;
use Closure;
use Illuminate\Http\Request;

class AuthenticateViaAuthService
{
    protected AuthServiceClient $authClient;

    public function __construct(AuthServiceClient $authClient)
    {
        $this->authClient = $authClient;
    }

    /**
     * Xử lý request: Xác thực qua Service 2 (Auth Service) theo nguyên tắc SOA
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Truy cập bị từ chối: Thiếu Bearer Token trong header Authorization.',
                'hint' => 'Vui lòng đăng nhập tại Auth Service (Port 8000: POST /login) để lấy access_token, sau đó gửi kèm Authorization: Bearer <token>',
            ], 401)->header('WWW-Authenticate', 'Bearer');
        }

        // Gọi sang Auth Service (Service 2) để kiểm tra tính hợp lệ của token
        $verification = $this->authClient->verifyToken($token);

        if (!$verification['valid']) {
            $status = $verification['status_code'] ?? 401;
            return response()->json([
                'success' => false,
                'message' => 'Xác thực thất bại qua Dịch vụ Xác thực (Auth Service - Bài thực hành 2).',
                'error' => $verification['error'] ?? 'Token không hợp lệ hoặc đã hết hạn.',
            ], $status)->header('WWW-Authenticate', 'Bearer');
        }

        // Gắn thông tin người dùng đã xác thực vào Request
        $request->attributes->set('auth_user', $verification['user']);
        $request->attributes->set('bearer_token', $token);

        return $next($request);
    }
}
