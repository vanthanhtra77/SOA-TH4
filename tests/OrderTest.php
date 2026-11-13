<?php

namespace Tests;

use App\Models\Order;
use App\Models\OrderItem;

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

    // =========================================================================
    // 1. KIỂM THỬ XÁC THỰC & BẢO MẬT LIÊN DỊCH VỤ
    // =========================================================================

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

    // =========================================================================
    // 2. KIỂM THỬ NHÓM API QUẢN LÝ ĐƠN HÀNG (ORDERS)
    // =========================================================================

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
                '*' => [
                    'id',
                    'customer_name',
                    'customer_email',
                    'total_amount',
                    'status',
                    'items' => [
                        '*' => ['id', 'order_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'total_price']
                    ]
                ]
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
            'customer_name' => $order->customer_name,
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
            'customer_name' => 'Lê Hoàng Nam',
            'customer_email' => 'namle@example.com',
            'product_id' => 1,
            'quantity' => 2,
            'shipping_address' => 'Hà Nội',
            'notes' => 'Giao buổi chiều',
        ];

        $this->post('/orders', $payload, ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(201);
        $this->seeJson([
            'success' => true,
            'customer_name' => 'Lê Hoàng Nam',
            'status' => 'pending',
        ]);

        $this->seeInDatabase('orders', [
            'customer_name' => 'Lê Hoàng Nam',
            'customer_email' => 'namle@example.com',
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
        $this->seeJson(['success' => false]);
    }

    /**
     * Test 8: Tạo đơn hàng với mã sản phẩm không tồn tại trả về HTTP 404
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
     * Test 9: Cập nhật trạng thái đơn hàng (pending -> completed) (HTTP 200)
     */
    public function test_can_update_order_status(): void
    {
        $order = Order::where('status', 'pending')->first();
        $this->assertNotNull($order);

        $this->put('/orders/' . $order->id, ['status' => 'completed'], ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(200);
        $this->seeJson([
            'success' => true,
            'status' => 'completed',
        ]);
    }

    /**
     * Test 10: Hủy đơn hàng (status = cancelled) tự động hoàn trả số lượng kho (HTTP 200)
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
     * Test 11: Xóa đơn hàng thành công (HTTP 200)
     */
    public function test_can_delete_order(): void
    {
        $temp = Order::create([
            'customer_name' => 'Khách hàng thử nghiệm xóa',
            'customer_email' => 'testdelete@example.com',
            'total_amount' => 100000,
            'status' => 'pending',
        ]);
        $temp->items()->create([
            'product_id' => 1,
            'product_name' => 'Sản phẩm tạm',
            'quantity' => 1,
            'unit_price' => 100000,
            'total_price' => 100000,
        ]);

        $this->delete('/orders/' . $temp->id, [], ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(200);
        $this->seeJson([
            'success' => true,
            'deleted_id' => $temp->id,
        ]);

        $this->notSeeInDatabase('orders', ['id' => $temp->id]);
    }

    // =========================================================================
    // 3. KIỂM THỬ NHÓM API QUẢN LÝ CHI TIẾT ĐƠN HÀNG (ORDER_ITEMS)
    // =========================================================================

    /**
     * Test 12: Lấy danh sách tất cả các mặt hàng trong đơn hàng (HTTP 200)
     */
    public function test_can_get_all_order_items(): void
    {
        $this->get('/order_items', ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(200);
        $this->seeJsonStructure([
            'success',
            'message',
            'total',
            'data' => [
                '*' => ['id', 'order_id', 'product_id', 'product_name', 'quantity', 'unit_price', 'total_price']
            ]
        ]);
    }

    /**
     * Test 13: Lấy chi tiết một mặt hàng theo ID (HTTP 200)
     */
    public function test_can_get_single_order_item_by_id(): void
    {
        $item = OrderItem::first();
        $this->assertNotNull($item);

        $this->get('/order_items/' . $item->id, ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(200);
        $this->seeJson([
            'success' => true,
            'id' => $item->id,
            'product_name' => $item->product_name,
        ]);
    }

    /**
     * Test 14: Lấy mặt hàng không tồn tại trả về HTTP 404
     */
    public function test_get_nonexistent_order_item_returns_404(): void
    {
        $this->get('/order_items/999999', ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(404);
        $this->seeJson(['success' => false]);
    }

    /**
     * Test 15: Tạo mặt hàng mới trong đơn hàng (HTTP 201)
     */
    public function test_can_create_order_item(): void
    {
        $order = Order::where('status', '!=', 'cancelled')->first();
        $this->assertNotNull($order);

        $payload = [
            'order_id' => $order->id,
            'product_id' => 1,
            'quantity' => 1,
        ];

        $this->post('/order_items', $payload, ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(201);
        $this->seeJson([
            'success' => true,
            'order_id' => $order->id,
            'product_id' => 1,
        ]);

        $this->seeInDatabase('order_items', [
            'order_id' => $order->id,
            'product_id' => 1,
        ]);
    }

    /**
     * Test 16: Cập nhật số lượng mặt hàng (HTTP 200)
     */
    public function test_can_update_order_item(): void
    {
        $item = OrderItem::whereHas('order', fn($q) => $q->where('status', '!=', 'cancelled'))->first();
        $this->assertNotNull($item);

        $newQty = $item->quantity + 1;
        $this->put('/order_items/' . $item->id, ['quantity' => $newQty], ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(200);
        $this->seeJson([
            'success' => true,
            'quantity' => $newQty,
        ]);
    }

    /**
     * Test 17: Xóa mặt hàng khỏi đơn hàng (HTTP 200)
     */
    public function test_can_delete_order_item(): void
    {
        $order = Order::first();
        $tempItem = $order->items()->create([
            'product_id' => 1,
            'product_name' => 'Item thử nghiệm xóa',
            'quantity' => 1,
            'unit_price' => 50000,
            'total_price' => 50000,
        ]);

        $this->delete('/order_items/' . $tempItem->id, [], ['Authorization' => 'Bearer ' . $this->validToken]);
        $this->assertResponseStatus(200);
        $this->seeJson([
            'success' => true,
            'deleted_id' => $tempItem->id,
        ]);

        $this->notSeeInDatabase('order_items', ['id' => $tempItem->id]);
    }

    /**
     * Test 18: Endpoint kiểm tra trạng thái sức khỏe /health
     */
    public function test_health_check_endpoint(): void
    {
        $this->get('/health');
        $this->assertResponseStatus(200);
        $this->seeJson(['status' => 'UP']);
    }
}
