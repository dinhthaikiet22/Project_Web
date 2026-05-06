<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// Auto-migration bọc gọn: Tự động tạo bảng brands nếu chưa có để tránh crash
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS brands (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL UNIQUE,
        origin VARCHAR(100),
        description TEXT,
        image_url TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (PDOException $e) {
    // Nếu user không có quyền create table thì bỏ qua, lỗi sẽ in ở dưới
}

// Lấy danh sách thương hiệu & Số lượng xe
$brands = [];
try {
    // Giả định bảng bikes lưu tên hãng tại cột 'brand' dạng string
    // Bổ sung cú pháp COLLATE utf8mb4_unicode_ci để tránh lỗi Illegal mix of collations
    $sql = "SELECT br.id, br.name, br.origin, br.description, br.image_url,
            (CASE 
                WHEN br.name = 'Thương hiệu khác' THEN 
                    (SELECT COUNT(id) FROM bikes b2 
                     WHERE NOT EXISTS (
                         SELECT 1 FROM brands b3 
                         WHERE b3.name != 'Thương hiệu khác' 
                           AND b2.brand COLLATE utf8mb4_unicode_ci LIKE CONCAT('%', b3.name, '%') COLLATE utf8mb4_unicode_ci
                     ))
                ELSE 
                    (SELECT COUNT(id) FROM bikes b2 
                     WHERE b2.brand COLLATE utf8mb4_unicode_ci LIKE CONCAT('%', br.name, '%') COLLATE utf8mb4_unicode_ci)
            END) AS total_bikes
            FROM brands br
            ORDER BY br.id ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger mx-3 mt-3">Lỗi cơ sở dữ liệu: ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Quản lý Thương hiệu (Brands)</h2>
</div>

<div class="row g-4 align-items-start">
    <!-- Cột trái: Form Thêm Thương hiệu -->
    <div class="col-lg-4">
        <div class="admin-card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
            <div class="admin-card-title mb-4">
                <h4 class="h5 fw-bold mb-0"><i class="fa-solid fa-copyright text-primary me-2"></i>Thêm Hãng Xe Mới</h4>
            </div>

            <form action="modules/admin/admin_brand_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">

                <div class="mb-3">
                    <label for="brand_name" class="form-label fw-semibold">Tên hãng xe <span
                            class="text-danger">*</span></label>
                    <input type="text" id="brand_name" name="name" class="form-control bg-light"
                        placeholder="Ví dụ: Giant, Trek..." required>
                </div>

                <div class="mb-3">
                    <label for="brand_origin" class="form-label fw-semibold">Xuất xứ <span
                            class="text-muted fw-normal">(Quốc gia)</span></label>
                    <input type="text" id="brand_origin" name="origin" class="form-control bg-light"
                        placeholder="Ví dụ: Đài Loan, Mỹ...">
                </div>

                <div class="mb-3">
                    <label for="brand_desc" class="form-label fw-semibold">Mô tả ngắn</label>
                    <textarea id="brand_desc" name="description" class="form-control bg-light" rows="3"
                        placeholder="Thông tin về hãng xe..."></textarea>
                </div>

                <div class="mb-4">
                    <label for="brand_logo" class="form-label fw-semibold">Logo hãng xe</label>
                    <input type="file" id="brand_logo" name="logo" class="form-control bg-light" accept="image/*">
                </div>

                <button type="submit"
                    class="btn text-white fw-bold w-100 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm"
                    style="background-color: #FF5722; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'"
                    onmouseout="this.style.opacity='1'">
                    <i class="fa-solid fa-plus"></i> Tạo thương hiệu
                </button>
            </form>
        </div>
    </div>

    <!-- Cột phải: Bảng Dữ liệu -->
    <div class="col-lg-8">
        <div class="admin-card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover table-admin align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3" style="width: 50px;">ID</th>
                            <th scope="col" class="py-3">Thương hiệu</th>
                            <th scope="col" class="py-3 text-center">Số lượng Xe</th>
                            <th scope="col" class="py-3 text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($brands)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-copyright fs-1 mb-3"></i>
                                    <p class="mb-0">Hệ thống chưa có thương hiệu nào.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($brands as $brand): ?>
                                <tr>
                                    <td class="fw-semibold text-muted">#<?= $brand['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($brand['image_url'])): ?>
                                                <div class="border rounded p-1 bg-white d-flex align-items-center justify-content-center"
                                                    style="width: 50px; height: 50px;">
                                                    <img src="public/assets/images/brands/<?= htmlspecialchars((string) $brand['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                                                        alt="<?= htmlspecialchars($brand['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                        style="width: 50px; height: 50px; object-fit: contain;"
                                                        onerror="this.onerror=null; this.outerHTML='<i class=\'fa-solid fa-bicycle\' style=\'color: #FF5722; font-size: 1.25rem;\'></i>';">
                                                </div>
                                            <?php else: ?>
                                                <div class="bg-light border rounded d-flex align-items-center justify-content-center"
                                                    style="width: 50px; height: 50px; color: #FF5722; font-size: 1.25rem;">
                                                    <i class="fa-solid fa-bicycle"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                                                    <?= htmlspecialchars((string) $brand['name'], ENT_QUOTES, 'UTF-8') ?>
                                                    <?php if (!empty($brand['origin'])): ?>
                                                        <span class="badge bg-light text-secondary border fw-normal"
                                                            style="font-size: 0.70rem;"><i
                                                                class="fa-solid fa-earth-americas me-1"></i><?= htmlspecialchars((string) $brand['origin'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="text-muted small text-truncate" style="max-width: 250px;"
                                                    title="<?= htmlspecialchars((string) ($brand['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars((string) ($brand['description'] ?? 'Chưa có mô tả chi tiết'), ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <!-- Badge Cam nổi bật số lượng -->
                                        <span class="badge rounded-pill text-white shadow-sm"
                                            style="background-color: #FF5722; font-size: 0.85rem; padding: 0.4em 0.9em;">
                                            <i class="fa-solid fa-box me-1"></i> <?= $brand['total_bikes'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button
                                                onclick="editBrand(<?= $brand['id'] ?>, '<?= htmlspecialchars((string) $brand['name'], ENT_QUOTES, 'UTF-8') ?>')"
                                                class="btn btn-sm btn-outline-primary rounded-3 text-primary d-flex align-items-center justify-content-center border-primary"
                                                style="width: 34px; height: 34px;" title="Sửa tên">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button onclick="deleteBrand(<?= $brand['id'] ?>, <?= $brand['total_bikes'] ?>)"
                                                class="btn btn-sm btn-outline-danger rounded-3 text-danger d-flex align-items-center justify-content-center border-danger"
                                                style="width: 34px; height: 34px;" title="Xóa">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Xử lý thông báo URL như Categories
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    const error = urlParams.get('error');

    if (msg === 'add_success') {
        Swal.fire({ title: 'Thành công!', text: 'Thương hiệu đã được thêm vào hệ thống.', icon: 'success', confirmButtonColor: '#FF5722' });
    } else if (msg === 'edit_success') {
        Swal.fire({ title: 'Thành công!', text: 'Tên thương hiệu đã được cập nhật.', icon: 'success', confirmButtonColor: '#FF5722' });
    } else if (msg === 'delete_success') {
        Swal.fire({ title: 'Đã xóa!', text: 'Thương hiệu đã bị gỡ bỏ thành công.', icon: 'success', confirmButtonColor: '#FF5722' });
    } else if (error === 'has_bikes') {
        Swal.fire({ title: 'Lỗi - Không thể xóa!', text: 'Thương hiệu này đang chứa các tin đăng xe. Hãy xóa hoặc điều chuyển xe trước!', icon: 'error', confirmButtonColor: '#dc3545' });
    } else if (error === 'db_error') {
        Swal.fire({ title: 'Lỗi DB!', text: 'Đã có lỗi xảy ra hoặc Tên Hãng bị trùng lặp.', icon: 'error' });
    }

    function deleteBrand(id, bikeCount) {
        if (bikeCount > 0) {
            Swal.fire({
                title: 'Hệ thống từ chối xóa!',
                text: `Thương hiệu này đang có ${bikeCount} tin đăng. Bạn không thể xóa để bảo toàn dữ liệu cho Seller!`,
                icon: 'warning',
                confirmButtonColor: '#6c757d'
            });
            return;
        }

        Swal.fire({
            title: 'Bạn có chắc chắn?',
            text: "Hành động này sẽ xóa vĩnh viễn Thương hiệu!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#FF5722',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Đồng ý xóa!',
            cancelButtonText: 'Hủy'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'modules/admin/admin_brand_action.php?action=delete&id=' + id;
            }
        });
    }

    function editBrand(id, currentName) {
        Swal.fire({
            title: 'Đổi tên Thương hiệu',
            input: 'text',
            inputValue: currentName,
            showCancelButton: true,
            confirmButtonText: 'Lưu thay đổi',
            confirmButtonColor: '#FF5722',
            cancelButtonText: 'Hủy',
            inputValidator: (value) => {
                if (!value) return 'Tên thương hiệu không được để trống!';
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const newName = result.value;
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'modules/admin/admin_brand_action.php';

                [{ name: 'action', value: 'edit' }, { name: 'id', value: id }, { name: 'name', value: newName }].forEach(item => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = item.name;
                    input.value = item.value;
                    form.appendChild(input);
                });
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>