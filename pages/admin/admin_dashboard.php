<?php
declare(strict_types=1);

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// Initialize defaults to prevent UI breaking
$totalRevenue = 0;
$totalOrders = 0;
$totalUsers = 0;
$totalBikes = 0;
$rawSevenDaysData = [];
$rawStatusData = [];
$recentTransactions = [];

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

    // Tầng Visuals - Line Chart (Doanh thu 7 ngày qua)
    $sevenDaysQuery = $conn->query("
        SELECT DATE(created_at) as order_date, SUM(total_price) as sum_price 
        FROM orders 
        WHERE order_status IN ('paid', 'completed') 
          AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        GROUP BY DATE(created_at)
    ");
    $rawSevenDaysData = $sevenDaysQuery->fetchAll(PDO::FETCH_ASSOC);

    // Doughnut Chart (Trạng thái đơn hàng)
    $statusQuery = $conn->query("SELECT order_status, COUNT(*) as count_status FROM orders GROUP BY order_status");
    $rawStatusData = $statusQuery->fetchAll(PDO::FETCH_KEY_PAIR);

    // Tầng Data - 5 giao dịch mới nhất
    $transactionsQuery = $conn->query("SELECT * FROM transactions ORDER BY created_at DESC LIMIT 5");
    $recentTransactions = $transactionsQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Hiển thị lỗi ra console hoặc file log (Tránh quăng hỏng mảng UI)
    echo '<div class="alert alert-danger">Lỗi truy xuất cơ sở dữ liệu Dashboard: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Map data into exactly the last 7 days to ensure no missing dates and 100% truthful zeros
$chartLabels = [];
$chartRevenueData = [];
for ($i = 6; $i >= 0; $i--) {
    $dateStr = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d/m', strtotime($dateStr)); // Format: DD/MM
    
    $revenueAmount = 0;
    foreach ($rawSevenDaysData as $dataItem) {
        if ($dataItem['order_date'] === $dateStr) {
            $revenueAmount = (float)$dataItem['sum_price'];
            break;
        }
    }
    $chartRevenueData[] = $revenueAmount;
}

// Provide default values 0 if a status hasn't occurred yet
$statusLabels = ['Waiting Payment', 'Paid', 'Completed', 'Cancelled'];
$statusDataCount = [
    $rawStatusData['waiting_payment'] ?? 0,
    $rawStatusData['paid'] ?? 0,
    $rawStatusData['completed'] ?? 0,
    $rawStatusData['cancelled'] ?? 0
];

function formatVND($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' ₫';
}

function getTxStatusBadge($status) {
    switch ($status) {
        case 'success':
            return '<span class="badge bg-success">Success</span>';
        case 'pending':
            return '<span class="badge bg-warning text-dark">Pending</span>';
        case 'failed':
            return '<span class="badge bg-danger">Failed</span>';
        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars(strval($status)) . '</span>';
    }
}
?>

<!-- KPI Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-wallet"></i></div>
            <div class="kpi-details">
                <p>Doanh Thu</p>
                <h3><?= formatVND($totalRevenue) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <div class="kpi-details">
                <p>Đơn Hàng</p>
                <h3><?= number_format((float)$totalOrders) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-users"></i></div>
            <div class="kpi-details">
                <p>Khách Hàng</p>
                <h3><?= number_format((float)$totalUsers) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-icon"><i class="fa-solid fa-motorcycle"></i></div>
            <div class="kpi-details">
                <p>Xe Đang Bán</p>
                <h3><?= number_format((float)$totalBikes) ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <div class="col-md-8">
        <div class="admin-card">
            <div class="admin-card-title">Doanh Thu 7 Ngày Qua</div>
            <canvas id="revenueChart" height="100"></canvas>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-card">
            <div class="admin-card-title">Tỷ Lệ Đơn Hàng</div>
            <canvas id="orderStatusChart" height="200"></canvas>
        </div>
    </div>
</div>

<!-- Recent Transactions List -->
<div class="row mt-2">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-title">5 Giao Dịch Mới Nhất</div>
            <div class="table-responsive">
                <table class="table table-hover table-admin">
                    <thead>
                        <tr>
                            <th>Mã GD</th>
                            <th>Thời Gian</th>
                            <th>Loại</th>
                            <th>Số Tiền</th>
                            <th>Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTransactions)): ?>
                        <tr><td colspan="5" class="text-center py-4">Chưa có giao dịch nào</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentTransactions as $tx): ?>
                            <tr>
                                <td><strong class="text-primary"><?= htmlspecialchars($tx['transaction_code'] ?? 'N/A') ?></strong></td>
                                <td><?= date('H:i d/m/Y', strtotime($tx['created_at'])) ?></td>
                                <td><span class="text-uppercase" style="font-size: 0.85rem; font-weight: 600;"><?= htmlspecialchars($tx['type']) ?></span></td>
                                <td class="fw-bold"><?= formatVND($tx['amount']) ?></td>
                                <td><?= getTxStatusBadge($tx['status']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Line Chart Data
    const revenueLabels = <?= json_encode($chartLabels) ?>;
    const revenueData = <?= json_encode($chartRevenueData) ?>;

    const ctxRev = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRev, {
        type: 'line',
        data: {
            labels: revenueLabels,
            datasets: [{
                label: 'Doanh Thu (VND)',
                data: revenueData,
                borderColor: '#FF5722',
                backgroundColor: 'rgba(255, 87, 34, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#FF5722',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });

    // Orders Doughnut Chart Data
    const statusLabels = <?= json_encode($statusLabels) ?>;
    const statusData = <?= json_encode($statusDataCount) ?>;

    const ctxStatus = document.getElementById('orderStatusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: ['#ffc107', '#17a2b8', '#28a745', '#dc3545'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
</script>
