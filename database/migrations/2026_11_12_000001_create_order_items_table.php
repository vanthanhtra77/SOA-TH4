<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Chạy migration: Tạo bảng order_items cho Dịch vụ Quản lý Đơn hàng (Bài TH 4 - SOA)
     * Tuân thủ cấu trúc Bảng chi tiết đơn hàng (order_items) trong Phụ lục trang 2 đề bài:
     * - id: INT (PRIMARY KEY)
     * - order_id: INT (liên kết với bảng orders)
     * - product_id: INT (lưu vết để liên kết với dịch vụ quản lý sản phẩm)
     * - product_name: VARCHAR(255)
     * - quantity: INT (Số lượng sản phẩm trong đơn hàng)
     * - unit_price: DECIMAL(10, 2) (Giá sản phẩm tại thời điểm đặt hàng)
     * - total_price: DECIMAL(10, 2) (Tổng giá trị cho từng sản phẩm = quantity * unit_price)
     * - created_at & updated_at: TIMESTAMP
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->increments('id'); // INT PRIMARY KEY AUTO_INCREMENT
            $table->unsignedInteger('order_id'); // ID đơn hàng (liên kết orders.id)
            $table->unsignedInteger('product_id'); // ID sản phẩm từ Product Service
            $table->string('product_name', 255); // Tên sản phẩm lưu vết
            $table->integer('quantity'); // Số lượng sản phẩm
            $table->decimal('unit_price', 10, 2); // Giá tại thời điểm đặt hàng
            $table->decimal('total_price', 12, 2); // Tổng giá trị = quantity * unit_price
            $table->timestamps(); // created_at & updated_at TIMESTAMP

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Hoàn tác migration
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
