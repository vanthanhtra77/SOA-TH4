# SOA TH4 - Dịch Vụ Quản Lý Đơn Hàng (Order Management Service)

[![Tests](https://img.shields.io/badge/PHPUnit-18%20Passed%2C%20154%20Assertions-brightgreen)](tests/OrderTest.php)
[![Architecture](https://img.shields.io/badge/Architecture-SOA-blue)]()
[![Framework](https://img.shields.io/badge/Framework-Lumen%2010-red)]()
[![PHP](https://img.shields.io/badge/PHP-8.4-purple)]()

Dự án **Bài Thực Hành Số 4** - Môn Kiến Trúc Hướng Dịch Vụ (Service-Oriented Architecture - SOA).
Dịch vụ hoạt động độc lập trên cổng **8002** với cơ sở dữ liệu riêng biệt `database/orders.sqlite` gồm 2 bảng chuẩn theo Phụ lục đề bài: `orders` và `order_items`. Tích hợp xác thực với **Auth Service (Port 8000)** và kiểm tra tồn kho từ **Product Service (Port 8001)**.

---

## 🎯 Chức Năng Chính
- **Quản lý Đơn hàng (`orders`)**:
  - `GET /orders`: Lấy danh sách tất cả các đơn hàng (kèm danh sách chi tiết các mặt hàng `items`).
  - `GET /orders/{id}`: Lấy thông tin chi tiết một đơn hàng.
  - `POST /orders`: Tạo đơn hàng mới (kiểm tra tồn kho và khấu trừ tự động tại Product Service).
  - `PUT /orders/{id}`: Cập nhật trạng thái đơn hàng (`pending`, `completed`, `cancelled`).
  - `DELETE /orders/{id}`: Xóa đơn hàng (tự động hoàn kho nếu đơn chưa hủy).
- **Quản lý Chi tiết Mặt hàng (`order_items`)**:
  - `GET /order_items`: Lấy danh sách tất cả mặt hàng trong đơn hàng.
  - `GET /order_items/{id}`: Lấy thông tin chi tiết một mặt hàng.
  - `POST /order_items`: Tạo mặt hàng mới trong đơn hàng (kiểm tra và trừ kho).
  - `PUT /order_items/{id}`: Cập nhật số lượng mặt hàng (tự động bù trừ kho và tính lại tổng tiền đơn).
  - `DELETE /order_items/{id}`: Xóa một mặt hàng khỏi đơn hàng (hoàn kho lại Product Service).
- **Cơ chế Giao dịch Bù trừ (SOA Compensation)**: Tự động hoàn trả tồn kho về Product Service khi đơn hàng bị hủy hoặc xóa.
- **Giao diện Web tương tác trực quan (Demo UI)**: Tailwind CSS responsive với 2 tab chuyển đổi mượt mà.

---

## 🚀 Khởi Chạy Nhanh

### 1. Bật toàn bộ các dịch vụ
```powershell
.\start_services.ps1
```

### 2. Truy cập hệ thống
- **Web UI**: [http://localhost:8002](http://localhost:8002)
- **Tài khoản mẫu**: `student` / `123456`
- **Health Check**: [http://localhost:8002/health](http://localhost:8002/health)

### 3. Chạy kiểm thử tự động
```powershell
.\run_tests.ps1
# hoặc: php vendor/phpunit/phpunit/phpunit --testdox
```

---

## 📁 Tài Liệu Chi Tiết
- [Hướng dẫn chi tiết & Câu hỏi bảo vệ đồ án](HUONG_DAN.md)
- [Postman Collection](postman/Order-Service.postman_collection.json)
