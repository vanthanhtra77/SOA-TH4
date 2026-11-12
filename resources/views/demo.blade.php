<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BÀI THỰC HÀNH SỐ 4 - Dịch vụ Quản lý Đơn hàng (SOA)</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .code-font { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">

    <!-- Top Header -->
    <header class="bg-slate-900 text-white shadow-lg border-b border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <span class="px-2.5 py-1 bg-indigo-700 text-indigo-100 rounded text-xs font-semibold uppercase tracking-wider">BÀI THỰC HÀNH SỐ 4</span>
                        <span class="px-2.5 py-1 bg-emerald-600 text-white rounded text-xs font-semibold">SOA Architecture</span>
                        <span class="px-2.5 py-1 bg-sky-600 text-white rounded text-xs font-semibold">Port 8002</span>
                    </div>
                    <h1 class="text-2xl font-bold mt-2">Dịch Vụ Quản Lý Đơn Hàng (Order Management)</h1>
                    <p class="text-slate-300 text-sm mt-1">
                        Cơ sở dữ liệu riêng • Kiểm tra tồn kho từ <strong>Product Service (Port 8001)</strong> • Xác thực qua <strong>Auth Service (Port 8000)</strong>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/health" target="_blank" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded text-xs font-medium text-slate-200 border border-slate-700 transition">
                        <i class="fa-solid fa-heart-pulse mr-1 text-emerald-400"></i> Service Health
                    </a>
                    <a href="#console-section" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 rounded text-xs font-medium text-slate-200 border border-slate-700 transition">
                        <i class="fa-solid fa-terminal mr-1 text-amber-400"></i> API Log Console
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- 1. SOA Ecosystem Status Bar -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 font-medium">Service 1: Xác Thực (Bài 2)</div>
                        <div class="text-sm font-bold text-slate-800">Port 8000 (Auth Service)</div>
                    </div>
                </div>
                <span id="badge-auth-service" class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold">Chưa kiểm tra</span>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-boxes-stacked"></i>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 font-medium">Service 2: Sản Phẩm (Bài 3)</div>
                        <div class="text-sm font-bold text-slate-800">Port 8001 (Product Service)</div>
                    </div>
                </div>
                <span id="badge-product-service" class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-semibold">Chưa kiểm tra</span>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <div>
                        <div class="text-xs text-slate-500 font-medium">Service 3: Đơn Hàng (Bài 4)</div>
                        <div class="text-sm font-bold text-slate-800">Port 8002 (Order Service)</div>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold">Đang Chạy (UP)</span>
            </div>
        </section>

        <!-- 2. Authentication & Live Stock Viewer -->
        <section class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Form Auth Service -->
            <div class="lg:col-span-6 bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <h2 class="text-base font-bold text-slate-800 flex items-center">
                            <i class="fa-solid fa-shield-halved text-indigo-600 mr-2"></i> 1. Xác thực người dùng (Auth Service - Port 8000)
                        </h2>
                        <span id="auth-status-badge" class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                            Chưa có Token
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">UserName</label>
                            <input type="text" id="auth-username" value="student" class="w-full px-3 py-1.5 text-sm bg-slate-50 border border-slate-300 rounded focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 mb-1">Mật khẩu</label>
                            <input type="password" id="auth-password" value="123456" class="w-full px-3 py-1.5 text-sm bg-slate-50 border border-slate-300 rounded focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex gap-2 mb-3">
                        <button onclick="loginAuthService()" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded text-xs font-semibold shadow-sm transition flex items-center justify-center gap-1.5">
                            <i class="fa-solid fa-right-to-bracket"></i> Đăng Nhập Service 2
                        </button>
                        <button onclick="clearToken()" class="bg-rose-50 hover:bg-rose-100 text-rose-600 border border-rose-200 px-3 py-2 rounded text-xs font-semibold transition" title="Xóa Token">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>

                    <div>
                        <div class="flex items-center justify-between text-xs text-slate-500 mb-1">
                            <span>Bearer Token hiện hành:</span>
                            <span id="user-info-text">Chưa xác thực</span>
                        </div>
                        <textarea id="current-token" rows="2" readonly placeholder="Chưa có token. Bấm 'Đăng Nhập Service 2' ở trên..." class="w-full text-xs code-font bg-slate-50 border border-slate-300 rounded p-2 text-slate-700 resize-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Live Products & Stock Viewer -->
            <div class="lg:col-span-6 bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                        <h2 class="text-base font-bold text-slate-800 flex items-center">
                            <i class="fa-solid fa-warehouse text-emerald-600 mr-2"></i> 2. Tồn kho thời gian thực (Product Service - Port 8001)
                        </h2>
                        <button onclick="loadProductsStock()" class="text-xs text-emerald-600 hover:text-emerald-700 font-semibold flex items-center gap-1">
                            <i class="fa-solid fa-rotate"></i> Làm mới tồn kho
                        </button>
                    </div>

                    <div id="product-stock-list" class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <div class="text-xs text-center text-slate-400 py-6">
                            <i class="fa-solid fa-spinner fa-spin mr-1"></i> Đang tải dữ liệu tồn kho từ Product Service...
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 text-xs text-slate-500 flex justify-between items-center">
                    <span>* Khi tạo đơn hàng, Order Service sẽ tự động trừ số lượng tồn kho này.</span>
                </div>
            </div>
        </section>

        <!-- 3. Create Order & Manage Orders Section -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between border-b border-slate-100 pb-4 mb-5 gap-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold text-lg">
                        <i class="fa-solid fa-receipt"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">3. Quản Lý Đơn Hàng (RESTful API Orders)</h2>
                        <p class="text-xs text-slate-500">Cơ sở dữ liệu riêng: bảng <code class="text-sky-700 code-font">orders</code> (id, user_id, product_id, quantity, total_price, status...)</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button onclick="loadOrders()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 border border-slate-300">
                        <i class="fa-solid fa-rotate"></i> Tải lại danh sách
                    </button>
                    <button onclick="openCreateOrderModal()" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-cart-plus"></i> Tạo Đơn Hàng Mới
                    </button>
                </div>
            </div>

            <!-- Filters & Fast Test Scenarios -->
            <div class="mb-5 p-3 bg-slate-50 border border-slate-200 rounded-lg flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-slate-700"><i class="fa-solid fa-filter mr-1"></i> Lọc trạng thái:</span>
                    <select id="filter-status" onchange="loadOrders()" class="bg-white border border-slate-300 rounded px-2.5 py-1 text-xs focus:ring-1 focus:ring-sky-500 focus:outline-none">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending">Chờ xác nhận (pending)</option>
                        <option value="confirmed">Đã xác nhận (confirmed)</option>
                        <option value="shipping">Đang giao hàng (shipping)</option>
                        <option value="completed">Đã hoàn tất (completed)</option>
                        <option value="cancelled">Đã hủy (cancelled)</option>
                    </select>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-semibold text-amber-800"><i class="fa-solid fa-vial mr-1"></i> Kịch bản SOA:</span>
                    <button onclick="testWithoutToken()" class="px-2.5 py-1 bg-white hover:bg-amber-50 text-amber-800 border border-amber-300 rounded font-medium transition">
                        1. Gọi không có Token (401)
                    </button>
                    <button onclick="testExceedStockOrder()" class="px-2.5 py-1 bg-white hover:bg-amber-50 text-amber-800 border border-amber-300 rounded font-medium transition">
                        2. Đặt vượt tồn kho (422)
                    </button>
                    <button onclick="testNonexistentProductOrder()" class="px-2.5 py-1 bg-white hover:bg-amber-50 text-amber-800 border border-amber-300 rounded font-medium transition">
                        3. SP không tồn tại (404)
                    </button>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-100 text-slate-700 font-semibold text-xs">
                        <tr>
                            <th class="py-3 px-4 text-left w-16">ID Đơn</th>
                            <th class="py-3 px-4 text-left">Khách hàng</th>
                            <th class="py-3 px-4 text-left">Sản phẩm</th>
                            <th class="py-3 px-4 text-center">SL</th>
                            <th class="py-3 px-4 text-right">Đơn giá</th>
                            <th class="py-3 px-4 text-right">Tổng tiền</th>
                            <th class="py-3 px-4 text-center">Trạng thái</th>
                            <th class="py-3 px-4 text-center">Thời gian</th>
                            <th class="py-3 px-4 text-center w-40">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body" class="divide-y divide-slate-200 bg-white">
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">
                                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Đang tải danh sách đơn hàng...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex items-center justify-between text-xs text-slate-500">
                <span id="order-count-summary">Hiển thị 0 đơn hàng</span>
                <span>RESTful APIs: <code class="code-font">GET /orders</code>, <code class="code-font">POST /orders</code>, <code class="code-font">PUT /orders/{id}</code>, <code class="code-font">DELETE /orders/{id}</code></span>
            </div>
        </section>

        <!-- 4. Real-time API Monitor Console -->
        <section id="console-section" class="bg-slate-900 rounded-xl shadow-lg border border-slate-800 p-6 text-slate-100">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3 mb-4">
                <div class="flex items-center space-x-2">
                    <span class="w-3 h-3 rounded-full bg-rose-500 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                    <span class="w-3 h-3 rounded-full bg-emerald-500 inline-block"></span>
                    <span class="text-xs font-semibold text-slate-400 ml-2">RESTful API Request / Response Monitor (SOA Inter-Service Tracing)</span>
                </div>
                <div class="flex items-center gap-2">
                    <span id="api-method-badge" class="px-2 py-0.5 rounded text-xs font-bold bg-slate-800 text-slate-300">IDLE</span>
                    <span id="api-status-badge" class="px-2 py-0.5 rounded text-xs font-bold bg-slate-800 text-slate-300">---</span>
                </div>
            </div>
            <div class="text-xs code-font text-slate-400 mb-2 truncate">
                <span class="text-slate-500">Target Endpoint: </span>
                <span id="api-url-text" class="text-sky-400">Chưa có request nào</span>
            </div>
            <pre id="api-response-body" class="p-4 rounded-lg bg-slate-950 text-emerald-400 code-font text-xs overflow-x-auto max-h-80 border border-slate-800 leading-relaxed">// JSON Response sẽ xuất hiện tại đây khi bạn thao tác với giao diện hoặc gọi API...</pre>
        </section>

    </main>

    <!-- Modal Create Order -->
    <div id="order-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-cart-plus text-sky-600"></i> Tạo Đơn Hàng Mới (POST /orders)
                </h3>
                <button onclick="closeOrderModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form onsubmit="handleCreateOrder(event)" class="p-6 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Chọn sản phẩm <span class="text-rose-500">*</span> (Lấy trực tiếp từ Product Service Port 8001)
                    </label>
                    <select id="form-order-product" required onchange="onProductSelectChange()" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none">
                        <option value="">-- Đang nạp danh sách sản phẩm... --</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Số lượng mua <span class="text-rose-500">*</span></label>
                        <input type="number" id="form-order-quantity" min="1" value="1" required class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tồn kho hiện tại</label>
                        <input type="text" id="form-current-stock-display" readonly value="---" class="w-full px-3 py-2 text-sm bg-slate-100 border border-slate-300 rounded-lg font-bold text-slate-600">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tên người nhận</label>
                    <input type="text" id="form-receiver-name" placeholder="Ví dụ: Nguyễn Văn A" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Số điện thoại</label>
                    <input type="text" id="form-receiver-phone" placeholder="Ví dụ: 0901234567" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Địa chỉ giao hàng</label>
                    <textarea id="form-shipping-address" rows="2" placeholder="Nhập địa chỉ nhận hàng..." class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:outline-none"></textarea>
                </div>

                <div class="pt-3 flex items-center justify-end gap-3 border-t border-slate-100">
                    <button type="button" onclick="closeOrderModal()" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg transition">
                        Hủy
                    </button>
                    <button type="submit" class="px-5 py-2 text-xs font-semibold text-white bg-sky-600 hover:bg-sky-700 rounded-lg shadow-sm transition">
                        Xác Nhận Đặt Hàng
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal View Order Detail -->
    <div id="detail-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full overflow-hidden border border-slate-200">
            <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-base flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-sky-600"></i> Chi Tiết Đơn Hàng
                </h3>
                <button onclick="closeDetailModal()" class="text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <div id="detail-modal-content" class="p-6 space-y-4 text-sm">
                <!-- Nội dung được nạp qua JS -->
            </div>
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 text-right">
                <button onclick="closeDetailModal()" class="px-4 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold rounded-lg transition">Đóng</button>
            </div>
        </div>
    </div>

    <!-- JavaScript Application Logic -->
    <script>
        const AUTH_BASE_URL = 'http://localhost:8000';
        const PRODUCT_BASE_URL = 'http://localhost:8001';
        const ORDER_BASE_URL = 'http://localhost:8002';

        let currentToken = localStorage.getItem('soa_jwt_token') || '';
        let availableProducts = [];

        document.addEventListener('DOMContentLoaded', () => {
            if (currentToken) {
                document.getElementById('current-token').value = currentToken;
                updateAuthStatus(true, 'Token sẵn sàng');
            } else {
                updateAuthStatus(false, 'Chưa có Token');
            }
            checkServicesStatus();
            loadProductsStock();
            loadOrders();
        });

        function updateAuthStatus(isValid, label) {
            const badge = document.getElementById('auth-status-badge');
            if (isValid) {
                badge.className = 'px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300';
                badge.innerHTML = '<i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i> ' + label;
            } else {
                badge.className = 'px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-300';
                badge.innerHTML = '<i class="fa-solid fa-circle-xmark text-rose-600 mr-1"></i> ' + label;
            }
        }

        async function checkServicesStatus() {
            // Check Auth Service
            try {
                const res = await fetch(`${AUTH_BASE_URL}/`, { method: 'GET' });
                document.getElementById('badge-auth-service').className = 'px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold';
                document.getElementById('badge-auth-service').textContent = 'Đang Chạy (UP)';
            } catch (e) {
                document.getElementById('badge-auth-service').className = 'px-2.5 py-1 bg-rose-100 text-rose-800 rounded-full text-xs font-semibold';
                document.getElementById('badge-auth-service').textContent = 'Không phản hồi';
            }

            // Check Product Service
            try {
                const res = await fetch(`${PRODUCT_BASE_URL}/health`, { method: 'GET' });
                if (res.ok) {
                    document.getElementById('badge-product-service').className = 'px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-semibold';
                    document.getElementById('badge-product-service').textContent = 'Đang Chạy (UP)';
                }
            } catch (e) {
                document.getElementById('badge-product-service').className = 'px-2.5 py-1 bg-rose-100 text-rose-800 rounded-full text-xs font-semibold';
                document.getElementById('badge-product-service').textContent = 'Không phản hồi';
            }
        }

        function logConsole(method, url, status, data) {
            const methodBadge = document.getElementById('api-method-badge');
            const statusBadge = document.getElementById('api-status-badge');
            const urlText = document.getElementById('api-url-text');
            const bodyEl = document.getElementById('api-response-body');

            methodBadge.textContent = method;
            methodBadge.className = 'px-2 py-0.5 rounded text-xs font-bold ' + 
                (method === 'GET' ? 'bg-sky-700 text-sky-100' : 
                 method === 'POST' ? 'bg-emerald-700 text-emerald-100' :
                 method === 'PUT' ? 'bg-amber-700 text-amber-100' : 'bg-rose-700 text-rose-100');

            statusBadge.textContent = status + (status >= 200 && status < 300 ? ' OK' : ' ERROR');
            statusBadge.className = 'px-2 py-0.5 rounded text-xs font-bold ' +
                (status >= 200 && status < 300 ? 'bg-emerald-700 text-emerald-100' : 'bg-rose-700 text-rose-100');

            urlText.textContent = url;
            bodyEl.textContent = JSON.stringify(data, null, 2);
        }

        // 1. Đăng nhập Auth Service
        async function loginAuthService() {
            const userName = document.getElementById('auth-username').value.trim();
            const rawPassword = document.getElementById('auth-password').value;
            const base64Password = btoa(rawPassword);

            try {
                const response = await fetch(`${AUTH_BASE_URL}/login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ userName: userName, password: base64Password })
                });

                const data = await response.json();
                logConsole('POST', `${AUTH_BASE_URL}/login`, response.status, data);

                if (response.ok && data.access_token) {
                    currentToken = data.access_token;
                    localStorage.setItem('soa_jwt_token', currentToken);
                    document.getElementById('current-token').value = currentToken;
                    document.getElementById('user-info-text').textContent = `${data.user?.UserName || userName} (ID: ${data.user?.IdUser || 1})`;
                    updateAuthStatus(true, 'Đã xác thực JWT');
                    alert('Đăng nhập thành công qua Auth Service (Port 8000)!');
                    loadProductsStock();
                    loadOrders();
                } else {
                    updateAuthStatus(false, 'Đăng nhập thất bại');
                    alert('Đăng nhập thất bại: ' + (data.message || 'Lỗi không xác định'));
                }
            } catch (err) {
                logConsole('POST', `${AUTH_BASE_URL}/login`, 0, { error: err.message });
                alert('Không thể kết nối đến Auth Service tại ' + AUTH_BASE_URL);
            }
        }

        function clearToken() {
            currentToken = '';
            localStorage.removeItem('soa_jwt_token');
            document.getElementById('current-token').value = '';
            document.getElementById('user-info-text').textContent = 'Chưa xác thực';
            updateAuthStatus(false, 'Chưa có Token');
            alert('Đã xóa token hiện tại!');
        }

        // 2. Nạp tồn kho từ Product Service
        async function loadProductsStock() {
            const listEl = document.getElementById('product-stock-list');
            try {
                const headers = { 'Accept': 'application/json' };
                if (currentToken) headers['Authorization'] = 'Bearer ' + currentToken;

                const response = await fetch(`${PRODUCT_BASE_URL}/products`, { headers });
                const data = await response.json();

                if (response.ok && data.data) {
                    availableProducts = data.data;
                    renderProductsStock();
                    updateProductSelectDropdown();
                } else {
                    listEl.innerHTML = `<div class="text-xs text-center text-amber-600 py-4"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Chưa lấy được tồn kho: ${data.message || 'Cần bật Product Service và đăng nhập'}</div>`;
                }
            } catch (err) {
                listEl.innerHTML = `<div class="text-xs text-center text-rose-500 py-4"><i class="fa-solid fa-circle-exclamation mr-1"></i> Không thể kết nối Product Service (Port 8001)</div>`;
            }
        }

        function renderProductsStock() {
            const listEl = document.getElementById('product-stock-list');
            if (availableProducts.length === 0) {
                listEl.innerHTML = `<div class="text-xs text-center text-slate-400 py-4">Kho hàng hiện không có sản phẩm nào.</div>`;
                return;
            }

            listEl.innerHTML = availableProducts.map(p => `
                <div class="flex items-center justify-between p-2.5 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 transition">
                    <div class="truncate pr-2">
                        <span class="text-xs font-bold text-slate-700">#${p.id} ${escapeHtml(p.name)}</span>
                        <div class="text-xs text-emerald-600 font-semibold">${formatCurrency(p.price)}</div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs px-2.5 py-0.5 rounded-full font-bold ${p.quantity > 5 ? 'bg-emerald-100 text-emerald-800' : p.quantity > 0 ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800'}">
                            Tồn: ${p.quantity}
                        </span>
                        <button onclick="quickOrder(${p.id})" class="px-2 py-1 bg-sky-600 hover:bg-sky-700 text-white rounded text-xs font-semibold shadow-xs">
                            Đặt
                        </button>
                    </div>
                </div>
            `).join('');
        }

        function updateProductSelectDropdown() {
            const selectEl = document.getElementById('form-order-product');
            if (availableProducts.length === 0) {
                selectEl.innerHTML = `<option value="">-- Không có sản phẩm nào --</option>`;
                return;
            }

            selectEl.innerHTML = `<option value="">-- Chọn sản phẩm cần đặt --</option>` + 
                availableProducts.map(p => `
                    <option value="${p.id}" data-stock="${p.quantity}" data-price="${p.price}">
                        #${p.id} ${escapeHtml(p.name)} (Giá: ${formatCurrency(p.price)} - Còn: ${p.quantity})
                    </option>
                `).join('');
        }

        function onProductSelectChange() {
            const selectEl = document.getElementById('form-order-product');
            const selectedOpt = selectEl.options[selectEl.selectedIndex];
            const stock = selectedOpt.getAttribute('data-stock');
            document.getElementById('form-current-stock-display').value = stock ? `${stock} sản phẩm` : '---';
        }

        function quickOrder(productId) {
            openCreateOrderModal();
            const selectEl = document.getElementById('form-order-product');
            selectEl.value = productId;
            onProductSelectChange();
        }

        // 3. Tải danh sách đơn hàng (GET /orders)
        async function loadOrders() {
            const tbody = document.getElementById('orders-table-body');
            tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Đang nạp danh sách đơn hàng...</td></tr>`;

            const status = document.getElementById('filter-status').value;
            let url = `${ORDER_BASE_URL}/orders`;
            if (status) url += `?status=${encodeURIComponent(status)}`;

            try {
                const headers = { 'Accept': 'application/json' };
                if (currentToken) headers['Authorization'] = 'Bearer ' + currentToken;

                const response = await fetch(url, { headers });
                const data = await response.json();
                logConsole('GET', url, response.status, data);

                if (!response.ok) {
                    tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-rose-500 font-medium"><i class="fa-solid fa-triangle-exclamation mr-2"></i> ${data.message || 'Lỗi tải đơn hàng'} (HTTP ${response.status})</td></tr>`;
                    document.getElementById('order-count-summary').textContent = 'Truy cập thất bại';
                    return;
                }

                const orders = data.data || [];
                document.getElementById('order-count-summary').textContent = `Hiển thị ${orders.length} đơn hàng`;

                if (orders.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-slate-400"><i class="fa-solid fa-box-open mr-2"></i> Không có đơn hàng nào.</td></tr>`;
                    return;
                }

                tbody.innerHTML = orders.map(o => `
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-bold text-slate-700">#${o.id}</td>
                        <td class="py-3 px-4">
                            <div class="font-medium text-slate-800">${escapeHtml(o.receiver_name || o.user_name || 'Khách hàng')}</div>
                            <div class="text-xs text-slate-400">${escapeHtml(o.receiver_phone || '')}</div>
                        </td>
                        <td class="py-3 px-4 font-medium text-slate-900">
                            <span class="text-xs text-slate-400 mr-1">#${o.product_id}</span>${escapeHtml(o.product_name)}
                        </td>
                        <td class="py-3 px-4 text-center font-bold text-slate-700">${o.quantity}</td>
                        <td class="py-3 px-4 text-right text-xs text-slate-500">${formatCurrency(o.unit_price)}</td>
                        <td class="py-3 px-4 text-right font-bold text-emerald-600">${formatCurrency(o.total_price)}</td>
                        <td class="py-3 px-4 text-center">${getStatusBadge(o.status)}</td>
                        <td class="py-3 px-4 text-center text-xs text-slate-400">${formatDate(o.created_at)}</td>
                        <td class="py-3 px-4 text-center">
                            <div class="flex items-center justify-center space-x-1">
                                <button onclick="viewOrderDetail(${o.id})" class="p-1.5 text-sky-600 hover:bg-sky-50 rounded transition" title="Xem chi tiết">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button onclick="changeOrderStatusPrompt(${o.id}, '${o.status}')" class="p-1.5 text-amber-600 hover:bg-amber-50 rounded transition" title="Đổi trạng thái">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                ${o.status !== 'cancelled' ? `
                                    <button onclick="cancelOrder(${o.id})" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded transition" title="Hủy đơn (Tự động hoàn kho)">
                                        <i class="fa-solid fa-ban"></i>
                                    </button>
                                ` : ''}
                                <button onclick="deleteOrder(${o.id})" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition" title="Xóa đơn">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center text-rose-500 font-medium">Lỗi kết nối tới Order Service (Port 8002): ${err.message}</td></tr>`;
            }
        }

        function getStatusBadge(status) {
            switch(status) {
                case 'pending': return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Chờ duyệt</span>`;
                case 'confirmed': return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-sky-100 text-sky-800">Đã duyệt</span>`;
                case 'shipping': return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">Đang giao</span>`;
                case 'completed': return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">Hoàn tất</span>`;
                case 'cancelled': return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">Đã hủy</span>`;
                default: return `<span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">${status}</span>`;
            }
        }

        // 4. Tạo đơn hàng mới (POST /orders)
        async function handleCreateOrder(e) {
            e.preventDefault();
            const productId = parseInt(document.getElementById('form-order-product').value, 10);
            const quantity = parseInt(document.getElementById('form-order-quantity').value, 10);
            const receiverName = document.getElementById('form-receiver-name').value.trim();
            const receiverPhone = document.getElementById('form-receiver-phone').value.trim();
            const shippingAddress = document.getElementById('form-shipping-address').value.trim();

            if (!productId) {
                alert('Vui lòng chọn sản phẩm!');
                return;
            }

            try {
                const response = await fetch(`${ORDER_BASE_URL}/orders`, {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + currentToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        product_id: productId,
                        quantity: quantity,
                        receiver_name: receiverName,
                        receiver_phone: receiverPhone,
                        shipping_address: shippingAddress,
                    })
                });

                const data = await response.json();
                logConsole('POST', `${ORDER_BASE_URL}/orders`, response.status, data);

                if (response.ok) {
                    alert('🎉 Tạo đơn hàng thành công! Tồn kho tại Product Service đã được tự động trừ.');
                    closeOrderModal();
                    loadOrders();
                    loadProductsStock(); // Cập nhật lại danh sách tồn kho
                } else {
                    alert('⚠️ Lỗi tạo đơn hàng (' + response.status + '): ' + (data.message || JSON.stringify(data)));
                }
            } catch (err) {
                alert('Lỗi kết nối: ' + err.message);
            }
        }

        // 5. Cập nhật trạng thái / Hủy đơn hàng (PUT /orders/{id})
        async function changeOrderStatusPrompt(id, currentStatus) {
            const nextStatus = prompt(`Nhập trạng thái mới cho đơn hàng #${id} (pending, confirmed, shipping, completed, cancelled):`, currentStatus);
            if (!nextStatus || nextStatus === currentStatus) return;

            try {
                const response = await fetch(`${ORDER_BASE_URL}/orders/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + currentToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: nextStatus })
                });

                const data = await response.json();
                logConsole('PUT', `${ORDER_BASE_URL}/orders/${id}`, response.status, data);

                if (response.ok) {
                    alert(`Đã cập nhật trạng thái đơn hàng #${id} sang "${nextStatus}"!`);
                    loadOrders();
                    loadProductsStock();
                } else {
                    alert('Lỗi cập nhật: ' + (data.message || response.statusText));
                }
            } catch (err) {
                alert('Lỗi kết nối: ' + err.message);
            }
        }

        async function cancelOrder(id) {
            if (!confirm(`Bạn có chắc muốn HỦY đơn hàng #${id}? Số lượng sản phẩm sẽ được tự động hoàn lại vào kho của Product Service.`)) {
                return;
            }

            try {
                const response = await fetch(`${ORDER_BASE_URL}/orders/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Authorization': 'Bearer ' + currentToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ status: 'cancelled' })
                });

                const data = await response.json();
                logConsole('PUT', `${ORDER_BASE_URL}/orders/${id}`, response.status, data);

                if (response.ok) {
                    alert(`Đã hủy đơn hàng #${id}. Số lượng sản phẩm đã được tự động hoàn vào kho!`);
                    loadOrders();
                    loadProductsStock();
                } else {
                    alert('Lỗi hủy đơn: ' + data.message);
                }
            } catch (err) {
                alert('Lỗi kết nối: ' + err.message);
            }
        }

        // 6. Xóa đơn hàng (DELETE /orders/{id})
        async function deleteOrder(id) {
            if (!confirm(`Xóa hoàn toàn đơn hàng #${id}?`)) return;

            try {
                const response = await fetch(`${ORDER_BASE_URL}/orders/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'Authorization': 'Bearer ' + currentToken,
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();
                logConsole('DELETE', `${ORDER_BASE_URL}/orders/${id}`, response.status, data);

                if (response.ok) {
                    alert(`Đã xóa đơn hàng #${id}!`);
                    loadOrders();
                    loadProductsStock();
                } else {
                    alert('Lỗi xóa: ' + data.message);
                }
            } catch (err) {
                alert('Lỗi kết nối: ' + err.message);
            }
        }

        // 7. Xem chi tiết đơn hàng (GET /orders/{id})
        async function viewOrderDetail(id) {
            try {
                const response = await fetch(`${ORDER_BASE_URL}/orders/${id}`, {
                    headers: { 'Authorization': 'Bearer ' + currentToken, 'Accept': 'application/json' }
                });
                const data = await response.json();
                logConsole('GET', `${ORDER_BASE_URL}/orders/${id}`, response.status, data);

                if (response.ok && data.data) {
                    const o = data.data;
                    document.getElementById('detail-modal-content').innerHTML = `
                        <div class="bg-sky-50 p-4 rounded-lg border border-sky-100 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-sky-600 uppercase">Mã Đơn: #${o.id}</span>
                                <h4 class="text-base font-bold text-slate-800">${escapeHtml(o.product_name)}</h4>
                            </div>
                            <div>${getStatusBadge(o.status)}</div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="p-3 bg-slate-50 rounded border border-slate-200">
                                <span class="text-xs text-slate-500 block">Số lượng mua:</span>
                                <span class="text-base font-bold text-slate-800">${o.quantity} sản phẩm</span>
                            </div>
                            <div class="p-3 bg-slate-50 rounded border border-slate-200">
                                <span class="text-xs text-slate-500 block">Tổng tiền đơn:</span>
                                <span class="text-base font-bold text-emerald-600">${formatCurrency(o.total_price)}</span>
                            </div>
                        </div>
                        <div class="space-y-1 text-xs text-slate-600 bg-slate-50 p-3 rounded border border-slate-200">
                            <div><strong>Người nhận:</strong> ${escapeHtml(o.receiver_name || o.user_name || '---')}</div>
                            <div><strong>Số điện thoại:</strong> ${escapeHtml(o.receiver_phone || '---')}</div>
                            <div><strong>Địa chỉ giao:</strong> ${escapeHtml(o.shipping_address || '---')}</div>
                            <div><strong>Ghi chú:</strong> ${escapeHtml(o.notes || 'Không có')}</div>
                        </div>
                        <div class="text-xs text-slate-400">
                            <i class="fa-regular fa-clock mr-1"></i> Ngày tạo đơn: ${formatDate(o.created_at)}
                        </div>
                    `;
                    document.getElementById('detail-modal').classList.remove('hidden');
                }
            } catch (err) {
                alert('Lỗi nạp chi tiết: ' + err.message);
            }
        }

        // 8. Thử nghiệm nhanh các kịch bản SOA
        async function testWithoutToken() {
            const url = `${ORDER_BASE_URL}/orders`;
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            logConsole('GET', url, res.status, data);
        }

        async function testExceedStockOrder() {
            const url = `${ORDER_BASE_URL}/orders`;
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + currentToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ product_id: 1, quantity: 999999 })
            });
            const data = await res.json();
            logConsole('POST', url, res.status, data);
        }

        async function testNonexistentProductOrder() {
            const url = `${ORDER_BASE_URL}/orders`;
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + currentToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ product_id: 999999, quantity: 1 })
            });
            const data = await res.json();
            logConsole('POST', url, res.status, data);
        }

        // Modals & Utilities
        function openCreateOrderModal() { document.getElementById('order-modal').classList.remove('hidden'); }
        function closeOrderModal() { document.getElementById('order-modal').classList.add('hidden'); }
        function closeDetailModal() { document.getElementById('detail-modal').classList.add('hidden'); }
        function formatCurrency(val) { return Number(val).toLocaleString('vi-VN') + ' ₫'; }
        function formatDate(val) { return val ? new Date(val).toLocaleString('vi-VN') : '---'; }
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
        }
    </script>
</body>
</html>
