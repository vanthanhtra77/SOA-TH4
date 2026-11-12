# BÀI THỰC HÀNH SỐ 4 - DỊCH VỤ QUẢN LÝ ĐƠN HÀNG (SOA)

Dự án triển khai theo kiến trúc hướng dịch vụ (**SOA - Service-Oriented Architecture**) bằng framework **Lumen 10 (PHP)**.

## 🚀 Khởi động nhanh (Khởi chạy đồng thời cả 3 Services)
```powershell
# Chạy đồng thời Auth (Port 8000), Product (Port 8001) và Order (Port 8002)
.\start_services.ps1
```

- **Order Service Web UI**: `http://localhost:8002`
- **Product Service (Bài 3)**: `http://localhost:8001`
- **Auth Service (Bài 2)**: `http://localhost:8000`
- **Tài khoản đăng nhập demo**: `student` / `123456`

## 🧪 Kiểm thử tự động (PHPUnit Tests)
```powershell
php vendor/phpunit/phpunit/phpunit
```
> Kết quả: `OK (13 tests, 86 assertions)` đạt 100% assertions thành công.

## 📂 Tài liệu chi tiết
Xem file [HUONG_DAN.md](HUONG_DAN.md) để xem:
- Đặc tả API RESTful đầy đủ
- Schema cơ sở dữ liệu `orders`
- Cơ chế kiểm tra tồn kho & bù trừ kho phân tán (Distributed Stock Management)
- Hướng dẫn kiểm thử Postman (`postman/Order-Service.postman_collection.json`)
- Bộ câu hỏi & câu trả lời phản biện với giảng viên
