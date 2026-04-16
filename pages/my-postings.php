<?php
declare(strict_types=1);

// --- PHẦN 1: CODE XỬ LÝ DỮ LIỆU (BACKEND) ---

// TẠM THỜI ẨN KIỂM TRA ĐĂNG NHẬP ĐỂ BẠN THIẾT KẾ UI
/*
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}
*/

// Giả lập user_id = 1 để lấy dữ liệu ra test giao diện (nếu chưa đăng nhập)
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1; 

/** @var PDO $conn */
$conn = require __DIR__ . '/../config/db.php';

$stmt = $conn->prepare('SELECT * FROM bikes WHERE user_id = :user_id ORDER BY created_at DESC');
$stmt->execute([':user_id' => $userId]);
$bikes = $stmt->fetchAll() ?: [];

$defaultBikeOnError = BASE_URL . 'public/assets/images/default-bike.jpg';

function bikeStatusLabel(array $bike): array
{
    if (isset($bike['status']) && is_string($bike['status'])) {
        $raw = trim($bike['status']);
        if ($raw !== '') {
            $isSold = in_array(mb_strtolower($raw), ['sold', 'đã bán', 'da ban'], true);
            // Thay class badge của Bootstrap thành class ct-status của giao diện mới
            return [$isSold ? 'Đã bán' : 'Đang bán', $isSold ? 'ct-status ct-status--rejected' : 'ct-status ct-status--approved'];
        }
    }

    if (array_key_exists('is_sold', $bike)) {
        $isSold = (int)$bike['is_sold'] === 1;
        return [$isSold ? 'Đã bán' : 'Đang bán', $isSold ? 'ct-status ct-status--rejected' : 'ct-status ct-status--approved'];
    }

    return ['Đang bán', 'ct-status ct-status--approved'];
}
?>

<div class="ct-dashboard-layout">
    
    <aside class="ct-sidebar">
        <a href="<?= BASE_URL ?>?page=home" class="ct-sidebar__brand">CYCLETRUST</a>
        
        <nav class="ct-sidebar__nav">
            <a href="#" class="ct-sidebar__link">
                <i class="fa-solid fa-chart-pie ct-sidebar__icon"></i>
                Tổng quan
            </a>
            <a href="#" class="ct-sidebar__link active">
                <i class="fa-solid fa-bicycle ct-sidebar__icon"></i>
                Xe đang bán
            </a>
            <a href="#" class="ct-sidebar__link">
                <i class="fa-solid fa-file-invoice-dollar ct-sidebar__icon"></i>
                Đơn hàng
            </a>
            <a href="#" class="ct-sidebar__link">
                <i class="fa-solid fa-user-gear ct-sidebar__icon"></i>
                Cài đặt tài khoản
            </a>
        </nav>
    </aside>

    <main class="ct-main-content">
        
        <div class="d-flex align-items-center justify-content-between mb-4" style="display: flex; justify-content: space-between; align-items: flex-end;">
            <div>
                <h1 class="ct-section-title" style="font-size: 1.8rem; margin-bottom: 4px;">Tin đăng của tôi</h1>
                <p class="ct-section-subtitle m-0">Quản lý các tin bạn đã đăng trên CycleTrust</p>
            </div>
            <a href="<?= BASE_URL ?>?page=post-bike" class="btn btn-primary" style="padding: 10px 18px;">
                <i class="fa-solid fa-plus"></i> Đăng xe mới
            </a>
        </div>

        <?php if (empty($bikes)): ?>
            <div class="surface p-5 text-center" style="box-shadow: var(--shadow-sm); border-radius: var(--radius-lg); padding: 60px 20px;">
                <i class="fa-solid fa-bicycle" style="font-size: 3rem; color: var(--border-focus); margin-bottom: 16px;"></i>
                <h2 class="h5 mb-2 fw-700">Bạn chưa có tin đăng nào</h2>
                <p class="text-muted mb-4">Hãy đăng tin đầu tiên để bắt đầu bán xe đạp của bạn.</p>
                <a class="btn btn-primary" href="<?= BASE_URL ?>?page=post-bike">
                    Đăng tin ngay
                </a>
            </div>
        <?php else: ?>
            <div class="ct-table-wrapper">
                <table class="ct-table">
                    <thead>
                        <tr>
                            <th>Thông tin xe</th>
                            <th>Ngày đăng</th>
                            <th>Giá bán</th>
                            <th>Trạng thái</th>
                            <th style="text-align: right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bikes as $row): ?>
                            <?php
                            // Xử lý dữ liệu từng dòng
                            $title = (string)($row['title'] ?? '');
                            $imageRaw = trim((string)($row['image_url'] ?? ''));
                            if ($imageRaw === '') {
                                $imageFile = $defaultBikeOnError;
                            } elseif (str_starts_with(strtolower($imageRaw), 'http')) {
                                $imageFile = $imageRaw;
                            } else {
                                $imageFile = BASE_URL . 'public/uploads/products/' . $imageRaw;
                            }
                            $price = isset($row['price']) ? (float)$row['price'] : 0;
                            $createdAt = (string)($row['created_at'] ?? '');
                            $createdLabel = $createdAt !== '' ? date('d/m/Y', strtotime($createdAt)) : '-';
                            [$statusLabel, $statusClass] = bikeStatusLabel(is_array($row) ? $row : []);
                            $id = isset($row['id']) ? (int)$row['id'] : 0;
                            ?>
                            
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 70px; height: 50px; background: #e5e7eb; border-radius: 6px; overflow: hidden; flex-shrink: 0;">
                                            <img src="<?= htmlspecialchars($imageFile, ENT_QUOTES, 'UTF-8') ?>" 
                                                 alt="Bike" 
                                                 style="width: 100%; height: 100%; object-fit: cover;"
                                                 onerror="this.src='<?= $defaultBikeOnError ?>';">
                                        </div>
                                        <div>
                                            <div class="fw-700" style="color: var(--ink);"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="text-muted" style="font-size: 0.85rem;">ID: #<?= $id ?></div>
                                        </div>
                                    </div>
                                </td>
                                
                                <td class="text-muted" style="font-size: 0.95rem;"><?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                
                                <td class="fw-700" style="color: var(--primary);">
                                    <?= number_format($price, 0, ',', '.') ?> đ
                                </td>
                                
                                <td><span class="<?= htmlspecialchars($statusClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                                
                                <td style="text-align: right;">
                                    <div style="display: flex; justify-content: flex-end; gap: 6px;">
                                        <a href="<?= BASE_URL ?>?page=edit-bike&id=<?= $id ?>" class="btn btn-ghost" style="padding: 6px 10px;" title="Sửa tin">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>?page=delete-bike&id=<?= $id ?>" class="btn btn-ghost" style="padding: 6px 10px; color: var(--danger);" title="Xóa tin">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </main>
</div>