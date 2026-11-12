<?php

namespace App\Services;

class ProductServiceClient
{
    protected string $productServiceUrl;
    protected int $timeout;
    protected bool $mockEnabled;

    /**
     * Dữ liệu giả lập cho môi trường testing / mock độc lập
     */
    protected static array $mockProducts = [
        1 => [
            'id' => 1,
            'name' => 'iPhone 15 Pro Max 256GB',
            'description' => 'Điện thoại cao cấp chip A17 Pro',
            'price' => 29990000.00,
            'quantity' => 15,
        ],
        2 => [
            'id' => 2,
            'name' => 'Laptop Dell XPS 15 9530',
            'description' => 'Laptop doanh nhân Core i7',
            'price' => 45500000.00,
            'quantity' => 8,
        ],
        3 => [
            'id' => 3,
            'name' => 'Bàn phím cơ Keychron K2 Pro',
            'description' => 'Bàn phím cơ không dây',
            'price' => 2150000.00,
            'quantity' => 2,
        ],
        4 => [
            'id' => 4,
            'name' => 'Chuột không dây Logitech MX Master 3S',
            'description' => 'Chuột công thái học cao cấp',
            'price' => 2490000.00,
            'quantity' => 0,
        ],
    ];

    public function __construct()
    {
        $this->productServiceUrl = rtrim(env('PRODUCT_SERVICE_URL', 'http://127.0.0.1:8001/products'), '/');
        $this->timeout = (int) env('PRODUCT_SERVICE_TIMEOUT', 5);
        $this->mockEnabled = (bool) env('PRODUCT_SERVICE_MOCK', false);
    }

    protected function isMock(): bool
    {
        return $this->mockEnabled || (bool) env('PRODUCT_SERVICE_MOCK', false) || app()->environment('testing');
    }

    /**
     * Lấy thông tin chi tiết một sản phẩm từ Product Service (GET /products/{id})
     */
    public function getProduct(int $productId, ?string $token = null): array
    {
        if ($this->isMock()) {
            if (isset(self::$mockProducts[$productId])) {
                return [
                    'success' => true,
                    'status_code' => 200,
                    'product' => self::$mockProducts[$productId],
                    'error' => null,
                ];
            }
            return [
                'success' => false,
                'status_code' => 404,
                'product' => null,
                'error' => "Không tìm thấy sản phẩm có ID = {$productId} tại Product Service.",
            ];
        }

        $url = $this->productServiceUrl . '/' . $productId;
        $headers = [
            'Accept: application/json',
            'User-Agent: OrderService/1.0',
        ];
        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $responseBody = curl_exec($ch);
        $curlError = curl_error($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($responseBody === false || !empty($curlError)) {
            return [
                'success' => false,
                'status_code' => 503,
                'product' => null,
                'error' => "Không thể kết nối đến Product Service tại {$url}. Lỗi: {$curlError}",
            ];
        }

        $data = json_decode($responseBody, true);

        if ($statusCode === 200 && isset($data['data'])) {
            return [
                'success' => true,
                'status_code' => 200,
                'product' => $data['data'],
                'error' => null,
            ];
        }

        return [
            'success' => false,
            'status_code' => $statusCode ?: 404,
            'product' => null,
            'error' => $data['message'] ?? "Sản phẩm #{$productId} không tồn tại hoặc lỗi lấy dữ liệu.",
        ];
    }

    /**
     * Kiểm tra số lượng tồn kho sản phẩm từ Product Service
     */
    public function checkStock(int $productId, int $requestedQuantity, ?string $token = null): array
    {
        $res = $this->getProduct($productId, $token);

        if (!$res['success']) {
            return [
                'available' => false,
                'status_code' => $res['status_code'],
                'product' => null,
                'current_stock' => 0,
                'message' => $res['error'] ?? 'Không tìm thấy sản phẩm.',
            ];
        }

        $product = $res['product'];
        $currentStock = (int) ($product['quantity'] ?? 0);

        if ($currentStock <= 0) {
            return [
                'available' => false,
                'status_code' => 422,
                'product' => $product,
                'current_stock' => $currentStock,
                'message' => "Sản phẩm '{$product['name']}' hiện đã hết hàng trong kho.",
            ];
        }

        if ($currentStock < $requestedQuantity) {
            return [
                'available' => false,
                'status_code' => 422,
                'product' => $product,
                'current_stock' => $currentStock,
                'message' => "Số lượng tồn kho không đủ (Hiện còn: {$currentStock}, yêu cầu: {$requestedQuantity}).",
            ];
        }

        return [
            'available' => true,
            'status_code' => 200,
            'product' => $product,
            'current_stock' => $currentStock,
            'message' => 'Số lượng sản phẩm trong kho đáp ứng đủ yêu cầu đơn hàng.',
        ];
    }

    /**
     * Cập nhật trừ số lượng tồn kho khi tạo đơn hàng thành công (PUT /products/{id})
     */
    public function decreaseStock(int $productId, int $quantityToDeduct, ?string $token = null): array
    {
        if ($this->isMock()) {
            if (isset(self::$mockProducts[$productId])) {
                self::$mockProducts[$productId]['quantity'] = max(0, self::$mockProducts[$productId]['quantity'] - $quantityToDeduct);
                return ['success' => true, 'remaining_stock' => self::$mockProducts[$productId]['quantity']];
            }
            return ['success' => false, 'error' => 'Mock product not found'];
        }

        $check = $this->getProduct($productId, $token);
        if (!$check['success']) {
            return ['success' => false, 'error' => $check['error']];
        }

        $currentStock = (int) $check['product']['quantity'];
        $newStock = max(0, $currentStock - $quantityToDeduct);

        return $this->updateStockDirect($productId, $newStock, $token);
    }

    /**
     * Hoàn lại số lượng tồn kho khi đơn hàng bị hủy hoặc xóa (PUT /products/{id})
     */
    public function restoreStock(int $productId, int $quantityToRestore, ?string $token = null): array
    {
        if ($this->isMock()) {
            if (isset(self::$mockProducts[$productId])) {
                self::$mockProducts[$productId]['quantity'] += $quantityToRestore;
                return ['success' => true, 'remaining_stock' => self::$mockProducts[$productId]['quantity']];
            }
            return ['success' => false, 'error' => 'Mock product not found'];
        }

        $check = $this->getProduct($productId, $token);
        if (!$check['success']) {
            return ['success' => false, 'error' => $check['error']];
        }

        $currentStock = (int) $check['product']['quantity'];
        $newStock = $currentStock + $quantityToRestore;

        return $this->updateStockDirect($productId, $newStock, $token);
    }

    /**
     * Gửi PUT request cập nhật trực tiếp số lượng tồn kho
     */
    protected function updateStockDirect(int $productId, int $newStock, ?string $token = null): array
    {
        $url = $this->productServiceUrl . '/' . $productId;
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: OrderService/1.0',
        ];
        if (!empty($token)) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $payload = json_encode(['quantity' => $newStock]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $responseBody = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode >= 200 && $statusCode < 300) {
            return ['success' => true, 'remaining_stock' => $newStock];
        }

        return [
            'success' => false,
            'error' => "Cập nhật tồn kho tại Product Service thất bại (Mã lỗi: {$statusCode})",
        ];
    }
}
