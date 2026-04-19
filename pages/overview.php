<?php
declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$totalBikes = 5;
$totalViews = 1240;
$totalSold = 2;
?>

<div class="ct-dashboard-layout">
    
    <aside class="ct-sidebar">
        <a href="<?= BASE_URL ?>?page=home" class="ct-sidebar__brand">CYCLETRUST</a>
        
        <nav class="ct-sidebar__nav">
            <a href="<?= BASE_URL ?>?page=overview" class="ct-sidebar__link active">
                <i class="fa-solid fa-chart-pie ct-sidebar__icon"></i>
                Tổng quan
            </a>
            <a href="<?= BASE_URL ?>?page=my-postings" class="ct-sidebar__link">
                <i class="fa-solid fa-bicycle ct-sidebar__icon"></i>
                Xe đang bán
            </a>
            <a href="<?= BASE_URL ?>?page=user/orders" class="ct-sidebar__link">
                <i class="fa-solid fa-file-invoice-dollar ct-sidebar__icon"></i>
                Đơn hàng
            </a>
            <a href="<?= BASE_URL ?>?page=user/profile" class="ct-sidebar__link">
                <i class="fa-solid fa-user-gear ct-sidebar__icon"></i>
                Cài đặt tài khoản
            </a>
        </nav>
    </aside>

    <main class="ct-main-content">
        
        <div class="mb-5">
            <h1 class="ct-section-title" style="font-size: 1.8rem; margin-bottom: 4px;">Tổng quan hoạt động</h1>
            <p class="ct-section-subtitle m-0">Theo dõi hiệu quả bán hàng của bạn trên CycleTrust.</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 40px;">
            
            <div style="background: var(--surface); padding: 24px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(255, 87, 34, 0.1); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fa-solid fa-bicycle"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.9rem; font-weight: 600;">Xe đang bán</div>
                        <div style="font-size: 1.8rem; font-weight: 800; color: var(--ink);"><?= $totalBikes ?></div>
                    </div>
                </div>
            </div>

            <div style="background: var(--surface); padding: 24px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(16, 185, 129, 0.1); color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fa-solid fa-eye"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.9rem; font-weight: 600;">Lượt xem tin</div>
                        <div style="font-size: 1.8rem; font-weight: 800; color: var(--ink);"><?= number_format($totalViews) ?></div>
                    </div>
                </div>
            </div>

            <div style="background: var(--surface); padding: 24px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: rgba(59, 130, 246, 0.1); color: #3b82f6; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                        <i class="fa-solid fa-check-double"></i>
                    </div>
                    <div>
                        <div class="text-muted" style="font-size: 0.9rem; font-weight: 600;">Xe đã bán</div>
                        <div style="font-size: 1.8rem; font-weight: 800; color: var(--ink);"><?= $totalSold ?></div>
                    </div>
                </div>
            </div>

        </div>

<div style="background: var(--surface); padding: 24px; border-radius: var(--radius); border: 1px solid var(--border); box-shadow: var(--shadow-sm);">
            <h3 class="fw-700 mb-4" style="font-size: 1.2rem;">Thống kê lượt xem 7 ngày qua</h3>
            <canvas id="viewsChart" height="100"></canvas>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const ctx = document.getElementById('viewsChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'],
            datasets: [{
                label: 'Lượt xem',
                data: [120, 190, 150, 250, 220, 400, 380],
                borderColor: '#FF5722',
                backgroundColor: 'rgba(255, 87, 34, 0.1)',
                borderWidth: 3,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
                x: { grid: { display: false } }
            }
        }
    });
</script>