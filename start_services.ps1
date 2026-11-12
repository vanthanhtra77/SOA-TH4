# ==============================================================================
# Script khởi chạy toàn bộ 3 dịch vụ trong Hệ sinh thái SOA:
# 1. Auth Service (Port 8000) - Bài Thực Hành Số 2
# 2. Product Service (Port 8001) - Bài Thực Hành Số 3
# 3. Order Service (Port 8002) - Bài Thực Hành Số 4
# ==============================================================================

Write-Host "=================================================================" -ForegroundColor Cyan
Write-Host "   KHOI DONG HE THONG SOA (SERVICE-ORIENTED ARCHITECTURE)" -ForegroundColor Green
Write-Host "   Service 1 (Auth):    http://localhost:8000" -ForegroundColor Yellow
Write-Host "   Service 2 (Product): http://localhost:8001" -ForegroundColor Yellow
Write-Host "   Service 3 (Order):   http://localhost:8002" -ForegroundColor Yellow
Write-Host "=================================================================" -ForegroundColor Cyan

$th2Path = "D:\SOA\th2\SOATh2-main\SOATh2-main"
$th3Path = "D:\SOA\TH3"
$th4Path = "D:\SOA\TH4"

# 1. Kiểm tra môi trường PHP
try {
    $phpVersion = php -r "echo PHP_VERSION;"
    Write-Host "[OK] Tim thay PHP phien ban: $phpVersion" -ForegroundColor Green
} catch {
    Write-Host "[ERROR] Khong tim thay PHP trong PATH. Vui long cai dat PHP!" -ForegroundColor Red
    Exit 1
}

# 2. Chuan bi Database Auth Service (Port 8000)
Write-Host "`n[1/4] Kiem tra Auth Service (Bài Thực Hành Số 2)..." -ForegroundColor Cyan
if (Test-Path $th2Path) {
    if (!(Test-Path "$th2Path\database\database.sqlite")) {
        New-Item -ItemType File -Force -Path "$th2Path\database\database.sqlite" | Out-Null
    }
    Push-Location $th2Path
    php artisan migrate --force | Out-Null
    php artisan db:seed --force | Out-Null
    Pop-Location
    Write-Host "  -> Auth Service da san sang (Port 8000)" -ForegroundColor Green
} else {
    Write-Host "  -> [Canh bao] Khong tim thay thu muc Auth Service tai $th2Path" -ForegroundColor Yellow
}

# 3. Chuan bi Database Product Service (Port 8001)
Write-Host "`n[2/4] Kiem tra Product Service (Bài Thực Hành Số 3)..." -ForegroundColor Cyan
if (Test-Path $th3Path) {
    if (!(Test-Path "$th3Path\database\products.sqlite")) {
        New-Item -ItemType File -Force -Path "$th3Path\database\products.sqlite" | Out-Null
    }
    Push-Location $th3Path
    php artisan migrate --force | Out-Null
    php artisan db:seed --force | Out-Null
    Pop-Location
    Write-Host "  -> Product Service da san sang (Port 8001)" -ForegroundColor Green
} else {
    Write-Host "  -> [Canh bao] Khong tim thay thu muc Product Service tai $th3Path" -ForegroundColor Yellow
}

# 4. Chuan bi Database Order Service (Port 8002)
Write-Host "`n[3/4] Kiem tra Order Service (Bài Thực Hành Số 4)..." -ForegroundColor Cyan
if (!(Test-Path "$th4Path\database\orders.sqlite")) {
    New-Item -ItemType File -Force -Path "$th4Path\database\orders.sqlite" | Out-Null
}
Push-Location $th4Path
php artisan migrate --force | Out-Null
php artisan db:seed --force | Out-Null
Pop-Location
Write-Host "  -> Order Service da san sang (Port 8002)" -ForegroundColor Green

# 5. Khoi chay 3 Services tren 3 cua so PowerShell rieng biet
Write-Host "`n[4/4] Dang khoi chay 3 Services..." -ForegroundColor Cyan

# Cua so 1: Auth Service (Port 8000)
$authStartScript = "cd '$th2Path'; Write-Host 'AUTH SERVICE (PORT 8000) DANG CHAY...' -ForegroundColor Green; php -S 127.0.0.1:8000 -t public"
Start-Process powershell -ArgumentList "-NoExit", "-Command", $authStartScript

# Cua so 2: Product Service (Port 8001)
$productStartScript = "cd '$th3Path'; Write-Host 'PRODUCT SERVICE (PORT 8001) DANG CHAY...' -ForegroundColor Green; php -S 127.0.0.1:8001 -t public"
Start-Process powershell -ArgumentList "-NoExit", "-Command", $productStartScript

# Cua so 3: Order Service (Port 8002)
$orderStartScript = "cd '$th4Path'; Write-Host 'ORDER SERVICE (PORT 8002) DANG CHAY...' -ForegroundColor Green; php -S 127.0.0.1:8002 -t public"
Start-Process powershell -ArgumentList "-NoExit", "-Command", $orderStartScript

Start-Sleep -Seconds 2

# Mo trinh duyet den trang Web Demo Order Service
Write-Host "`n=================================================================" -ForegroundColor Cyan
Write-Host " [SUCCESS] 3 Services da duoc khoi chay thanh cong!" -ForegroundColor Green
Write-Host " -> Giao dien Web Demo:   http://localhost:8002" -ForegroundColor White
Write-Host " -> Postman Collection:    D:\SOA\TH4\postman\Order-Service.postman_collection.json" -ForegroundColor White
Write-Host " -> Tai khoan dang nhap:  student / 123456" -ForegroundColor Yellow
Write-Host "=================================================================" -ForegroundColor Cyan

Start-Process "http://localhost:8002"
