<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = [
            [
                'user_id' => 1,
                'user_name' => 'student',
                'product_id' => 1,
                'product_name' => 'iPhone 15 Pro Max 256GB',
                'quantity' => 1,
                'unit_price' => 29990000.00,
                'total_price' => 29990000.00,
                'status' => 'completed',
                'receiver_name' => 'Nguyễn Văn Sinh Viên',
                'receiver_phone' => '0901234567',
                'shipping_address' => 'Số 123 Đường Nguyễn Huệ, Quận 1, TP. Hồ Chí Minh',
                'notes' => 'Giao hàng giờ hành chính',
            ],
            [
                'user_id' => 1,
                'user_name' => 'student',
                'product_id' => 3,
                'product_name' => 'Bàn phím cơ Keychron K2 Pro',
                'quantity' => 2,
                'unit_price' => 2150000.00,
                'total_price' => 4300000.00,
                'status' => 'confirmed',
                'receiver_name' => 'Nguyễn Văn Sinh Viên',
                'receiver_phone' => '0901234567',
                'shipping_address' => 'Ký túc xá Đại học, Khu đô thị ĐHQG',
                'notes' => 'Gọi trước khi giao 15 phút',
            ],
            [
                'user_id' => 1,
                'user_name' => 'student',
                'product_id' => 4,
                'product_name' => 'Chuột không dây Logitech MX Master 3S',
                'quantity' => 1,
                'unit_price' => 2490000.00,
                'total_price' => 2490000.00,
                'status' => 'pending',
                'receiver_name' => 'Trần Thị Thu Hà',
                'receiver_phone' => '0987654321',
                'shipping_address' => 'Phòng 402 Tòa nhà Landmark, TP. Thủ Đức',
                'notes' => 'Hàng công nghệ đóng gói cẩn thận',
            ],
        ];

        foreach ($orders as $order) {
            Order::create($order);
        }
    }
}
