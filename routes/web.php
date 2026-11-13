<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes - BÀI THỰC HÀNH SỐ 4: Dịch vụ Quản lý Đơn hàng (SOA)
|--------------------------------------------------------------------------
*/

// Trang Web Demo kiểm thử trực quan trên trình duyệt
$router->get('/', function () {
    return view('demo');
});

// Endpoint kiểm tra trạng thái sức khỏe dịch vụ & kết nối SOA
$router->get('/health', function () {
    return response()->json([
        'service' => 'Order Management Service (Bài Thực Hành Số 4)',
        'status' => 'UP',
        'port' => 8002,
        'database' => config('database.default'),
        'connected_services' => [
            'auth_service' => env('AUTH_SERVICE_URL', 'http://127.0.0.1:8000/auth'),
            'product_service' => env('PRODUCT_SERVICE_URL', 'http://127.0.0.1:8001/products'),
        ],
        'timestamp' => date('Y-m-d H:i:s'),
    ]);
});

// Nhóm API Quản lý đơn hàng & chi tiết đơn hàng (Yêu cầu xác thực qua Auth Service - Service 2)
$router->group(['middleware' => 'auth.service'], function () use ($router) {
    // -------------------------------------------------------------
    // 1. NHÓM API QUẢN LÝ ĐƠN HÀNG (ORDERS)
    // -------------------------------------------------------------
    // GET /orders: Lấy danh sách tất cả các đơn hàng.
    $router->get('/orders', 'OrderController@index');

    // GET /orders/id: Lấy thông tin chi tiết một đơn hàng.
    $router->get('/orders/{id}', 'OrderController@show');

    // POST /orders: Tạo đơn hàng mới (kiểm tra tồn kho).
    $router->post('/orders', 'OrderController@store');

    // PUT /orders/id: Cập nhật trạng thái đơn hàng.
    $router->put('/orders/{id}', 'OrderController@update');

    // DELETE /orders/id: Xóa một đơn hàng.
    $router->delete('/orders/{id}', 'OrderController@destroy');

    // -------------------------------------------------------------
    // 2. NHÓM API CHI TIẾT ĐƠN HÀNG (ORDER_ITEMS)
    // -------------------------------------------------------------
    // GET /order_items: Lấy danh sách tất cả mặt hàng trong đơn hàng.
    $router->get('/order_items', 'OrderItemController@index');

    // GET /order_items/id: Lấy thông tin chi tiết một mặt hàng.
    $router->get('/order_items/{id}', 'OrderItemController@show');

    // POST /order_items: Tạo mặt hàng mới.
    $router->post('/order_items', 'OrderItemController@store');

    // PUT /order_items/id: Cập nhật mặt hàng.
    $router->put('/order_items/{id}', 'OrderItemController@update');

    // DELETE /order_items/id: Xóa một mặt hàng trong đơn hàng.
    $router->delete('/order_items/{id}', 'OrderItemController@destroy');
});
