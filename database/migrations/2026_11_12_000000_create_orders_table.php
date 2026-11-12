<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Chạy migration: Tạo bảng orders cho Dịch vụ Quản lý Đơn hàng (Bài TH 4 - SOA)
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id'); // INT PRIMARY KEY AUTO_INCREMENT
            $table->unsignedInteger('user_id'); // ID người dùng đặt hàng (từ Auth Service)
            $table->string('user_name', 255)->nullable(); // Tên tài khoản người đặt
            $table->unsignedInteger('product_id'); // ID sản phẩm (từ Product Service)
            $table->string('product_name', 255); // Tên sản phẩm lưu vết
            $table->integer('quantity'); // Số lượng sản phẩm mua
            $table->decimal('unit_price', 10, 2); // Đơn giá tại thời điểm mua
            $table->decimal('total_price', 12, 2); // Tổng tiền = quantity * unit_price
            $table->string('status', 50)->default('pending'); // pending, confirmed, shipping, completed, cancelled
            $table->string('receiver_name', 255)->nullable(); // Tên người nhận hàng
            $table->string('receiver_phone', 50)->nullable(); // Số điện thoại
            $table->text('shipping_address')->nullable(); // Địa chỉ giao nhận
            $table->text('notes')->nullable(); // Ghi chú đơn hàng
            $table->timestamps(); // created_at & updated_at TIMESTAMP
        });
    }

    /**
     * Hoàn tác migration
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
