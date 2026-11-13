<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ProductServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderItemController extends Controller
{
    protected ProductServiceClient $productClient;

    public function __construct(ProductServiceClient $productClient)
    {
        $this->productClient = $productClient;
    }

    /**
     * GET /order_items
     * Lấy danh sách tất cả mặt hàng trong đơn hàng (hỗ trợ lọc theo ?order_id=... hoặc ?product_id=...)
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = OrderItem::with('order');

        if ($orderId = $request->query('order_id')) {
            $query->where('order_id', $orderId);
        }

        if ($productId = $request->query('product_id')) {
            $query->where('product_id', $productId);
        }

        $items = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lấy danh sách chi tiết mặt hàng thành công.',
            'total' => $items->count(),
            'data' => $items,
        ], 200);
    }

    /**
     * GET /order_items/{id}
     * Lấy thông tin chi tiết một mặt hàng
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function show($id): JsonResponse
    {
        $item = OrderItem::with('order')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy mặt hàng có ID = {$id}.",
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lấy thông tin chi tiết mặt hàng thành công.',
            'data' => $item,
        ], 200);
    }

    /**
     * POST /order_items
     * Tạo mặt hàng mới trong đơn hàng (kiểm tra tồn kho và trừ kho từ Product Service)
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $this->validate($request, [
            'order_id' => 'required|integer|exists:orders,id',
            'product_id' => 'required|integer|min:1',
            'quantity' => 'required|integer|min:1',
        ], [
            'order_id.required' => 'Mã đơn hàng (order_id) là bắt buộc.',
            'order_id.exists' => 'Đơn hàng không tồn tại trong hệ thống.',
            'product_id.required' => 'Mã sản phẩm (product_id) là bắt buộc.',
            'quantity.required' => 'Số lượng mua (quantity) là bắt buộc.',
            'quantity.min' => 'Số lượng mua tối thiểu là 1.',
        ]);

        $order = Order::find($request->input('order_id'));
        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy đơn hàng tương ứng.',
            ], 404);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Không thể thêm mặt hàng vào đơn hàng đã bị hủy.',
            ], 422);
        }

        $productId = (int) $request->input('product_id');
        $quantity = (int) $request->input('quantity');
        $token = $request->bearerToken();

        // 1. Kiểm tra tồn kho tại Product Service (Port 8001)
        $stockCheck = $this->productClient->checkStock($productId, $quantity, $token);
        if (!$stockCheck['available']) {
            $status = $stockCheck['status_code'] ?? 422;
            return response()->json([
                'success' => false,
                'message' => 'Không thể thêm mặt hàng: ' . $stockCheck['message'],
                'current_stock' => $stockCheck['current_stock'] ?? 0,
                'requested_quantity' => $quantity,
            ], $status);
        }

        $product = $stockCheck['product'];
        $unitPrice = (float) $product['price'];
        $totalPrice = $unitPrice * $quantity;

        // 2. Trừ tồn kho tại Product Service
        $decreaseRes = $this->productClient->decreaseStock($productId, $quantity, $token);
        if (!$decreaseRes['success']) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi trừ kho từ Product Service: ' . ($decreaseRes['error'] ?? 'Không thể trừ tồn kho.'),
            ], 500);
        }

        // 3. Tạo bản ghi OrderItem
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $productId,
            'product_name' => $product['name'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
        ]);

        // 4. Cập nhật lại tổng tiền đơn hàng cha
        $order->recalculateTotal();

        return response()->json([
            'success' => true,
            'message' => 'Thêm mặt hàng mới vào đơn hàng thành công.',
            'data' => $item,
            'updated_order_total' => $order->total_amount,
        ], 201);
    }

    /**
     * PUT /order_items/{id}
     * Cập nhật mặt hàng (ví dụ thay đổi số lượng, tự động bù trừ kho với Product Service)
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     * @throws ValidationException
     */
    public function update(Request $request, $id): JsonResponse
    {
        $item = OrderItem::with('order')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy mặt hàng có ID = {$id} để cập nhật.",
            ], 404);
        }

        $this->validate($request, [
            'quantity' => 'sometimes|required|integer|min:1',
        ], [
            'quantity.min' => 'Số lượng mua tối thiểu là 1.',
        ]);

        $token = $request->bearerToken();
        $order = $item->order;

        if ($request->has('quantity')) {
            $newQuantity = (int) $request->input('quantity');
            $oldQuantity = (int) $item->quantity;
            $diff = $newQuantity - $oldQuantity;

            // Nếu số lượng tăng: cần kiểm tra và trừ thêm kho
            if ($diff > 0) {
                $stockCheck = $this->productClient->checkStock($item->product_id, $diff, $token);
                if (!$stockCheck['available']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Không thể tăng số lượng: ' . $stockCheck['message'],
                        'current_stock' => $stockCheck['current_stock'] ?? 0,
                        'additional_required' => $diff,
                    ], 422);
                }

                $decreaseRes = $this->productClient->decreaseStock($item->product_id, $diff, $token);
                if (!$decreaseRes['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Lỗi trừ kho bổ sung: ' . ($decreaseRes['error'] ?? 'Không thể trừ kho.'),
                    ], 500);
                }
            } elseif ($diff < 0 && $order->status !== 'cancelled') {
                // Nếu số lượng giảm: hoàn trả phần dư vào kho
                $restoreQty = abs($diff);
                $this->productClient->restoreStock($item->product_id, $restoreQty, $token);
            }

            $item->quantity = $newQuantity;
            $item->total_price = $item->unit_price * $newQuantity;
            $item->save();

            // Cập nhật lại tổng tiền đơn hàng
            $order->recalculateTotal();
        }

        return response()->json([
            'success' => true,
            'message' => "Cập nhật mặt hàng #{$id} thành công.",
            'data' => $item->fresh(),
            'updated_order_total' => $order->fresh()->total_amount,
        ], 200);
    }

    /**
     * DELETE /order_items/{id}
     * Xóa một mặt hàng trong đơn hàng (hoàn kho cho Product Service)
     *
     * @param Request $request
     * @param int|string $id
     * @return JsonResponse
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $item = OrderItem::with('order')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => "Không tìm thấy mặt hàng có ID = {$id} để xóa.",
            ], 404);
        }

        $token = $request->bearerToken();
        $order = $item->order;

        // Nếu đơn hàng chưa hủy hoặc hoàn tất, hoàn kho lại Product Service
        if ($order && $order->status !== 'cancelled' && $order->status !== 'completed') {
            $this->productClient->restoreStock($item->product_id, $item->quantity, $token);
        }

        $deletedId = $item->id;
        $item->delete();

        // Cập nhật lại tổng tiền đơn hàng
        if ($order) {
            $order->recalculateTotal();
        }

        return response()->json([
            'success' => true,
            'message' => "Đã xóa mặt hàng #{$deletedId} khỏi đơn hàng thành công.",
            'deleted_id' => $deletedId,
            'updated_order_total' => $order ? $order->fresh()->total_amount : 0,
        ], 200);
    }
}
