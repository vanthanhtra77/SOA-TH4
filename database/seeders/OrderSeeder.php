<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ordersData = [
            [
                'order' => [
                    'customer_name' => 'Nguyễn Văn Sinh Viên',
                    'customer_email' => 'student@example.com',
                    'status' => 'completed',
                    'user_id' => 1,
                    'shipping_address' => 'Số 123 Đường Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh',
                    'notes' => 'Giao hàng giờ hành chính',
                ],
                'items' => [
                    [
                        'product_id' => 1,
                        'product_name' => 'iPhone 15 Pro Max 256GB',
                        'quantity' => 1,
                        'unit_price' => 29990000.00,
                        'total_price' => 29990000.00,
                    ],
                ]
            ],
            [
                'order' => [
                    'customer_name' => 'Trần Thị Thu Hà',
                    'customer_email' => 'thuha@example.com',
                    'status' => 'pending',
                    'user_id' => 1,
                    'shipping_address' => 'Ký túc xá Đại học, Khu đô thị ĐHQG',
                    'notes' => 'Gọi trước khi giao 15 phút',
                ],
                'items' => [
                    [
                        'product_id' => 3,
                        'product_name' => 'Bàn phím cơ Keychron K2 Pro',
                        'quantity' => 2,
                        'unit_price' => 2150000.00,
                        'total_price' => 4300000.00,
                    ],
                    [
                        'product_id' => 4,
                        'product_name' => 'Chuột không dây Logitech MX Master 3S',
                        'quantity' => 1,
                        'unit_price' => 2490000.00,
                        'total_price' => 2490000.00,
                    ],
                ]
            ],
            [
                'order' => [
                    'customer_name' => 'Lê Hoàng Nam',
                    'customer_email' => 'namle@example.com',
                    'status' => 'cancelled',
                    'user_id' => 1,
                    'shipping_address' => 'Phòng 402 Tòa nhà Landmark, TP. Thủ Đức',
                    'notes' => 'Khách hàng đổi ý hủy đơn',
                ],
                'items' => [
                    [
                        'product_id' => 6,
                        'product_name' => 'Tai nghe Sony WH-1000XM5',
                        'quantity' => 1,
                        'unit_price' => 7990000.00,
                        'total_price' => 7990000.00,
                    ],
                ]
            ],
        ];

        foreach ($ordersData as $data) {
            $totalAmount = array_sum(array_column($data['items'], 'total_price'));
            $orderAttr = $data['order'];
            $orderAttr['total_amount'] = $totalAmount;

            $order = Order::create($orderAttr);

            foreach ($data['items'] as $item) {
                $item['order_id'] = $order->id;
                OrderItem::create($item);
            }
        }
    }
}
