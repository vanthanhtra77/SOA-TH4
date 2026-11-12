# BÁO CÁO & HƯỚNG DẪN THỰC HIỆN BÀI THỰC HÀNH SỐ 4
## XÂY DỰNG DỊCH VỤ QUẢN LÝ ĐƠN HÀNG (ORDER MANAGEMENT SERVICE) THEO KIẾN TRÚC SOA

---

### I. MỤC TIÊU VÀ NGUYÊN TẮC KIẾN TRÚC SOA

Bài thực hành số 4 yêu cầu xây dựng dịch vụ Quản lý đơn hàng (Order Service) hoạt động độc lập và tương tác với các dịch vụ trước đó để hình thành một **Hệ sinh thái SOA hoàn chỉnh (3 Services)**:

```
                            [Client / Postman / Web UI]
                                         |
               +-------------------------+-------------------------+
               | (1. Đăng nhập)          | (2. CRUD Sản phẩm)      | (3. Đặt hàng)
               v                         v                         v
     +-------------------+     +-------------------+     +-------------------+
     |   Auth Service    |     |  Product Service  |     |   Order Service   |
     |  (Bài 2 - 8000)   |     |  (Bài 3 - 8001)   |     |  (Bài 4 - 8002)   |
     +-------------------+     +-------------------+     +-------------------+
               ^                         ^                         |
               |                         | (Kiểm tra & trừ tồn kho)|
               +-------------------------+-------------------------+
                           (Xác thực Token JWT)
```

1. **Tính độc lập dịch vụ (Service Autonomy & Loose Coupling):**
   - **Auth Service:** Cổng `8000` (`http://localhost:8000`)
   - **Product Service:** Cổng `8001` (`http://localhost:8001`)
   - **Order Service:** Cổng `8002` (`http://localhost:8002`)
2. **Cơ sở dữ liệu độc lập (Database per Service pattern):**
   - Auth Service: Database riêng chứa bảng `users`.
   - Product Service: Database riêng chứa bảng `products`.
   - Order Service: Database riêng chứa bảng `orders`.
3. **Luồng nghiệp vụ liên dịch vụ (Inter-Service Orchestration):**
   - **Xác thực:** Order Service thẩm định JWT token của người dùng qua Auth Service (`GET http://localhost:8000/auth`).
   - **Kiểm tra tồn kho (Stock Check):** Khi tạo đơn hàng, Order Service gọi Product Service (`GET http://localhost:8001/products/{id}`) để kiểm tra:
     - Sản phẩm có tồn tại không.
     - Số lượng tồn kho (`quantity`) có đủ đáp ứng số lượng khách đặt không.
   - **Cập nhật trừ kho (Stock Deduction):** Khi đơn hàng hợp lệ, gọi `PUT http://localhost:8001/products/{id}` để trừ số lượng tồn kho tương ứng.
   - **Hoàn trả kho (Stock Compensation):** Khi đơn hàng bị hủy hoặc xóa, Order Service tự động gọi Product Service để cộng hoàn trả lại số lượng vào kho.

---

### II. CƠ SỞ DỮ LIỆU BẢNG `orders`

| Tên cột | Kiểu dữ liệu | Ràng buộc | Mô tả |
| :--- | :--- | :--- | :--- |
| `id` | `INT` | PRIMARY KEY, AUTO_INCREMENT | Mã định danh duy nhất của đơn hàng |
| `user_id` | `INT` | NOT NULL | ID khách hàng đặt đơn (trích xuất từ JWT) |
| `user_name` | `VARCHAR(255)` | NULLABLE | Tên đăng nhập của khách hàng |
| `product_id` | `INT` | NOT NULL | ID sản phẩm được đặt (từ Product Service) |
| `product_name` | `VARCHAR(255)` | NOT NULL | Tên sản phẩm tại thời điểm tạo đơn |
| `quantity` | `INT` | NOT NULL | Số lượng sản phẩm khách đặt |
| `unit_price` | `DECIMAL(10, 2)` | NOT NULL | Đơn giá sản phẩm tại thời điểm đặt |
| `total_price` | `DECIMAL(12, 2)` | NOT NULL | Tổng tiền đơn hàng (`quantity * unit_price`) |
| `status` | `VARCHAR(50)` | DEFAULT `'pending'` | Trạng thái: `pending`, `confirmed`, `shipping`, `completed`, `cancelled` |
| `receiver_name` | `VARCHAR(255)` | NULLABLE | Họ tên người nhận hàng |
| `receiver_phone` | `VARCHAR(50)` | NULLABLE | Số điện thoại liên hệ |
| `shipping_address`| `TEXT` | NULLABLE | Địa chỉ giao hàng |
| `notes` | `TEXT` | NULLABLE | Ghi chú đơn hàng |
| `created_at` | `TIMESTAMP` | NULLABLE | Thời điểm tạo đơn hàng |
| `updated_at` | `TIMESTAMP` | NULLABLE | Thời điểm cập nhật đơn hàng |

File migration: `database/migrations/2026_11_12_000000_create_orders_table.php`

---

### III. DANH SÁCH RESTFUL API ENDPOINTS (ORDER SERVICE - PORT 8002)

Base URL: `http://localhost:8002`  
Header yêu cầu: `Authorization: Bearer <token>` và `Accept: application/json`

| STT | Phương thức | Endpoint | Chức năng | Body mẫu (JSON) | Mã HTTP |
| :---: | :---: | :--- | :--- | :--- | :---: |
| 1 | `GET` | `/orders` | Lấy danh sách tất cả các đơn hàng (có lọc `status`, `user_id`, `product_id`) | Không | `200 OK` |
| 2 | `GET` | `/orders/{id}` | Xem thông tin chi tiết một đơn hàng theo ID | Không | `200 OK` / `404 Not Found` |
| 3 | `POST` | `/orders` | Tạo đơn hàng mới (tự động kiểm tra & trừ kho tại Product Service) | `{"product_id": 1, "quantity": 2, "receiver_name": "Nguyen Van A", "receiver_phone": "0901234567"}` | `201 Created` / `422 Unprocessable` |
| 4 | `PUT` | `/orders/{id}` | Cập nhật thông tin / trạng thái đơn hàng (nếu `cancelled` tự động hoàn kho) | `{"status": "confirmed"}` | `200 OK` / `404 Not Found` |
| 5 | `DELETE` | `/orders/{id}` | Xóa đơn hàng (tự động hoàn kho nếu đơn chưa hoàn tất) | Không | `200 OK` / `404 Not Found` |
| 6 | `GET` | `/health` | Kiểm tra trạng thái sức khỏe Order Service & các dịch vụ liên kết | Không | `200 OK` |
| 7 | `GET` | `/` | Giao diện Web tương tác trực quan (Demo UI) | Không | `200 OK` (HTML) |

---

### IV. HƯỚNG DẪN KHỞI CHẠY HỆ THỐNG

#### Cách 1: Khởi động tự động bằng Script 1-Click (Khuyên dùng)
Mở PowerShell tại thư mục `D:\SOA\TH4` và chạy:
```powershell
.\start_services.ps1
```
Script sẽ tự động:
1. Chuẩn bị database và migrate cho cả 3 Services.
2. Khởi chạy đồng thời:
   - **Auth Service:** `http://localhost:8000`
   - **Product Service:** `http://localhost:8001`
   - **Order Service:** `http://localhost:8002`
3. Mở ngay trình duyệt web để thao tác kiểm thử trực quan.

#### Cách 2: Khởi động thủ công bằng dòng lệnh

- **Terminal 1 (Auth Service):**
  ```powershell
  cd D:\SOA\th2\SOATh2-main\SOATh2-main
  php -S 127.0.0.1:8000 -t public
  ```
- **Terminal 2 (Product Service):**
  ```powershell
  cd D:\SOA\TH3
  php -S 127.0.0.1:8001 -t public
  ```
- **Terminal 3 (Order Service):**
  ```powershell
  cd D:\SOA\TH4
  php -S 127.0.0.1:8002 -t public
  ```

---

### V. GIAO DIỆN WEB DEMO TRỰC QUAN (`http://localhost:8002/`)

Mở trình duyệt truy cập: **[http://localhost:8002](http://localhost:8002)**
- **Xác thực:** Đăng nhập 1-click với tài khoản `student / 123456`, token tự động được đồng bộ.
- **Xem kho hàng thời gian thực:** Hiển thị danh sách sản phẩm lấy từ Product Service (Port 8001) với số lượng tồn kho cập nhật ngay khi đặt hàng.
- **Tạo đơn hàng:** Chọn sản phẩm, số lượng, giao diện hiển thị ngay tồn kho. Hệ thống tự động chặn nếu đặt vượt tồn kho.
- **Quản lý đơn hàng:** Danh sách bảng, đổi trạng thái (Duyệt đơn, Đang giao, Hoàn tất, Hủy đơn).
- **Cơ chế hoàn kho tự động:** Khi bấm **Hủy đơn**, sản phẩm được tự động cộng trả lại vào kho của Product Service.
- **Console theo dõi:** Khung log hiển thị chi tiết method HTTP, URL gọi sang các service và kết quả JSON trả về.

---

### VI. KIỂM THỬ TỰ ĐỘNG (PHPUNIT & POSTMAN)

#### 1. Kiểm thử PHPUnit (Automated Tests)
Chạy lệnh kiểm thử tự động tại thư mục `D:\SOA\TH4`:
```powershell
php vendor/phpunit/phpunit/phpunit
# Hoặc chạy script:
.\run_tests.ps1
```
> **Kết quả:** `OK (13 tests, 86 assertions)` — Đạt **100% assertions** thành công.

#### 2. Kiểm thử bằng Postman
- Mở Postman -> Chọn **Import**.
- Chọn file: `D:\SOA\TH4\postman\Order-Service.postman_collection.json`.
- Chạy lần lượt các kịch bản:
  1. Đăng nhập lấy Token
  2. Kiểm tra tồn kho tại Product Service
  3. Tạo đơn hàng và kiểm tra kho bị trừ
  4. Cập nhật trạng thái / Hủy đơn và kiểm tra kho được hoàn lại
  5. Các kịch bản lỗi: 401 (thiếu token), 422 (vượt tồn kho), 404 (sản phẩm không tồn tại).

---

### VII. CÂU HỎI PHẢN BIỆN VẤN ĐÁP THƯỜNG GẶP (HỖ TRỢ TRẢ LỜI GIẢNG VIÊN)

**Câu 1: Trong kiến trúc SOA, tại sao Order Service không truy vấn trực tiếp vào Database của Product Service để lấy và trừ số lượng sản phẩm?**
> *Trả lời:* Đây là nguyên tắc cốt lõi **Database per Service** và **Loose Coupling (Ràng buộc lỏng)** của SOA. Nếu Order Service chọc thẳng vào database của Product Service, hai dịch vụ sẽ bị ràng buộc chặt chẽ vào cấu trúc bảng (schema) của nhau. Khi Product Service thay đổi cấu trúc bảng, Order Service sẽ bị hỏng. Trong SOA, mọi tương tác dữ liệu giữa các dịch vụ bắt buộc phải thông qua giao diện chuẩn (API Contract) qua HTTP RESTful.

**Câu 2: Làm thế nào để giải quyết bài toán giao dịch phân tán (Distributed Transaction) khi trừ kho ở Product Service và tạo đơn ở Order Service?**
> *Trả lời:* Trong kiến trúc phân tán không thể dùng ACID Transaction của một cơ sở dữ liệu duy nhất, mà áp dụng mẫu thiết kế **Saga Pattern / Compensation (Giao dịch bù)**:
> 1. Order Service kiểm tra tồn kho và gọi Product Service trừ kho trước.
> 2. Sau đó Order Service lưu bản ghi đơn hàng.
> 3. Nếu việc lưu đơn hàng thất bại, hoặc sau đó đơn hàng bị **Hủy (Cancelled)**, Order Service sẽ kích hoạt hành động bù (**Compensating Action**) bằng cách gọi `restoreStock` sang Product Service để cộng trả lại số lượng tồn kho.

**Câu 3: Nếu dịch vụ Product Service bị treo hoặc mất mạng thì Order Service xử lý như thế nào?**
> *Trả lời:*
> - Client HTTP của Order Service được cấu hình **Timeout** (5 giây) để không làm tắc nghẽn luồng xử lý của hệ thống.
> - Khi không kết nối được Product Service, Order Service sẽ chặn yêu cầu tạo đơn và phản hồi mã lỗi `HTTP 503 Service Unavailable` kèm thông báo rõ ràng cho người dùng, thay vì để đơn hàng bị lỗi dữ liệu chập chờn.
