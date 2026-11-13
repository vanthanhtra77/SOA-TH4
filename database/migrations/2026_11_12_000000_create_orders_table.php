<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Chạy migration: Tạo bảng orders cho Dịch vụ Quản lý Đơn hàng (Bài TH 4 - SOA)
     * Tuân thủ cấu trúc Bảng đơn hàng (orders) trong Phụ lục đề bài:
     * - id: INT (PRIMARY KEY)
     * - customer_name: VARCHAR(255)
     * - customer_email: VARCHAR(255)
     * - total_amount: DECIMAL(10, 2)
     * - status: VARCHAR(50) (pending, completed, cancelled)
     * - created_at & updated_at: TIMESTAMP
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->increments('id'); // INT PRIMARY KEY AUTO_INCREMENT
            $table->string('customer_name', 255); // Tên khách hàng (bắt buộc theo đề)
            $table->string('customer_email', 255)->nullable(); // Email khách hàng (theo đề)
            $table->decimal('total_amount', 12, 2)->default(0); // Tổng số tiền của đơn hàng (theo đề)
            $table->string('status', 50)->default('pending'); // Trạng thái đơn hàng: pending, completed, cancelled
            
            // Các trường bổ trợ tích hợp SOA
            $table->unsignedInteger('user_id')->nullable(); // ID người dùng (từ Auth Service)
            $table->text('shipping_address')->nullable(); // Địa chỉ giao hàng
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
