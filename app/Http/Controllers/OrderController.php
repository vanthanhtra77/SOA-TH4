<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
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
     * Lấy danh sách tất cả các đơn hàng (kèm chi tiết các mặt hàng trong đơn)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with('items');

        // Lọc theo trạng thái đơn hàng (pending, completed, cancelled)
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        // Lọc theo tên khách hàng
        if ($customerName = $request->query('customer_name') ?? $request->query('name')) {
            $query->where('customer_name', 'like', "%{$customerName}%");
        }

        // Lọc theo product_id thông qua order_items
        if ($productId = $request->query('product_id')) {
            $query->whereHas('items', function ($q) use ($productId) {
                $q->where('product_id', $productId);
            });
        }

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
        $order = Order::with('items')->find($id);

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
     * Tạo đơn hàng mới (Tuân thủ SOA: Kiểm tra tồn kho và trừ kho từ Product Service)
     * Hỗ trợ tạo đơn kèm danh sách mặt hàng (items array) hoặc 1 mặt hàng đơn lẻ
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        // 1. Kiểm tra cấu trúc payload
        if ($request->has('items') && is_array($request->input('items'))) {
            $this->validate($request, [
                'customer_name' => 'nullable|string|max:255',
                'customer_email' => 'nullable|email|max:255',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|min:1',
                'items.*.quantity' => 'required|integer|min:1',
                'shipping_address' => 'nullable|string',
                'notes' => 'nullable|string',
            ], [
                'items.required' => 'Danh sách mặt hàng (items) là bắt buộc.',
                'items.min' => 'Đơn hàng phải có ít nhất 1 mặt hàng.',
                'items.*.product_id.required' => 'Mã sản phẩm (product_id) là bắt buộc.',
                'items.*.quantity.required' => 'Số lượng (quantity) là bắt buộc.',
            ]);

            $rawItems = $request->input('items');
        } else {
            $this->validate($request, [
                'product_id' => 'required|integer|min:1',
                'quantity' => 'required|integer|min:1',
                'customer_name' => 'nullable|string|max:255',
                'customer_email' => 'nullable|email|max:255',
                'shipping_address' => 'nullable|string',
                'notes' => 'nullable|string',
            ], [
                'product_id.required' => 'Mã sản phẩm (product_id) là bắt buộc.',
                'product_id.integer' => 'Mã sản phẩm phải là một số nguyên.',
                'quantity.required' => 'Số lượng mua (quantity) là bắt buộc.',
                'quantity.integer' => 'Số lượng mua phải là một số nguyên.',
                'quantity.min' => 'Số lượng mua tối thiểu là 1.',
            ]);

            $rawItems = [
                [
                    'product_id' => (int) $request->input('product_id'),
                    'quantity' => (int) $request->input('quantity'),
                ]
            ];
        }

        $token = $request->bearerToken();
        $authUser = $request->attributes->get('auth_user') ?? [];

        // 2. Giao tiếp liên dịch vụ SOA: Kiểm tra tồn kho cho tất cả sản phẩm
        $verifiedItems = [];
        foreach ($rawItems as $item) {
            $pId = (int) $item['product_id'];
            $qty = (int) $item['quantity'];

            $stockCheck = $this->productClient->checkStock($pId, $qty, $token);
            if (!$stockCheck['available']) {
                $status = $stockCheck['status_code'] ?? 422;
                return response()->json([
                    'success' => false,
                    'message' => 'Không thể tạo đơn hàng: ' . $stockCheck['message'],
                    'product_id' => $pId,
                    'current_stock' => $stockCheck['current_stock'] ?? 0,
                    'requested_quantity' => $qty,
                ], $status);
            }

            $product = $stockCheck['product'];
            $unitPrice = (float) $product['price'];
            $totalPrice = $unitPrice * $qty;

            $verifiedItems[] = [
                'product_id' => $pId,
                'product_name' => $product['name'],
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
            ];
        }

        // 3. Trừ số lượng tồn kho qua Product Service
        $decreasedItems = [];
        foreach ($verifiedItems as $vItem) {
            $decreaseRes = $this->productClient->decreaseStock($vItem['product_id'], $vItem['quantity'], $token);
            if (!$decreaseRes['success']) {
                // Hoàn tác kho cho các mặt hàng đã trừ trước đó nếu gặp lỗi
                foreach ($decreasedItems as $dec) {
                    $this->productClient->restoreStock($dec['product_id'], $dec['quantity'], $token);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Lỗi đồng bộ kho với Product Service: ' . ($decreaseRes['error'] ?? 'Không thể trừ tồn kho.'),
                ], 500);
            }
            $decreasedItems[] = $vItem;
        }

        // 4. Lưu đơn hàng vào CSDL riêng của Order Service (theo chuẩn Phụ lục)
        $customerName = $request->input('customer_name') 
            ?? $request->input('receiver_name') 
            ?? ($authUser['UserName'] ?? 'Nguyễn Văn Khách');
        
        $customerEmail = $request->input('customer_email') 
            ?? ($authUser['Email'] ?? ($authUser['UserName'] ?? 'customer') . '@example.com');

        $totalAmount = array_sum(array_column($verifiedItems, 'total_price'));

        $order = Order::create([
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'user_id' => $authUser['IdUser'] ?? 1,
            'shipping_address' => $request->input('shipping_address'),
            'notes' => $request->input('notes'),
        ]);

        // 5. Lưu các chi tiết mặt hàng vào bảng order_items
        foreach ($verifiedItems as $vItem) {
            $order->items()->create([
                'product_id' => $vItem['product_id'],
                'product_name' => $vItem['product_name'],
                'quantity' => $vItem['quantity'],
                'unit_price' => $vItem['unit_price'],
                'total_price' => $vItem['total_price'],
            ]);
        }

        $order->load('items');

        return response()->json([
            'success' => true,
            'message' => 'Tạo đơn hàng mới thành công. Tồn kho sản phẩm đã được tự động cập nhật.',
            'data' => $order,
        ], 201);
    }

    /**
     * PUT /orders/{id}
     * Cập nhật trạng thái đơn hàng (pending, completed, cancelled)
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id): JsonResponse
    {
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy đơn hàng có ID = {$id} để cập nhật.",
            ], 404);
        }

        $this->validate($request, [
            'status' => 'sometimes|required|string|in:pending,confirmed,shipping,completed,cancelled',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
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
            foreach ($order->items as $item) {
                $this->productClient->restoreStock($item->product_id, $item->quantity, $token);
            }
        }

        $order->update($request->only([
            'status',
            'customer_name',
            'customer_email',
            'shipping_address',
            'notes',
        ]));

        return response()->json([
            'success' => true,
            'message' => "Cập nhật đơn hàng #{$id} thành công (Trạng thái: {$order->status}).",
            'data' => $order->fresh(['items']),
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
        $order = Order::with('items')->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy đơn hàng có ID = {$id} để xóa.",
            ], 404);
        }

        $token = $request->bearerToken();

        // Nếu đơn hàng chưa hoàn tất hoặc chưa hủy, hoàn lại kho cho Product Service trước khi xóa
        if ($order->status !== 'cancelled' && $order->status !== 'completed') {
            foreach ($order->items as $item) {
                $this->productClient->restoreStock($item->product_id, $item->quantity, $token);
            }
        }

        $deletedId = $order->id;
        // Xóa chi tiết mặt hàng
        $order->items()->delete();
        $order->delete();

        return response()->json([
            'success' => true,
            'message' => "Đã xóa đơn hàng #{$deletedId} thành công.",
            'deleted_id' => $deletedId,
        ], 200);
    }
}
