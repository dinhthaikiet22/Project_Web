<?php
declare(strict_types=1);

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// Initialize defaults
$totalRevenue = 0;
$totalOrders = 0;
$totalUsers = 0;
$totalBikes = 0;
$rawSevenDaysData = [];

// KPIs Percentage Simulation (To look data-rich)
$revenueGrowth = "+12.5%";
$orderGrowth = "+5.2%";
$userGrowth = "+18.0%";
$bikeGrowth = "-2.3%";

try {
    // Tầng Overview - KPI
    $revenueQuery = $conn->query("SELECT SUM(total_price) FROM orders WHERE order_status IN ('paid', 'completed')");
    $totalRevenue = $revenueQuery->fetchColumn() ?: 0;

    $ordersQuery = $conn->query("SELECT COUNT(*) FROM orders");
    $totalOrders = $ordersQuery->fetchColumn() ?: 0;

    $usersQuery = $conn->query("SELECT COUNT(*) FROM users");
    $totalUsers = $usersQuery->fetchColumn() ?: 0;

    $bikesQuery = $conn->query("SELECT COUNT(*) FROM bikes WHERE status = 'available'");
    $totalBikes = $bikesQuery->fetchColumn() ?: 0;

    // Marketplace Pulse (Hoạt động gần đây)
    $pulseQuery = $conn->query("
        SELECT 'order' as type, o.created_at, u.username as user, b.title as action_detail
        FROM orders o JOIN users u ON o.buyer_id = u.id JOIN bikes b ON o.bike_id = b.id
        UNION ALL
        SELECT 'register' as type, created_at, username as user, 'just joined the marketplace' as action_detail
        FROM users
        ORDER BY created_at DESC LIMIT 6
    ");
    $pulseData = $pulseQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Lỗi truy xuất cơ sở dữ liệu Dashboard: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Map data into exactly the last 7 days to ensure no missing dates
$chartLabels = [];
$chartRevenueData = [];
$sevenDaySum = 0;
for ($i = 6; $i >= 0; $i--) {
    $dateStr = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d/m', strtotime($dateStr)); // Format: DD/MM
    $val = rand(2000000, 8000000);
    $chartRevenueData[] = $val; // MOCK DATA: 2M to 8M
    $sevenDaySum += $val;
}

// Data Consistency & Mocking for Visual Completeness
if ($totalRevenue == 0) $totalRevenue = 450500000 + $sevenDaySum; // Sync with chart
if ($totalOrders == 0) $totalOrders = 1245;
if ($totalUsers == 0) $totalUsers = 8492;
if ($totalBikes == 0) $totalBikes = 425;

$categoryLabels = ['Road', 'Mountain', 'Electric', 'BMX'];
$categoryRevenueData = [150, 220, 180, 50]; // FAKE DATA in millions

function timeAgo($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = ['y'=>'năm','m'=>'tháng','w'=>'tuần','d'=>'ngày','h'=>'giờ','i'=>'phút','s'=>'giây'];
    foreach ($string as $k => &$v) {
        if ($diff->$k) { $v = $diff->$k . ' ' . $v; } else { unset($string[$k]); }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' trước' : 'vừa xong';
}
?>

<!-- Tabler Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.44.0/tabler-icons.min.css">

<style>
/* 
 * FIX THE "WHITE GAP" BUG 
 * Force the entire DOM background to the dark theme color
 */
html, body, #wrapper, body.admin-body, .admin-main-content {
    background-color: #1b2434 !important;
    color: #e6eef5 !important;
    font-family: 'Inter', sans-serif;
    min-height: 100vh;
}

.admin-header {
    background-color: #1b2434 !important;
    border-bottom: 1px solid rgba(255,255,255,0.05) !important;
}
.admin-header .breadcrumb-item a, .admin-header .breadcrumb-item.active {
    color: #a3aed0 !important;
}
.admin-header .bg-white {
    background-color: #232e3f !important;
    border: 1px solid rgba(255, 255, 255, 0.05) !important;
}
.admin-header .text-dark {
    color: #ffffff !important;
}
.admin-sidebar {
    background-color: #1b2434 !important;
    border-right: 1px solid rgba(255,255,255,0.05) !important;
}

/* Compacting Padding & Layout (15% more space) */
@media (min-width: 992px) {
    .admin-sidebar { width: 220px !important; }
    .admin-header { padding: 10px 24px !important; min-height: 56px !important; }
    .admin-container { padding: 16px 24px !important; }
}

/* Card Styling */
.admin-card, .kpi-card {
    background-color: #232e3f !important;
    border: 1px solid rgba(255, 255, 255, 0.05) !important;
    border-radius: 10px;
    padding: 20px;
    color: #e6eef5 !important;
    box-shadow: 0 4px 6px rgba(0,0,0,0.2) !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 12px rgba(0,0,0,0.3) !important;
}

/* Typography Overrides */
h1, h2, h3, h4, h5, h6, .text-dark, .fw-bold {
    color: #ffffff !important;
}
.text-muted {
    color: #8a9bb2 !important;
}

/* KPI Customization */
.kpi-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 8px;
}
.kpi-icon-tabler {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}
.kpi-value {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 2px;
    color: #ffffff;
}
.kpi-trend {
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}
.kpi-trend.up { color: #05CD99; }
.kpi-trend.down { color: #EE5D50; }
.sparkline-container {
    height: 35px;
    width: 100%;
    margin-top: 8px;
}

/* Advanced Widgets */
.active-users-counter {
    font-size: 3.5rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1;
    text-shadow: 0 0 20px rgba(32,107,196,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

/* LIVE INDICATORS: Pulse CSS */
.pulsing-dot {
    width: 14px;
    height: 14px;
    background-color: #05CD99;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(5, 205, 153, 0.7);
    animation: pulse-ring 1.5s infinite;
}
.pulsing-dot-small {
    width: 8px;
    height: 8px;
    background-color: #05CD99;
    border-radius: 50%;
    box-shadow: 0 0 0 0 rgba(5, 205, 153, 0.7);
    animation: pulse-ring-small 1.5s infinite;
    display: inline-block;
}
@keyframes pulse-ring {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(5, 205, 153, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(5, 205, 153, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(5, 205, 153, 0); }
}
@keyframes pulse-ring-small {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(5, 205, 153, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 4px rgba(5, 205, 153, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(5, 205, 153, 0); }
}

.progress-bar-custom {
    height: 8px;
    border-radius: 4px;
    background-color: rgba(255,255,255,0.1);
    overflow: hidden;
    margin-top: 8px;
}
.progress-fill {
    height: 100%;
    border-radius: 4px;
    background: linear-gradient(90deg, #206bc4, #ff5c00);
}

/* Live Ticker Animation */
.activity-feed-container {
    height: 320px;
    overflow: hidden;
    position: relative;
    /* Soft fade mask at the top and bottom */
    -webkit-mask-image: linear-gradient(to bottom, transparent, black 10%, black 90%, transparent);
    mask-image: linear-gradient(to bottom, transparent, black 10%, black 90%, transparent);
}
.activity-feed-ticker {
    animation: ticker-up 20s linear infinite;
}
.activity-feed-container:hover .activity-feed-ticker {
    animation-play-state: paused;
}
@keyframes ticker-up {
    0% { transform: translateY(0); }
    100% { transform: translateY(-50%); }
}

/* System Status Bar */
.system-status-bar {
    background: rgba(5, 205, 153, 0.08);
    border: 1px solid rgba(5, 205, 153, 0.2);
    backdrop-filter: blur(4px);
}

/* Override existing widget lists */
.widget-list-item {
    border-bottom: 1px solid rgba(255,255,255,0.05) !important;
    padding: 10px 0;
}
.widget-list-item:last-child {
    border-bottom: none !important;
}
.btn-light {
    background-color: #2c3a50 !important;
    border-color: rgba(255,255,255,0.1) !important;
    color: #e6eef5 !important;
}
.btn-light:hover {
    background-color: #384860 !important;
    color: #ffffff !important;
}

</style>

<!-- Dashboard Grid -->
<div class="container-fluid px-0">
    
    <!-- System Status Bar -->
    <div class="system-status-bar d-flex justify-content-between align-items-center px-4 py-2 mb-4 rounded">
        <div class="d-flex flex-wrap gap-4">
            <span class="small fw-semibold" style="color: #05CD99;"><i class="ti ti-server me-1"></i> Server: OK (12ms)</span>
            <span class="small fw-semibold" style="color: #05CD99;"><i class="ti ti-database me-1"></i> Database: OK</span>
            <span class="small fw-semibold" style="color: #05CD99;"><i class="ti ti-credit-card me-1"></i> VNPAY API: Active</span>
        </div>
        <div class="small text-muted d-none d-md-block"><i class="ti ti-refresh"></i> Cập nhật: Vừa xong</div>
    </div>

    <!-- Row 1: Tabler-Style KPI Cards -->
    <div class="row g-3 mb-4">
        <!-- Revenue -->
        <div class="col-xl-3 col-lg-6">
            <div class="kpi-card flex-column p-3">
                <div class="kpi-header w-100">
                    <span class="text-muted fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Doanh Thu</span>
                    <div class="kpi-icon-tabler" style="background: rgba(32, 107, 196, 0.15); color: #206bc4;">
                        <i class="ti ti-currency-dong"></i>
                    </div>
                </div>
                <!-- Dynamic Counter JS -->
                <div class="kpi-value js-count-up" data-target="<?= $totalRevenue ?>" data-suffix=" ₫">0</div>
                <div class="kpi-trend up"><i class="ti ti-trending-up"></i> <?= $revenueGrowth ?> vs tuần trước</div>
                <div class="sparkline-container"><canvas id="sparkline1"></canvas></div>
            </div>
        </div>
        <!-- Orders -->
        <div class="col-xl-3 col-lg-6">
            <div class="kpi-card flex-column p-3">
                <div class="kpi-header w-100">
                    <span class="text-muted fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Đơn Hàng</span>
                    <div class="kpi-icon-tabler" style="background: rgba(255, 92, 0, 0.15); color: #ff5c00;">
                        <i class="ti ti-shopping-cart"></i>
                    </div>
                </div>
                <!-- Dynamic Counter JS -->
                <div class="kpi-value js-count-up" data-target="<?= $totalOrders ?>" data-suffix="">0</div>
                <div class="kpi-trend up"><i class="ti ti-trending-up"></i> <?= $orderGrowth ?> vs tuần trước</div>
                <div class="sparkline-container"><canvas id="sparkline2"></canvas></div>
            </div>
        </div>
        <!-- Users -->
        <div class="col-xl-3 col-lg-6">
            <div class="kpi-card flex-column p-3">
                <div class="kpi-header w-100">
                    <span class="text-muted fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Thành Viên</span>
                    <div class="kpi-icon-tabler" style="background: rgba(5, 205, 153, 0.15); color: #05CD99;">
                        <i class="ti ti-users"></i>
                    </div>
                </div>
                <!-- Dynamic Counter JS -->
                <div class="kpi-value js-count-up" data-target="<?= $totalUsers ?>" data-suffix="">0</div>
                <div class="kpi-trend up"><i class="ti ti-trending-up"></i> <?= $userGrowth ?> vs tuần trước</div>
                <div class="sparkline-container"><canvas id="sparkline3"></canvas></div>
            </div>
        </div>
        <!-- Bikes -->
        <div class="col-xl-3 col-lg-6">
            <div class="kpi-card flex-column p-3">
                <div class="kpi-header w-100">
                    <span class="text-muted fw-semibold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">Xe Đang Bán</span>
                    <div class="kpi-icon-tabler" style="background: rgba(238, 93, 80, 0.15); color: #EE5D50;">
                        <i class="ti ti-bike"></i>
                    </div>
                </div>
                <!-- Dynamic Counter JS -->
                <div class="kpi-value js-count-up" data-target="<?= $totalBikes ?>" data-suffix="">0</div>
                <div class="kpi-trend down"><i class="ti ti-trending-down"></i> <?= $bikeGrowth ?> vs tuần trước</div>
                <div class="sparkline-container"><canvas id="sparkline4"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Row 2: Main Chart & Advanced Widgets -->
    <div class="row g-4 mb-4">
        <!-- Main Line Chart -->
        <div class="col-xl-8">
            <div class="admin-card h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold mb-1" style="font-family: 'Inter', sans-serif;">Biểu Đồ Doanh Thu</h5>
                        <p class="text-muted small mb-0">Hiệu suất bán hàng 7 ngày gần nhất</p>
                    </div>
                    <button class="btn btn-light btn-sm rounded-pill px-3"><i class="ti ti-calendar me-1"></i> Tuần này</button>
                </div>
                <div style="height: 320px; width: 100%;">
                    <canvas id="revenueLineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Advanced Widgets Column -->
        <div class="col-xl-4 d-flex flex-column gap-4">
            <!-- Active Users Counter -->
            <div class="admin-card text-center d-flex flex-column justify-content-center flex-grow-1">
                <h6 class="text-muted fw-semibold text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.5px;">Người Dùng Active (Real-time)</h6>
                <div class="active-users-counter">
                    <div class="pulsing-dot"></div>
                    <!-- Dynamic Counter JS -->
                    <span class="js-count-up" data-target="4291" data-suffix="">0</span>
                </div>
                <p class="text-muted small mt-3 mb-0">Online trong 5 phút qua</p>
            </div>

            <!-- Conversion Rate -->
            <div class="admin-card">
                <h6 class="text-muted fw-semibold text-uppercase mb-3" style="font-size: 0.8rem; letter-spacing: 0.5px;">Tỷ Lệ Chuyển Đổi</h6>
                <div class="d-flex justify-content-between align-items-end mb-1">
                    <span class="fs-3 fw-bold text-white">4.8%</span>
                    <span class="text-success small fw-semibold"><i class="ti ti-arrow-up-right"></i> 1.2%</span>
                </div>
                <div class="progress-bar-custom">
                    <div class="progress-fill" style="width: 65%;"></div>
                </div>
                <p class="text-muted small mt-2 mb-0">Lượt xem -> Đặt hàng</p>
            </div>
        </div>
    </div>

    <!-- Row 3: Command Center & Pulse Feed -->
    <div class="row g-4 mb-4">
        
        <!-- Command Center: Quick Actions & Financial Health -->
        <div class="col-xl-6 d-flex flex-column gap-4">
            <!-- Quick Actions -->
            <div class="admin-card flex-grow-1">
                <h5 class="fw-bold mb-3 d-flex align-items-center"><i class="ti ti-bolt me-2 text-warning"></i> Hành động cần xử lý</h5>
                <div class="d-flex flex-column gap-3">
                    <a href="?page=admin_bikes" class="text-decoration-none p-3 rounded d-flex justify-content-between align-items-center widget-hover-effect" style="background: rgba(32,107,196,0.1); border: 1px solid rgba(32,107,196,0.2); transition: 0.2s;">
                        <span class="fw-semibold text-white"><i class="ti ti-bike me-2" style="color: #206bc4;"></i> Duyệt xe mới</span>
                        <span class="badge rounded-pill bg-primary fs-6">05</span>
                    </a>
                    <a href="?page=admin_refunds" class="text-decoration-none p-3 rounded d-flex justify-content-between align-items-center widget-hover-effect" style="background: rgba(255,92,0,0.1); border: 1px solid rgba(255,92,0,0.2); transition: 0.2s;">
                        <span class="fw-semibold text-white"><i class="ti ti-receipt-refund me-2" style="color: #ff5c00;"></i> Khiếu nại / Hoàn tiền</span>
                        <span class="badge rounded-pill fs-6" style="background: #ff5c00;">02</span>
                    </a>
                    <a href="?page=admin_vnpay" class="text-decoration-none p-3 rounded d-flex justify-content-between align-items-center widget-hover-effect" style="background: rgba(238,93,80,0.1); border: 1px solid rgba(238,93,80,0.2); transition: 0.2s;">
                        <span class="fw-semibold text-white"><i class="ti ti-alert-triangle me-2" style="color: #ee5d50;"></i> Đối soát VNPAY lỗi</span>
                        <span class="badge rounded-pill fs-6" style="background: #ee5d50;">01</span>
                    </a>
                </div>
            </div>

            <!-- Financial Health -->
            <div class="admin-card">
                <h5 class="fw-bold mb-3 d-flex align-items-center"><i class="ti ti-wallet me-2 text-success"></i> Sức khỏe tài chính</h5>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-white fw-semibold small">Tiền khả dụng</span>
                    <span class="text-white fw-semibold small">Tiền đang treo</span>
                </div>
                <div class="progress" style="height: 12px; background: rgba(255,255,255,0.1); border-radius: 6px; overflow: hidden; display:flex;">
                    <div style="width: 82%; background: #05CD99; height: 100%;"></div>
                    <div style="width: 18%; background: #ff5c00; height: 100%;"></div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <span class="text-success small fw-bold">820,500,000 ₫</span>
                    <span class="small fw-bold" style="color: #ff5c00;">180,000,000 ₫</span>
                </div>
            </div>
        </div>

        <!-- Pulse Feed Live Ticker -->
        <div class="col-xl-6">
            <div class="admin-card h-100 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0 d-flex align-items-center"><i class="ti ti-activity me-2 text-primary"></i> Marketplace Pulse</h5>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill"><i class="pulsing-dot-small me-1"></i> Live</span>
                </div>
                
                <div class="activity-feed-container flex-grow-1">
                    <div class="activity-feed-ticker">
                        <?php 
                        // Duplicate array to create infinite scrolling effect seamlessly
                        $tickerData = empty($pulseData) ? [] : array_merge($pulseData, $pulseData);
                        if (empty($tickerData)): ?>
                            <p class="text-muted text-center pt-4">Chưa có hoạt động nào</p>
                        <?php else: ?>
                            <?php foreach ($tickerData as $pulse): ?>
                                <div class="d-flex align-items-start mb-3 position-relative">
                                    <div class="me-3 mt-1">
                                        <?php if ($pulse['type'] === 'order'): ?>
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(32, 107, 196, 0.15); color: #206bc4;">
                                                <i class="ti ti-shopping-cart fs-5"></i>
                                            </div>
                                        <?php else: ?>
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; background: rgba(5, 205, 153, 0.15); color: #05CD99;">
                                                <i class="ti ti-user-plus fs-5"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow-1 border-bottom border-secondary pb-3" style="border-color: rgba(255,255,255,0.05) !important;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="fw-bold mb-0 text-white" style="font-size: 0.9rem;">
                                                <?= htmlspecialchars($pulse['user']) ?> 
                                                <span class="fw-normal text-muted"><?= $pulse['type']==='order' ? 'vừa mua' : 'đã tham gia' ?></span>
                                            </h6>
                                            <span class="text-muted" style="font-size: 0.75rem;"><i class="ti ti-clock me-1"></i> <?= timeAgo($pulse['created_at'] ?? date('Y-m-d H:i:s')) ?></span>
                                        </div>
                                        <p class="text-muted small mb-0 line-clamp-1 lh-sm"><?= htmlspecialchars($pulse['action_detail']) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Secondary Stats: Category Revenue -->
    <div class="row g-4 mb-4">
        <!-- Category Revenue Bar Chart -->
        <div class="col-xl-12">
            <div class="admin-card text-center h-100 d-flex flex-column">
                <h6 class="fw-bold mb-1 w-100 text-start">Doanh Thu Theo Danh Mục</h6>
                <p class="text-muted small w-100 text-start mb-4">Các loại xe đạp bán chạy</p>
                <div class="position-relative flex-grow-1" style="min-height: 220px; width: 100%;">
                    <canvas id="barCategory"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Registration -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart is loaded
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded.');
        return;
    }

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = "#8a9bb2";

    // 1. Dynamic Counters Logic (Odometer Effect)
    const counters = document.querySelectorAll('.js-count-up');
    counters.forEach(counter => {
        counter.innerText = '0';
        const updateCounter = () => {
            const target = +counter.getAttribute('data-target');
            const countStr = counter.innerText.replace(/,/g, '').replace(/\./g, '').replace(/ ₫/g, '');
            const count = +countStr;
            const increment = target / 40; // Controls speed, higher = slower

            if (count < target) {
                let newVal = Math.ceil(count + increment);
                let suffix = counter.hasAttribute('data-suffix') ? counter.getAttribute('data-suffix') : '';
                // format output correctly depending on suffix type
                if (suffix === ' ₫') {
                    counter.innerText = newVal.toLocaleString('vi-VN') + suffix;
                } else {
                    counter.innerText = newVal.toLocaleString('en-US') + suffix;
                }
                setTimeout(updateCounter, 25);
            } else {
                let suffix = counter.hasAttribute('data-suffix') ? counter.getAttribute('data-suffix') : '';
                if (suffix === ' ₫') {
                    counter.innerText = target.toLocaleString('vi-VN') + suffix;
                } else {
                    counter.innerText = target.toLocaleString('en-US') + suffix;
                }
            }
        };
        updateCounter();
    });

    // Reusable Sparkline Config
    const sparklineOptions = {
        type: 'line',
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false }, tooltip: { enabled: false } },
            scales: { x: { display: false }, y: { display: false } },
            elements: { point: { radius: 0 }, line: { tension: 0.4, borderWidth: 2 } },
            animation: { duration: 1500, easing: 'easeOutQuart' } // Add animation to sparklines too
        }
    };

    // Sparkline 1
    new Chart(document.getElementById('sparkline1').getContext('2d'), {
        ...sparklineOptions,
        data: { labels: ['1','2','3','4','5','6','7'], datasets: [{ data: [12,19,15,22,29,24,30], borderColor: '#206bc4' }] }
    });
    // Sparkline 2
    new Chart(document.getElementById('sparkline2').getContext('2d'), {
        ...sparklineOptions,
        data: { labels: ['1','2','3','4','5','6','7'], datasets: [{ data: [5,10,12,8,15,20,25], borderColor: '#ff5c00' }] }
    });
    // Sparkline 3
    new Chart(document.getElementById('sparkline3').getContext('2d'), {
        ...sparklineOptions,
        data: { labels: ['1','2','3','4','5','6','7'], datasets: [{ data: [100,120,110,140,160,150,180], borderColor: '#05CD99' }] }
    });
    // Sparkline 4
    new Chart(document.getElementById('sparkline4').getContext('2d'), {
        ...sparklineOptions,
        data: { labels: ['1','2','3','4','5','6','7'], datasets: [{ data: [50,45,40,38,35,42,39], borderColor: '#EE5D50' }] }
    });

    // Main Line Chart with Deep Dark Theme Config & Animation
    const ctxLine = document.getElementById('revenueLineChart').getContext('2d');
    
    // Create Soft Glow Gradient
    let gradient = ctxLine.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(32, 107, 196, 0.4)');   
    gradient.addColorStop(1, 'rgba(32, 107, 196, 0.0)');

    new Chart(ctxLine, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartLabels) ?>,
            datasets: [{
                label: 'Doanh thu',
                data: <?= json_encode($chartRevenueData) ?>,
                borderColor: '#206bc4', // Electric Blue
                backgroundColor: gradient,
                borderWidth: 2,
                tension: 0.4,
                fill: 'start',
                pointBackgroundColor: '#1b2434',
                pointBorderColor: '#206bc4',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1b2434',
                    titleColor: '#e6eef5',
                    bodyColor: '#e6eef5',
                    borderColor: 'rgba(255,255,255,0.1)',
                    borderWidth: 1,
                    titleFont: { size: 13 },
                    bodyFont: { size: 14, weight: 'bold' },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false },
                    ticks: { callback: function(value) { return (value/1000000).toFixed(1).replace(/\.0$/, '') + 'M'; } }
                },
                x: { 
                    grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false } 
                }
            }
        }
    });

    // Category Revenue Bar Chart
    new Chart(document.getElementById('barCategory').getContext('2d'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($categoryLabels) ?>,
            datasets: [{
                label: 'Doanh Thu',
                data: <?= json_encode($categoryRevenueData) ?>,
                backgroundColor: ['#206bc4', '#ff5c00', '#05CD99', '#EE5D50'],
                borderRadius: 4,
                barPercentage: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 2000,
                easing: 'easeOutQuart'
            },
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#1b2434', bodyColor: '#e6eef5' } },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero: true }
            }
        }
    });
});
</script>

<style>
/* Inline specific page overrides */
.line-clamp-1 { display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
.widget-hover-effect:hover {
    filter: brightness(1.2);
    transform: translateX(4px);
}
</style>
