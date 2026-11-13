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
                        CSDL riêng (<code class="text-amber-400">orders</code> & <code class="text-amber-400">order_items</code>) • Kiểm tra tồn kho từ <strong>Product Service (Port 8001)</strong> • Xác thực qua <strong>Auth Service (Port 8000)</strong>
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
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">1</div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Auth Service (Bài 2)</div>
                        <div class="font-bold text-slate-800 text-sm">Port 8000</div>
                    </div>
                </div>
                <span id="auth-status-badge" class="px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-medium">Chờ đăng nhập</span>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-sky-100 text-sky-700 flex items-center justify-center font-bold">2</div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Product Service (Bài 3)</div>
                        <div class="font-bold text-slate-800 text-sm">Port 8001</div>
                    </div>
                </div>
                <span id="product-status-badge" class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-medium">Kho & Giá</span>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">3</div>
                    <div>
                        <div class="text-xs font-semibold text-slate-500 uppercase">Order Service (Bài 4)</div>
                        <div class="font-bold text-emerald-700 text-sm">Port 8002 (Hiện tại)</div>
                    </div>
                </div>
                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-medium">Hoạt động độc lập</span>
            </div>
        </section>

        <!-- 2. Authentication Bar -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved text-indigo-600"></i>
                        Xác Thực Liên Dịch Vụ (Auth Service - Port 8000)
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">Đăng nhập tài khoản mẫu <code class="text-indigo-600 code-font">student</code> (mật khẩu <code class="code-font">123456</code>) để lấy JWT Bearer Token</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <button onclick="loginSampleUser()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition flex items-center gap-2">
                        <i class="fa-solid fa-key"></i> Đăng nhập & Lấy Token mẫu
                    </button>
                    <button onclick="clearToken()" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition border border-slate-300">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất
                    </button>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-2 text-xs">
                <div class="truncate max-w-2xl">
                    <span class="text-slate-500 font-semibold">Active Token: </span>
                    <span id="token-display" class="code-font text-slate-400 italic">Chưa có token (Vui lòng bấm Đăng nhập)</span>
                </div>
                <div id="user-info-display" class="font-medium text-slate-600">Người dùng: Chưa xác thực</div>
            </div>
        </section>

        <!-- 3. Navigation Tabs -->
        <div class="flex border-b border-slate-200">
            <button id="tab-orders-btn" onclick="switchTab('orders')" class="py-3 px-6 text-sm font-bold border-b-2 border-sky-600 text-sky-600 flex items-center gap-2">
                <i class="fa-solid fa-receipt"></i> 1. Danh Sách Đơn Hàng (<code class="code-font">/orders</code>)
            </button>
            <button id="tab-items-btn" onclick="switchTab('items')" class="py-3 px-6 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-boxes-stacked"></i> 2. Chi Tiết Mặt Hàng (<code class="code-font">/order_items</code>)
            </button>
        </div>

        <!-- 4. Tab 1: Orders Section -->
        <section id="tab-orders-section" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Quản Lý Đơn Hàng (Bảng <code class="text-sky-700 code-font">orders</code>)</h2>
                    <p class="text-xs text-slate-500">Gồm: <code class="code-font">id</code>, <code class="code-font">customer_name</code>, <code class="code-font">customer_email</code>, <code class="code-font">total_amount</code>, <code class="code-font">status</code> (pending, completed, cancelled)</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button onclick="loadOrders()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 border border-slate-300">
                        <i class="fa-solid fa-rotate"></i> Tải lại danh sách
                    </button>
                    <button onclick="openCreateOrderModal()" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-plus"></i> Tạo Đơn Hàng Mới
                    </button>
                    <select id="filter-status" onchange="loadOrders()" class="bg-white border border-slate-300 rounded px-2.5 py-1.5 text-xs focus:ring-1 focus:ring-sky-500 focus:outline-none">
                        <option value="">Tất cả trạng thái</option>
                        <option value="pending">pending (Chờ xử lý)</option>
                        <option value="completed">completed (Hoàn thành)</option>
                        <option value="cancelled">cancelled (Đã hủy)</option>
                    </select>
                </div>
            </div>

            <!-- Orders Table -->
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-100 text-slate-700 font-semibold text-xs">
                        <tr>
                            <th class="py-3 px-4 text-left">ID</th>
                            <th class="py-3 px-4 text-left">Khách Hàng</th>
                            <th class="py-3 px-4 text-left">Email</th>
                            <th class="py-3 px-4 text-left">Số Mặt Hàng</th>
                            <th class="py-3 px-4 text-right">Tổng Tiền (total_amount)</th>
                            <th class="py-3 px-4 text-center">Trạng Thái (status)</th>
                            <th class="py-3 px-4 text-left">Thời Gian</th>
                            <th class="py-3 px-4 text-center">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="orders-table-body" class="divide-y divide-slate-200 bg-white">
                        <tr><td colspan="8" class="py-8 text-center text-slate-400">Đang nạp danh sách đơn hàng...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 5. Tab 2: Order Items Section -->
        <section id="tab-items-section" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-6 hidden">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Chi Tiết Mặt Hàng Trong Đơn (Bảng <code class="text-sky-700 code-font">order_items</code>)</h2>
                    <p class="text-xs text-slate-500">Gồm: <code class="code-font">id</code>, <code class="code-font">order_id</code>, <code class="code-font">product_id</code>, <code class="code-font">product_name</code>, <code class="code-font">quantity</code>, <code class="code-font">unit_price</code>, <code class="code-font">total_price</code></p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button onclick="loadOrderItems()" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition flex items-center gap-1.5 border border-slate-300">
                        <i class="fa-solid fa-rotate"></i> Tải lại mặt hàng
                    </button>
                    <button onclick="openAddItemModal()" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
                        <i class="fa-solid fa-cart-plus"></i> Thêm Mặt Hàng Vào Đơn
                    </button>
                </div>
            </div>

            <!-- Order Items Table -->
            <div class="overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-100 text-slate-700 font-semibold text-xs">
                        <tr>
                            <th class="py-3 px-4 text-left">Item ID</th>
                            <th class="py-3 px-4 text-left">Mã Đơn (order_id)</th>
                            <th class="py-3 px-4 text-left">Mã SP (product_id)</th>
                            <th class="py-3 px-4 text-left">Tên Sản Phẩm</th>
                            <th class="py-3 px-4 text-center">Số Lượng</th>
                            <th class="py-3 px-4 text-right">Đơn Giá (unit_price)</th>
                            <th class="py-3 px-4 text-right">Thành Tiền (total_price)</th>
                            <th class="py-3 px-4 text-center">Thao Tác</th>
                        </tr>
                    </thead>
                    <tbody id="items-table-body" class="divide-y divide-slate-200 bg-white">
                        <tr><td colspan="8" class="py-8 text-center text-slate-400">Đang nạp chi tiết mặt hàng...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- 6. Quick Scenario Tests -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h2 class="text-base font-bold text-slate-800 flex items-center gap-2 mb-3">
                <i class="fa-solid fa-flask-vial text-purple-600"></i> Kịch Bản Thử Nghiệm Nhanh SOA & Kiểm Soát Lỗi
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <button onclick="testWithoutToken()" class="p-3 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 text-left transition">
                    <div class="text-xs font-bold text-rose-700">1. Không có Token</div>
                    <div class="text-[11px] text-slate-500 mt-1">Gọi GET /orders không gửi Token -> Mong đợi 401 Unauthorized</div>
                </button>
                <button onclick="testExceedStockOrder()" class="p-3 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 text-left transition">
                    <div class="text-xs font-bold text-amber-700">2. Mua vượt quá tồn kho</div>
                    <div class="text-[11px] text-slate-500 mt-1">Đặt số lượng 99999 -> Mong đợi 422 Unprocessable</div>
                </button>
                <button onclick="testNonexistentProductOrder()" class="p-3 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 text-left transition">
                    <div class="text-xs font-bold text-sky-700">3. Sản phẩm không tồn tại</div>
                    <div class="text-[11px] text-slate-500 mt-1">Đặt product_id = 999999 -> Mong đợi 404 Not Found</div>
                </button>
                <button onclick="loadOrderItems()" class="p-3 bg-slate-50 hover:bg-slate-100 rounded-lg border border-slate-200 text-left transition">
                    <div class="text-xs font-bold text-emerald-700">4. GET /order_items</div>
                    <div class="text-[11px] text-slate-500 mt-1">Lấy tất cả mặt hàng theo yêu cầu phụ lục đề bài</div>
                </button>
            </div>
        </section>

        <!-- 7. Console Log Section -->
        <section id="console-section" class="bg-slate-900 rounded-xl shadow-lg border border-slate-800 p-5 text-white">
            <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-terminal text-amber-400"></i>
                    <h3 class="font-bold text-sm tracking-wide">API Response Console (Real-time)</h3>
                </div>
                <button onclick="clearConsole()" class="text-xs text-slate-400 hover:text-white transition">Xóa màn hình</button>
            </div>
            <pre id="console-output" class="mt-4 p-4 bg-slate-950 rounded-lg code-font text-xs text-emerald-400 overflow-x-auto max-h-72 leading-relaxed">// Nhật ký phản hồi API...</pre>
        </section>

    </main>

    <!-- Modal: Tạo Đơn Hàng Mới -->
    <div id="order-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl shadow-2xl border border-slate-200 max-w-lg w-full mx-4 p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 text-base">Tạo Đơn Hàng Mới (POST /orders)</h3>
                <button onclick="closeOrderModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="create-order-form" onsubmit="handleCreateOrder(event)" class="space-y-4 mt-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Tên khách hàng (customer_name) *</label>
                    <input type="text" id="order-customer-name" value="Nguyễn Văn Sinh Viên" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Email khách hàng (customer_email)</label>
                    <input type="email" id="order-customer-email" value="student@example.com" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Chọn sản phẩm (từ Product Service) *</label>
                        <select id="order-product-select" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                            <option value="1">iPhone 15 Pro Max (ID 1)</option>
                            <option value="3">Bàn phím cơ Keychron K2 Pro (ID 3)</option>
                            <option value="4">Chuột không dây MX Master 3S (ID 4)</option>
                            <option value="6">Tai nghe Sony WH-1000XM5 (ID 6)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Số lượng mua (quantity) *</label>
                        <input type="number" id="order-quantity" value="1" min="1" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                    </div>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Địa chỉ giao hàng (shipping_address)</label>
                    <input type="text" id="order-address" value="KTX Khu B ĐHQG" class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeOrderModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium">Hủy bỏ</button>
                    <button type="submit" class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-lg font-semibold shadow-sm">Xác nhận tạo đơn</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Thêm Mặt Hàng Vào Đơn (POST /order_items) -->
    <div id="item-modal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl shadow-2xl border border-slate-200 max-w-md w-full mx-4 p-6">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="font-bold text-slate-800 text-base">Thêm Mặt Hàng Vào Đơn (POST /order_items)</h3>
                <button onclick="closeItemModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form id="create-item-form" onsubmit="handleCreateItem(event)" class="space-y-4 mt-4 text-xs">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Mã đơn hàng (order_id) *</label>
                    <input type="number" id="item-order-id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Mã sản phẩm (product_id) *</label>
                    <select id="item-product-id" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                        <option value="1">iPhone 15 Pro Max (ID 1)</option>
                        <option value="3">Bàn phím cơ Keychron K2 Pro (ID 3)</option>
                        <option value="4">Chuột không dây MX Master 3S (ID 4)</option>
                        <option value="6">Tai nghe Sony WH-1000XM5 (ID 6)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Số lượng mua (quantity) *</label>
                    <input type="number" id="item-quantity" value="1" min="1" required class="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-1 focus:ring-sky-500 focus:outline-none">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeItemModal()" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-700 hover:bg-slate-50 font-medium">Hủy bỏ</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold shadow-sm">Thêm mặt hàng</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript Application Logic -->
    <script>
        const AUTH_BASE_URL = 'http://127.0.0.1:8000';
        const ORDER_BASE_URL = 'http://127.0.0.1:8002';
        let currentToken = localStorage.getItem('soa_token') || '';

        function logConsole(method, url, status, data) {
            const el = document.getElementById('console-output');
            const time = new Date().toLocaleTimeString();
            const logEntry = `[${time}] ${method} ${url} -> Status: ${status}\n` + JSON.stringify(data, null, 2) + '\n\n';
            el.textContent = logEntry + el.textContent;
        }
        function clearConsole() { document.getElementById('console-output').textContent = '// Console cleared.'; }

        function switchTab(tab) {
            const ordersSection = document.getElementById('tab-orders-section');
            const itemsSection = document.getElementById('tab-items-section');
            const ordersBtn = document.getElementById('tab-orders-btn');
            const itemsBtn = document.getElementById('tab-items-btn');

            if (tab === 'orders') {
                ordersSection.classList.remove('hidden');
                itemsSection.classList.add('hidden');
                ordersBtn.className = 'py-3 px-6 text-sm font-bold border-b-2 border-sky-600 text-sky-600 flex items-center gap-2';
                itemsBtn.className = 'py-3 px-6 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-2';
                loadOrders();
            } else {
                ordersSection.classList.add('hidden');
                itemsSection.classList.remove('hidden');
                itemsBtn.className = 'py-3 px-6 text-sm font-bold border-b-2 border-emerald-600 text-emerald-600 flex items-center gap-2';
                ordersBtn.className = 'py-3 px-6 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center gap-2';
                loadOrderItems();
            }
        }

        async function loginSampleUser() {
            try {
                const b64Password = btoa('123456');
                const res = await fetch(`${AUTH_BASE_URL}/login`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ userName: 'student', password: b64Password })
                });
                const data = await res.json();
                logConsole('POST', `${AUTH_BASE_URL}/login`, res.status, data);

                if (res.ok && data.access_token) {
                    currentToken = data.access_token;
                    localStorage.setItem('soa_token', currentToken);
                    document.getElementById('token-display').textContent = currentToken.substring(0, 30) + '... (Hợp lệ)';
                    document.getElementById('auth-status-badge').className = 'px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-medium';
                    document.getElementById('auth-status-badge').textContent = 'Đã xác thực JWT';
                    document.getElementById('user-info-display').textContent = 'Người dùng: student (ID: 1)';
                    loadOrders();
                } else {
                    alert('Đăng nhập thất bại: ' + (data.message || 'Lỗi server'));
                }
            } catch (err) {
                alert('Không thể kết nối Auth Service (Port 8000): ' + err.message);
            }
        }

        function clearToken() {
            currentToken = '';
            localStorage.removeItem('soa_token');
            document.getElementById('token-display').textContent = 'Chưa có token (Vui lòng bấm Đăng nhập)';
            document.getElementById('auth-status-badge').className = 'px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full text-xs font-medium';
            document.getElementById('auth-status-badge').textContent = 'Chờ đăng nhập';
            document.getElementById('user-info-display').textContent = 'Người dùng: Chưa xác thực';
        }

        async function loadOrders() {
            const tbody = document.getElementById('orders-table-body');
            tbody.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Đang nạp danh sách đơn hàng...</td></tr>`;

            const status = document.getElementById('filter-status').value;
            let url = `${ORDER_BASE_URL}/orders`;
            if (status) url += `?status=${encodeURIComponent(status)}`;

            try {
                const headers = { 'Accept': 'application/json' };
                if (currentToken) headers['Authorization'] = 'Bearer ' + currentToken;
                const res = await fetch(url, { headers });
                const data = await res.json();
                logConsole('GET', url, res.status, data);

                if (!res.ok) {
                    tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-rose-600 font-medium">${escapeHtml(data.message || 'Lỗi tải danh sách')}</td></tr>`;
                    return;
                }

                const orders = data.data || [];
                if (orders.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-slate-400">Không có đơn hàng nào.</td></tr>`;
                    return;
                }

                tbody.innerHTML = orders.map(o => `
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-bold text-sky-700">#${o.id}</td>
                        <td class="py-3 px-4 font-medium text-slate-800">${escapeHtml(o.customer_name)}</td>
                        <td class="py-3 px-4 text-slate-500">${escapeHtml(o.customer_email || '---')}</td>
                        <td class="py-3 px-4 text-slate-600">${o.items ? o.items.length : 0} mặt hàng</td>
                        <td class="py-3 px-4 text-right font-bold text-slate-900">${formatCurrency(o.total_amount)}</td>
                        <td class="py-3 px-4 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${getStatusBadge(o.status)}">${escapeHtml(o.status)}</span>
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-400">${formatDate(o.created_at)}</td>
                        <td class="py-3 px-4 text-center space-x-1.5">
                            ${o.status !== 'cancelled' ? `<button onclick="updateOrderStatus(${o.id}, 'completed')" class="px-2 py-1 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 rounded text-xs">Hoàn thành</button>` : ''}
                            ${o.status !== 'cancelled' ? `<button onclick="updateOrderStatus(${o.id}, 'cancelled')" class="px-2 py-1 bg-amber-100 hover:bg-amber-200 text-amber-800 rounded text-xs">Hủy (Hoàn kho)</button>` : ''}
                            <button onclick="deleteOrder(${o.id})" class="px-2 py-1 bg-rose-100 hover:bg-rose-200 text-rose-800 rounded text-xs">Xóa</button>
                        </td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-rose-600">Lỗi kết nối Order Service: ${err.message}</td></tr>`;
            }
        }

        async function loadOrderItems() {
            const tbody = document.getElementById('items-table-body');
            tbody.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Đang nạp danh sách chi tiết mặt hàng...</td></tr>`;

            const url = `${ORDER_BASE_URL}/order_items`;
            try {
                const headers = { 'Accept': 'application/json' };
                if (currentToken) headers['Authorization'] = 'Bearer ' + currentToken;
                const res = await fetch(url, { headers });
                const data = await res.json();
                logConsole('GET', url, res.status, data);

                if (!res.ok) {
                    tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-rose-600 font-medium">${escapeHtml(data.message || 'Lỗi tải mặt hàng')}</td></tr>`;
                    return;
                }

                const items = data.data || [];
                if (items.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="8" class="py-8 text-center text-slate-400">Không có mặt hàng nào.</td></tr>`;
                    return;
                }

                tbody.innerHTML = items.map(item => `
                    <tr class="hover:bg-slate-50 transition">
                        <td class="py-3 px-4 font-bold text-emerald-700">#${item.id}</td>
                        <td class="py-3 px-4 text-sky-700 font-semibold">Đơn #${item.order_id}</td>
                        <td class="py-3 px-4 text-slate-600">SP #${item.product_id}</td>
                        <td class="py-3 px-4 font-medium text-slate-800">${escapeHtml(item.product_name)}</td>
                        <td class="py-3 px-4 text-center font-bold">${item.quantity}</td>
                        <td class="py-3 px-4 text-right text-slate-600">${formatCurrency(item.unit_price)}</td>
                        <td class="py-3 px-4 text-right font-bold text-slate-900">${formatCurrency(item.total_price)}</td>
                        <td class="py-3 px-4 text-center">
                            <button onclick="deleteOrderItem(${item.id})" class="px-2 py-1 bg-rose-100 hover:bg-rose-200 text-rose-800 rounded text-xs">Xóa</button>
                        </td>
                    </tr>
                `).join('');
            } catch (err) {
                tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-rose-600">Lỗi kết nối Order Service: ${err.message}</td></tr>`;
            }
        }

        async function handleCreateOrder(e) {
            e.preventDefault();
            const payload = {
                customer_name: document.getElementById('order-customer-name').value,
                customer_email: document.getElementById('order-customer-email').value,
                product_id: parseInt(document.getElementById('order-product-select').value),
                quantity: parseInt(document.getElementById('order-quantity').value),
                shipping_address: document.getElementById('order-address').value,
            };

            const url = `${ORDER_BASE_URL}/orders`;
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + currentToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            logConsole('POST', url, res.status, data);

            if (res.ok) {
                alert('Tạo đơn hàng thành công!');
                closeOrderModal();
                loadOrders();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể tạo đơn hàng'));
            }
        }

        async function handleCreateItem(e) {
            e.preventDefault();
            const payload = {
                order_id: parseInt(document.getElementById('item-order-id').value),
                product_id: parseInt(document.getElementById('item-product-id').value),
                quantity: parseInt(document.getElementById('item-quantity').value),
            };

            const url = `${ORDER_BASE_URL}/order_items`;
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + currentToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload)
            });
            const data = await res.json();
            logConsole('POST', url, res.status, data);

            if (res.ok) {
                alert('Thêm mặt hàng thành công!');
                closeItemModal();
                loadOrderItems();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể thêm mặt hàng'));
            }
        }

        async function updateOrderStatus(id, newStatus) {
            const url = `${ORDER_BASE_URL}/orders/${id}`;
            const res = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Authorization': 'Bearer ' + currentToken,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ status: newStatus })
            });
            const data = await res.json();
            logConsole('PUT', url, res.status, data);
            loadOrders();
        }

        async function deleteOrder(id) {
            if (!confirm(`Bạn có chắc muốn xóa đơn hàng #${id}?`)) return;
            const url = `${ORDER_BASE_URL}/orders/${id}`;
            const res = await fetch(url, {
                method: 'DELETE',
                headers: { 'Authorization': 'Bearer ' + currentToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            logConsole('DELETE', url, res.status, data);
            loadOrders();
        }

        async function deleteOrderItem(id) {
            if (!confirm(`Bạn có chắc muốn xóa mặt hàng #${id}?`)) return;
            const url = `${ORDER_BASE_URL}/order_items/${id}`;
            const res = await fetch(url, {
                method: 'DELETE',
                headers: { 'Authorization': 'Bearer ' + currentToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            logConsole('DELETE', url, res.status, data);
            loadOrderItems();
        }

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
                headers: { 'Authorization': 'Bearer ' + currentToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ product_id: 1, quantity: 999999 })
            });
            const data = await res.json();
            logConsole('POST', url, res.status, data);
        }

        async function testNonexistentProductOrder() {
            const url = `${ORDER_BASE_URL}/orders`;
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Authorization': 'Bearer ' + currentToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ product_id: 999999, quantity: 1 })
            });
            const data = await res.json();
            logConsole('POST', url, res.status, data);
        }

        function openCreateOrderModal() { document.getElementById('order-modal').classList.remove('hidden'); }
        function closeOrderModal() { document.getElementById('order-modal').classList.add('hidden'); }
        function openAddItemModal() { document.getElementById('item-modal').classList.remove('hidden'); }
        function closeItemModal() { document.getElementById('item-modal').classList.add('hidden'); }
        function formatCurrency(val) { return Number(val).toLocaleString('vi-VN') + ' ₫'; }
        function formatDate(val) { return val ? new Date(val).toLocaleString('vi-VN') : '---'; }
        function getStatusBadge(status) {
            switch (status) {
                case 'completed': return 'bg-emerald-100 text-emerald-800';
                case 'cancelled': return 'bg-rose-100 text-rose-800';
                default: return 'bg-amber-100 text-amber-800';
            }
        }
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/[&<>"']/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m]);
        }

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            if (currentToken) {
                document.getElementById('token-display').textContent = currentToken.substring(0, 30) + '... (Lưu từ trước)';
                document.getElementById('auth-status-badge').className = 'px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-medium';
                document.getElementById('auth-status-badge').textContent = 'Đã có Token';
            }
            loadOrders();
        });
    </script>
</body>
</html>
