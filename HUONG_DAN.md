# HƯỚNG DẪN CHI TIẾT & BẢO VỆ BÀI THỰC HÀNH SỐ 4 (SOA)
## DỊCH VỤ QUẢN LÝ ĐƠN HÀNG (ORDER MANAGEMENT SERVICE)

---

## 1. TỔNG QUAN ĐỀ BÀI VÀ MỤC TIÊU
- **Mục tiêu**:
  - Xây dựng dịch vụ quản lý đơn hàng (tạo, xem, cập nhật, xóa đơn hàng và chi tiết sản phẩm trong đơn).
  - Bao gồm các chức năng tạo đơn hàng, cập nhật trạng thái đơn hàng, và kiểm tra số lượng sản phẩm từ dịch vụ quản lý sản phẩm (Product Service).
  - Dịch vụ hoạt động độc lập trên một cổng (`8002`) và cơ sở dữ liệu riêng biệt (`orders.sqlite`) tuân theo nguyên tắc của kiến trúc SOA.
  - Xác thực qua dịch vụ đã xây dựng ở bài thực hành số 2 (Auth Service - Port `8000`).

---

## 2. KIẾN TRÚC CƠ SỞ DỮ LIỆU RIÊNG (PHỤ LỤC ĐỀ BÀI)

Cơ sở dữ liệu độc lập `database/orders.sqlite` gồm 2 bảng chuẩn theo đúng Phụ lục:

### Bảng 1: Bảng đơn hàng (`orders`)
| Tên cột | Kiểu dữ liệu | Mô tả |
| :--- | :--- | :--- |
| `id` | INT (PRIMARY KEY) | ID duy nhất của đơn hàng |
| `customer_name` | VARCHAR(255) | Tên khách hàng |
| `customer_email` | VARCHAR(255) | Email khách hàng |
| `total_amount` | DECIMAL(10, 2) | Tổng số tiền của đơn hàng |
| `status` | VARCHAR(50) | Trạng thái đơn hàng (`pending`, `completed`, `cancelled`) |
| `created_at` | TIMESTAMP | Ngày đơn hàng được tạo |
| `updated_at` | TIMESTAMP | Ngày đơn hàng được cập nhật lần cuối |

### Bảng 2: Bảng chi tiết đơn hàng (`order_items`)
| Tên cột | Kiểu dữ liệu | Mô tả |
| :--- | :--- | :--- |
| `id` | INT (PRIMARY KEY) | ID duy nhất của chi tiết đơn hàng |
| `order_id` | INT | ID đơn hàng (liên kết với bảng `orders`) |
| `product_id` | INT | ID sản phẩm (lưu vết để liên kết với dịch vụ quản lý sản phẩm) |
| `product_name` | VARCHAR(255) | Tên sản phẩm |
| `quantity` | INT | Số lượng sản phẩm trong đơn hàng |
| `unit_price` | DECIMAL(10, 2) | Giá sản phẩm tại thời điểm đặt hàng |
| `total_price` | DECIMAL(10, 2) | Tổng giá trị cho từng sản phẩm (`quantity * unit_price`) |
| `created_at` | TIMESTAMP | Ngày tạo mặt hàng |
| `updated_at` | TIMESTAMP | Ngày cập nhật mặt hàng |

---

## 3. DANH MỤC RESTFUL API HOÀN CHỈNH (10 ENDPOINTS)

### Nhóm 1: Quản lý Đơn hàng (`/orders`)
1. `GET /orders`: Lấy danh sách tất cả các đơn hàng (kèm danh sách chi tiết các mặt hàng `items`).
2. `GET /orders/{id}`: Lấy thông tin chi tiết một đơn hàng theo ID.
3. `POST /orders`: Tạo đơn hàng mới (kiểm tra tồn kho và tự động trừ kho tại Product Service).
4. `PUT /orders/{id}`: Cập nhật trạng thái đơn hàng (`pending`, `completed`, `cancelled`). Khi chuyển sang `cancelled`, hệ thống tự động hoàn trả số lượng vào kho của Product Service.
5. `DELETE /orders/{id}`: Xóa một đơn hàng (tự động hoàn kho nếu đơn chưa hủy).

### Nhóm 2: Quản lý Chi tiết Mặt hàng (`/order_items`)
6. `GET /order_items`: Lấy danh sách tất cả mặt hàng trong đơn hàng (hỗ trợ lọc theo `?order_id=...`).
7. `GET /order_items/{id}`: Lấy thông tin chi tiết một mặt hàng.
8. `POST /order_items`: Tạo mặt hàng mới trong đơn hàng (kiểm tra và trừ kho tại Product Service, tự động tính lại tổng tiền đơn hàng).
9. `PUT /order_items/{id}`: Cập nhật số lượng mặt hàng (tự động bù trừ kho với Product Service và tính lại tổng tiền đơn).
10. `DELETE /order_items/{id}`: Xóa một mặt hàng trong đơn hàng (hoàn kho cho Product Service và cập nhật lại tổng tiền đơn).

---

## 4. QUY TRÌNH GIAO TIẾP LIÊN DỊCH VỤ (SOA DATA FLOW)

```
[Client / Postman]
       |
       | 1. Gửi request kèm Header: Authorization: Bearer <token>
       v
[Order Service - Port 8002]
       |
       | 2. Giao tiếp Service 1 (Auth Service - Port 8000)
       |    GET /auth -> Xác minh Token hợp lệ & lấy thông tin user
       v
[Order Service - Port 8002]
       |
       | 3. Giao tiếp Service 2 (Product Service - Port 8001)
       |    GET /products/{id} -> Kiểm tra tồn kho (available stock >= requested quantity)
       |    PUT /products/{id} -> Khấu trừ số lượng tồn kho (quantity = current - requested)
       v
[Order Service CSDL Riêng: database/orders.sqlite]
       | 4. Lưu bản ghi vào bảng orders & bảng order_items
       v
[Client / Postman]: Nhận phản hồi HTTP 201 Created
```

---

## 5. HƯỚNG DẪN KIỂM THỬ

### 5.1. Khởi chạy hệ thống
Chạy script PowerShell tại thư mục `D:\SOA\TH4`:
```powershell
.\start_services.ps1
```

### 5.2. Chạy bộ kiểm thử tự động (PHPUnit Test Suite)
```powershell
.\run_tests.ps1
# hoặc: php vendor/phpunit/phpunit/phpunit --testdox
```
**Kết quả đạt được:** `OK (18 tests, 154 assertions)` — **100% Passed**.

### 5.3. Trải nghiệm giao diện Web trực quan
Mở trình duyệt truy cập: **`http://localhost:8002`**
- Tab 1: Quản lý Đơn hàng (`/orders`)
- Tab 2: Quản lý Chi tiết Mặt hàng (`/order_items`)
- Console Log thời gian thực và kịch bản thử nghiệm lỗi tự động.

---

## 6. CÂU HỎI VẤN ĐÁP BẢO VỆ VỚI GIẢNG VIÊN

**Câu hỏi 1: Tại sao dịch vụ đơn hàng lại cần 2 bảng `orders` và `order_items`?**
> **Trả lời:** Theo chuẩn thiết kế hệ thống thương mại điện tử và chuẩn hóa CSDL (1NF, 2NF, 3NF), một đơn hàng có thể chứa nhiều mặt hàng khác nhau với số lượng và đơn giá tại thời điểm mua khác nhau. Bảng `orders` lưu thông tin chung của đơn (khách hàng, tổng tiền, trạng thái), còn bảng `order_items` lưu chi tiết từng sản phẩm được đặt mua.

**Câu hỏi 2: Làm thế nào để đảm bảo tính nhất quán dữ liệu khi trừ kho ở Product Service?**
> **Trả lời:** Theo nguyên tắc SOA, hai dịch vụ không chia sẻ database nên không thể dùng Database Transaction cục bộ. Hệ thống áp dụng mẫu **Compensating Transaction (Giao dịch bù trừ)**:
> 1. Khi tạo đơn hoặc thêm item: Order Service kiểm tra kho trước, nếu đủ mới trừ kho và lưu đơn.
> 2. Nếu việc trừ kho thất bại giữa chừng, hệ thống hoàn tác lại các mặt hàng đã trừ trước đó.
> 3. Khi đơn hàng bị hủy (`status = cancelled`) hoặc xóa, Order Service tự động gọi ngược lại Product Service để hoàn trả lại số lượng tồn kho (`restoreStock`).
