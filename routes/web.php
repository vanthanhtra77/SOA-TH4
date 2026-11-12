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

// Nhóm API Quản lý đơn hàng (Yêu cầu xác thực qua Auth Service - Service 2)
$router->group(['middleware' => 'auth.service'], function () use ($router) {
    // GET /orders: Lấy danh sách tất cả các đơn hàng
    $router->get('/orders', 'OrderController@index');

    // GET /orders/{id}: Lấy thông tin chi tiết một đơn hàng
    $router->get('/orders/{id}', 'OrderController@show');

    // POST /orders: Tạo đơn hàng mới (kiểm tra tồn kho và trừ kho từ Product Service)
    $router->post('/orders', 'OrderController@store');

    // PUT /orders/{id}: Cập nhật trạng thái đơn hàng (pending, confirmed, shipping, completed, cancelled)
    $router->put('/orders/{id}', 'OrderController@update');

    // DELETE /orders/{id}: Xóa đơn hàng (tự động hoàn kho nếu đơn chưa hủy)
    $router->delete('/orders/{id}', 'OrderController@destroy');
});
