<?php

namespace Tests;

use App\Models\Order;

class OrderTest extends TestCase
{
    protected string $validToken = 'valid-test-token';
    protected string $invalidToken = 'invalid-test-token';

    protected function setUp(): void
    {
        parent::setUp();
        // Cấu hình mock cho testing độc lập
        putenv('AUTH_SERVICE_MOCK=true');
        putenv('PRODUCT_SERVICE_MOCK=true');
    }

    /**
     * Test 1: Yêu cầu không có Token phải bị từ chối với HTTP 401
     */
    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->get('/orders');
        $this->assertResponseStatus(401);
        $this->seeJsonStructure(['success', 'message']);
    }

    /**
     * Test 2: Yêu cầu với Token không hợp lệ phải bị từ chối với HTTP 401
     */
    public function test_invalid_token_is_rejected(): void
    {
        $this->get('/orders', ['Authorization' => 'Bearer ' . $this->invalidToken]);
        $this->assertResponseStatus(401);
        $this->seeJsonStructure(['success', 'message', 'error']);
    }

    /**
     * Test 3: Lấy danh sách tất cả đơn hàng thành công khi có Token hợp lệ
     */
    public function test_can_get_all_orders_with_valid_token(): void
    {
        $this->get('/orders', ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(200);
        $this->seeJsonStructure([
            'success',
            'message',
            'total',
            'data' => [
                '*' => ['id', 'user_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'total_price', 'status']
            ]
        ]);
    }

    /**
     * Test 4: Lấy chi tiết một đơn hàng thành công
     */
    public function test_can_get_single_order_by_id(): void
    {
        $order = Order::first();
        $this->assertNotNull($order, 'Cần có ít nhất 1 đơn hàng mẫu trong DB.');

        $this->get('/orders/' . $order->id, ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(200);
        $this->seeJson([
            'success' => true,
            'id' => $order->id,
            'product_name' => $order->product_name,
        ]);
    }

    /**
     * Test 5: Lấy đơn hàng không tồn tại phải trả về HTTP 404
     */
    public function test_get_nonexistent_order_returns_404(): void
    {
        $this->get('/orders/999999', ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(404);
        $this->seeJson(['success' => false]);
    }

    /**
     * Test 6: Tạo đơn hàng thành công khi sản phẩm tồn tại và đủ số lượng tồn kho (HTTP 201)
     */
    public function test_can_create_order_with_valid_stock(): void
    {
        // Mock Product ID = 1 có quantity = 15
        $payload = [
            'product_id' => 1,
            'quantity' => 2,
            'receiver_name' => 'Lê Hoàng Nam',
            'receiver_phone' => '0912345678',
            'shipping_address' => 'Hà Nội',
            'notes' => 'Giao buổi chiều',
        ];

        $this->post('/orders', $payload, ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(201);
        $this->seeJson([
            'success' => true,
            'product_id' => 1,
            'quantity' => 2,
            'status' => 'pending',
        ]);

        $this->seeInDatabase('orders', [
            'product_id' => 1,
            'quantity' => 2,
            'receiver_name' => 'Lê Hoàng Nam',
        ]);
    }

    /**
     * Test 7: Tạo đơn hàng thất bại khi số lượng mua vượt quá số lượng tồn kho (HTTP 422)
     */
    public function test_create_order_insufficient_stock_fails(): void
    {
        // Mock Product ID = 3 chỉ có quantity = 2
        $payload = [
            'product_id' => 3,
            'quantity' => 10, // Yêu cầu 10 cái -> Không đủ kho
        ];

        $this->post('/orders', $payload, ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(422);
        $this->seeJson([
            'success' => false,
        ]);
    }

    /**
     * Test 8: Tạo đơn hàng thất bại khi sản phẩm đã hết hàng (quantity = 0) (HTTP 422)
     */
    public function test_create_order_out_of_stock_fails(): void
    {
        // Mock Product ID = 4 có quantity = 0
        $payload = [
            'product_id' => 4,
            'quantity' => 1,
        ];

        $this->post('/orders', $payload, ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(422);
        $this->seeJson([
            'success' => false,
        ]);
    }

    /**
     * Test 9: Tạo đơn hàng với mã sản phẩm không tồn tại trả về HTTP 404
     */
    public function test_create_order_nonexistent_product_returns_404(): void
    {
        $payload = [
            'product_id' => 99999,
            'quantity' => 1,
        ];

        $this->post('/orders', $payload, ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(404);
        $this->seeJson(['success' => false]);
    }

    /**
     * Test 10: Cập nhật trạng thái đơn hàng (pending -> confirmed -> completed) (HTTP 200)
     */
    public function test_can_update_order_status(): void
    {
        $order = Order::where('status', 'pending')->first();
        $this->assertNotNull($order);

        $this->put('/orders/' . $order->id, ['status' => 'confirmed'], ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(200);
        $this->seeJson([
            'success' => true,
            'status' => 'confirmed',
        ]);
    }

    /**
     * Test 11: Hủy đơn hàng (status = cancelled) tự động hoàn trả số lượng kho (HTTP 200)
     */
    public function test_cancel_order_succeeds(): void
    {
        $order = Order::where('status', '!=', 'cancelled')->first();
        $this->assertNotNull($order);

        $this->put('/orders/' . $order->id, ['status' => 'cancelled'], ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(200);
        $this->seeJson([
            'success' => true,
            'status' => 'cancelled',
        ]);
    }

    /**
     * Test 12: Xóa đơn hàng thành công (HTTP 200)
     */
    public function test_can_delete_order(): void
    {
        $temp = Order::create([
            'user_id' => 1,
            'user_name' => 'student',
            'product_id' => 1,
            'product_name' => 'Đơn hàng tạm xóa',
            'quantity' => 1,
            'unit_price' => 100000,
            'total_price' => 100000,
            'status' => 'pending',
        ]);

        $this->delete('/orders/' . $temp->id, [], ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(200);
        $this->seeJson([
            'success' => true,
            'deleted_id' => $temp->id,
        ]);

        $this->notSeeInDatabase('orders', ['id' => $temp->id]);
    }

    /**
     * Test 13: Endpoint kiểm tra trạng thái sức khỏe /health
     */
    public function test_health_check_endpoint(): void
    {
        $this->get('/health');
        $this->assertResponseStatus(200);
        $this->seeJson(['status' => 'UP']);
    }
}
