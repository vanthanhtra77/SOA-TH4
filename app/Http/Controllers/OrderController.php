<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ProductServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    protected ProductServiceClient $productClient;

    public function __construct(ProductServiceClient $productClient)
    {
        $this->productClient = $productClient;
    }

    /**
     * GET /orders
     * Lấy danh sách tất cả các đơn hàng (hỗ trợ lọc theo status, user_id, product_id)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::query();

        // Lọc theo trạng thái đơn hàng (pending, confirmed, shipping, completed, cancelled)
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Lọc theo user_id
        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        // Lọc theo product_id
        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        // Sắp xếp đơn hàng mới nhất lên đầu
        $orders = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách đơn hàng thành công.',
            'total' => $orders->count(),
            'data' => $orders,
        ], 200);
    }

    /**
     * GET /orders/{id}
     * Lấy thông tin chi tiết một đơn hàng
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy đơn hàng có ID = {$id}.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin chi tiết đơn hàng thành công.',
            'data' => $order,
        ], 200);
    }

    /**
     * POST /orders
     * Tạo một đơn hàng mới (Tuân theo SOA: Kiểm tra tồn kho và trừ kho từ Product Service)
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'product_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1',
            'receiver_name' => 'nullable|string|max:255',
            'receiver_phone' => 'nullable|string|max:50',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
        ], [
            'product_id.required' => 'Mã sản phẩm (product_id) là bắt buộc.',
            'product_id.integer' => 'Mã sản phẩm phải là một số nguyên.',
            'quantity.required' => 'Số lượng mua (quantity) là bắt buộc.',
            'quantity.integer' => 'Số lượng mua phải là một số nguyên.',
            'quantity.min' => 'Số lượng mua tối thiểu là 1.',
        ]);

        $productId = (int) $request->input('product_id');
        $quantity = (int) $request->input('quantity');
        $token = $request->bearerToken();
        $authUser = $request->attributes->get('auth_user') ?? [];

        // 1. Giao tiếp liên dịch vụ SOA: Kiểm tra tồn kho tại Product Service (Port 8001)
        $stockCheck = $this->productClient->checkStock($productId, $quantity, $token);

        if (!$stockCheck['available']) {
            $status = $stockCheck['status_code'] ?? 422;
            return response()->json([
                'success' => false,
                'message' => 'Không thể tạo đơn hàng: ' . $stockCheck['message'],
                'current_stock' => $stockCheck['current_stock'] ?? 0,
                'requested_quantity' => $quantity,
            ], $status);
        }

        $product = $stockCheck['product'];
        $unitPrice = (float) $product['price'];
        $totalPrice = $unitPrice * $quantity;

        // 2. Giao tiếp liên dịch vụ SOA: Trừ số lượng tồn kho tại Product Service
        $decreaseRes = $this->productClient->decreaseStock($productId, $quantity, $token);
        if (!$decreaseRes['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi đồng bộ kho với Product Service: ' . ($decreaseRes['error'] ?? 'Không thể trừ tồn kho.'),
            ], 500);
        }

        // 3. Lưu đơn hàng vào cơ sở dữ liệu riêng của Order Service
        $order = Order::create([
            'user_id' => $authUser['IdUser'] ?? 1,
            'user_name' => $authUser['UserName'] ?? 'student',
            'product_id' => $productId,
            'product_name' => $product['name'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'status' => 'pending',
            'receiver_name' => $request->input('receiver_name', $authUser['UserName'] ?? 'Khách hàng'),
            'receiver_phone' => $request->input('receiver_phone'),
            'shipping_address' => $request->input('shipping_address'),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tạo đơn hàng mới thành công. Tồn kho sản phẩm đã được tự động cập nhật.',
            'data' => $order,
            'remaining_stock' => $decreaseRes['remaining_stock'] ?? null,
        ], 201);
    }

    /**
     * PUT /orders/{id}
     * Cập nhật thông tin / trạng thái đơn hàng
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy đơn hàng có ID = {$id} để cập nhật.",
            ], 404);
        }

        $this->validate($request, [
            'status' => 'sometimes|required|string|in:pending,confirmed,shipping,completed,cancelled',
            'receiver_name' => 'nullable|string|max:255',
            'receiver_phone' => 'nullable|string|max:50',
            'shipping_address' => 'nullable|string',
            'notes' => 'nullable|string',
        ], [
            'status.in' => 'Trạng thái đơn hàng phải thuộc một trong các giá trị: pending, confirmed, shipping, completed, cancelled.',
        ]);

        $token = $request->bearerToken();
        $oldStatus = $order->status;
        $newStatus = $request->input('status', $oldStatus);

        // Nghiệp vụ hoàn kho (SOA Compensation): Nếu hủy đơn hàng, tự động hoàn trả số lượng vào kho của Product Service
        if ($oldStatus !== 'cancelled' && $newStatus === 'cancelled') {
            $this->productClient->restoreStock($order->product_id, $order->quantity, $token);
        }

        $order->update($request->only([
            'status',
            'receiver_name',
            'receiver_phone',
            'shipping_address',
            'notes',
        ]));

        return response()->json([
            'success' => true,
            'message' => "Cập nhật đơn hàng #{$id} thành công (Trạng thái: {$order->status}).",
            'data' => $order->fresh(),
        ], 200);
    }

    /**
     * DELETE /orders/{id}
     * Xóa đơn hàng (Nếu đơn chưa bị hủy, tự động hoàn trả số lượng tồn kho)
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy đơn hàng có ID = {$id} để xóa.",
            ], 404);
        }

        $token = $request->bearerToken();

        // Nếu đơn hàng chưa hoàn tất hoặc chưa hủy, hoàn lại kho cho Product Service trước khi xóa
        if ($order->status !== 'cancelled' && $order->status !== 'completed') {
            $this->productClient->restoreStock($order->product_id, $order->quantity, $token);
        }

        $deletedId = $order->id;
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa đơn hàng #{$deletedId} thành công.",
            'deleted_id' => $deletedId,
        ], 200);
    }
}
