<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// Xử lý bộ lọc
$keyword = $_GET['keyword'] ?? '';
$statusFilter = $_GET['status'] ?? '';

// Build Query
$sql = "SELECT b.*, c.name AS category_name, u.username AS seller_name 
        FROM bikes b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN users u ON b.user_id = u.id
        WHERE 1=1 ";
$params = [];

if ($keyword !== '') {
    // b.title tương ứng với tên xe, u.username tương ứng tên người bán
    $sql .= " AND (b.title LIKE ? OR u.username LIKE ?) ";
    $params[] = "%" . $keyword . "%";
    $params[] = "%" . $keyword . "%";
}

if ($statusFilter !== '') {
    if ($statusFilter == 'pending') {
        $sql .= " AND (b.status = 'pending' OR b.status = 'pending_delivery' OR b.status IS NULL OR b.status = '') ";
    } elseif ($statusFilter === 'sold') {
        $sql .= " AND b.status IN ('banned', 'sold', 'out_of_stock') ";
    } else {
        $sql .= " AND b.status = ? ";
        $params[] = $statusFilter;
    }
}
$sql .= " ORDER BY b.id DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $bikes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Lỗi truy xuất cơ sở dữ liệu: ' . htmlspecialchars($e->getMessage()) . '</div>';
    $bikes = [];
}
?>

<!-- Header Toolbar -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Kiểm duyệt & Quản lý Tin đăng</h2>
</div>

<!-- Filters Row -->
<div class="admin-card mb-4 border-0 shadow-sm rounded-4">
    <form method="GET" action="index.php" class="row g-3 align-items-end">
        <input type="hidden" name="page" value="admin_bikes">
        
        <div class="col-md-5">
            <label class="form-label fw-semibold text-muted small">Tìm kiếm theo tên xe hoặc người bán</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="keyword" class="form-control border-start-0 ps-0 bg-light" placeholder="Nhập từ khóa..." value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        
        <div class="col-md-3">
            <label class="form-label fw-semibold text-muted small">Trạng thái</label>
            <select name="status" class="form-select bg-light">
                <option value="">Tất cả trạng thái</option>
                <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Chờ duyệt</option>
                <option value="available" <?= $statusFilter === 'available' ? 'selected' : '' ?>>Đang hiển thị</option>
                <option value="sold" <?= $statusFilter === 'sold' ? 'selected' : '' ?>>Khóa / Hết hàng</option>
            </select>
        </div>
        
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn text-white fw-bold px-4 flex-grow-1" style="background-color: #FF5722;"><i class="fa-solid fa-filter me-2"></i> Lọc dữ liệu</button>
            <a href="?page=admin_bikes" class="btn btn-secondary px-3 fw-bold text-white"><i class="fa-solid fa-rotate-right"></i> Làm mới</a>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="admin-card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table table-hover table-admin align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="py-3">ID</th>
                    <th scope="col" class="py-3">Thông tin Xe</th>
                    <th scope="col" class="py-3">Giá bán</th>
                    <th scope="col" class="py-3">Người bán</th>
                    <th scope="col" class="py-3">Trạng thái</th>
                    <th scope="col" class="py-3 text-end">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($bikes)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-box-open fs-1 mb-3"></i>
                            <p class="mb-0">Không tìm thấy tin đăng nào phụ hợp với điều kiện.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($bikes as $bike): 
                        $sellerName = $bike['seller_name'] ?? 'Ẩn danh';
                        $statusRaw = $bike['status'] ?? '';
                        
                        // Xử lý status badge chuẩn xác theo if-else
                        $badgeHTML = '';
                        if ($statusRaw === 'available' || $statusRaw === 'active') {
                            $badgeHTML = '<span class="badge bg-success">Đang hiển thị</span>';
                        } elseif ($statusRaw === 'pending' || $statusRaw === 'pending_delivery' || $statusRaw === '') {
                            $badgeHTML = '<span class="badge bg-warning text-dark">Chờ duyệt</span>';
                        } elseif ($statusRaw === 'banned' || $statusRaw === 'out_of_stock' || $statusRaw === 'sold') {
                            $badgeHTML = '<span class="badge bg-danger">Khóa / Hết hàng</span>';
                        } else {
                            $badgeHTML = '<span class="badge bg-secondary">' . htmlspecialchars((string)$statusRaw) . '</span>';
                        }
                    ?>
                        <tr>
                            <td class="fw-semibold text-muted">#<?= $bike['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; min-width: 60px;">
                                        <?php if (!empty($bike['image_url'])): ?>
                                            <img src="public/uploads/bikes/<?= $bike['image_url'] ?>" onerror="this.src='public/assets/images/categories/road-bike.jpg'" alt="Bike" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;">
                                        <?php else: ?>
                                            <img src="public/assets/images/categories/road-bike.jpg" alt="Bike" style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #eee;">
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 1.05rem; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars((string)$bike['title'], ENT_QUOTES, 'UTF-8') ?>">
                                            <?= htmlspecialchars((string)$bike['title'], ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <?php if (!empty($bike['category_name'])): ?>
                                            <span class="badge bg-light text-secondary border mt-1"><i class="fa-solid fa-tag me-1"></i> <?= htmlspecialchars($bike['category_name'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td class="fw-bold" style="color: #FF5722; font-size: 1.05rem;"><?= number_format((float)$bike['price'], 0, ',', '.') ?> đ</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars((string)$sellerName, ENT_QUOTES, 'UTF-8') ?></span>
                                </div>
                            </td>
                            <td>
                                <?= $badgeHTML ?>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <!-- Nút Xem -->
                                    <a href="?page=bike-detail&id=<?= $bike['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-3 text-secondary d-flex align-items-center justify-content-center border-secondary" style="width: 34px; height: 34px;" title="Xem chi tiết trên Web">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    
                                    <!-- Nút Duyệt -->
                                    <a href="modules/admin/admin_bike_action.php?action=approve&id=<?= $bike['id'] ?>" class="btn btn-sm btn-outline-success rounded-3 text-success d-flex align-items-center justify-content-center border-success" style="width: 34px; height: 34px;" title="Duyệt">
                                        <i class="fa-solid fa-check"></i>
                                    </a>
                                    
                                    <!-- Nút Khóa -->
                                    <a href="modules/admin/admin_bike_action.php?action=ban&id=<?= $bike['id'] ?>" onclick="return confirm('Khóa tin này?');" class="btn btn-sm btn-outline-danger rounded-3 text-danger d-flex align-items-center justify-content-center border-danger" style="width: 34px; height: 34px;" title="Khóa/Từ chối">
                                        <i class="fa-solid fa-ban"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    const error = urlParams.get('error');

    if (msg === 'success') {
        Swal.fire({
            title: 'Thành công!',
            text: 'Cập nhật trạng thái tin đăng thành công.',
            icon: 'success',
            confirmButtonColor: '#FF5722'
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_bikes");
    } else if (error) {
        Swal.fire({
            title: 'Lỗi!',
            text: 'Không thể cập nhật trạng thái. Vui lòng thử lại sau.',
            icon: 'error',
            confirmButtonColor: '#dc3545'
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_bikes");
    }
</script>

<?php 
require_once __DIR__ . '/../../includes/admin/admin_footer.php'; 
?>
