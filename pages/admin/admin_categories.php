<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// Fetch categories with bike count
try {
    // Thử truy vấn có các cột mở rộng (description, image_url)
    $sql = "SELECT c.id, c.name, c.description, c.image_url, 
            (SELECT COUNT(*) FROM bikes b WHERE b.category_id = c.id) AS bike_count
            FROM categories c
            ORDER BY c.id DESC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback: Nếu CSDL cũ chưa có cột description/image_url thì bỏ qua để không sập trang
    try {
        $sqlFallback = "SELECT c.id, c.name, '' AS description, '' AS image_url,
                (SELECT COUNT(*) FROM bikes b WHERE b.category_id = c.id) AS bike_count
                FROM categories c
                ORDER BY c.id DESC";
        $stmt = $conn->prepare($sqlFallback);
        $stmt->execute();
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {
        $categories = [];
        echo '<div class="alert alert-danger">Lỗi cơ sở dữ liệu: ' . htmlspecialchars($e2->getMessage()) . '</div>';
    }
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Quản lý Danh mục (Categories)</h2>
</div>

<div class="row g-4 align-items-start">
    <!-- Cột trái: Form Thêm Danh mục (4 col) -->
    <div class="col-lg-4">
        <div class="admin-card border-0 shadow-sm rounded-4 sticky-top" style="top: 20px;">
            <div class="admin-card-title mb-4">
                <h4 class="h5 fw-bold mb-0"><i class="fa-solid fa-folder-plus text-primary me-2"></i>Thêm Danh Mục Mới</h4>
            </div>
            
            <form action="modules/admin/admin_category_action.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label for="cat_name" class="form-label fw-semibold">Tên danh mục <span class="text-danger">*</span></label>
                    <input type="text" id="cat_name" name="name" class="form-control bg-light" placeholder="Ví dụ: Xe Đạp Thể Thao..." required>
                </div>
                
                <div class="mb-3">
                    <label for="cat_desc" class="form-label fw-semibold">Mô tả ngắn</label>
                    <textarea id="cat_desc" name="description" class="form-control bg-light" rows="3" placeholder="Mô tả công năng, dòng xe..."></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="cat_icon" class="form-label fw-semibold">Icon / Ảnh đại diện (nếu có)</label>
                    <input type="file" id="cat_icon" name="icon" class="form-control bg-light" accept="image/*">
                </div>
                
                <button type="submit" class="btn text-white fw-bold w-100 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background-color: #FF5722; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <i class="fa-solid fa-plus"></i> Tạo danh mục
                </button>
            </form>
        </div>
    </div>

    <!-- Cột phải: Bảng Dữ liệu (8 col) -->
    <div class="col-lg-8">
        <div class="admin-card border-0 shadow-sm rounded-4">
            <div class="table-responsive">
                <table class="table table-hover table-admin align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3" style="width: 50px;">ID</th>
                            <th scope="col" class="py-3">Thông tin Danh mục</th>
                            <th scope="col" class="py-3 text-center">Số lượng Xe</th>
                            <th scope="col" class="py-3 text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fs-1 mb-3"></i>
                                    <p class="mb-0">Hệ thống chưa có danh mục nào.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td class="fw-semibold text-muted">#<?= $cat['id'] ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if (!empty($cat['image_url'])): ?>
                                                <img src="<?= str_starts_with((string)$cat['image_url'], 'http') ? htmlspecialchars((string)$cat['image_url'], ENT_QUOTES, 'UTF-8') : 'public/uploads/categories/' . rawurlencode((string)$cat['image_url']) ?>" alt="icon" style="width: 45px; height: 45px; border-radius: 8px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; color: #FF5722; font-size: 1.25rem;">
                                                    <i class="fa-regular fa-folder"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold text-dark fs-6"><?= htmlspecialchars((string)$cat['name'], ENT_QUOTES, 'UTF-8') ?></div>
                                                <div class="text-muted small text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars((string)($cat['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <?= htmlspecialchars((string)($cat['description'] ?? 'Chưa có mô tả chi tiết'), ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <!-- Badge Cam nổi bật số lượng -->
                                        <span class="badge rounded-pill text-white shadow-sm" style="background-color: #FF5722; font-size: 0.85rem; padding: 0.4em 0.9em;">
                                            <i class="fa-solid fa-bicycle me-1"></i> <?= $cat['bike_count'] ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end gap-2">
                                            <button onclick="editCategory(<?= $cat['id'] ?>, '<?= htmlspecialchars((string)$cat['name'], ENT_QUOTES, 'UTF-8') ?>')" class="btn btn-sm btn-outline-primary rounded-3 text-primary d-flex align-items-center justify-content-center border-primary" style="width: 34px; height: 34px;" title="Sửa tên">
                                                <i class="fa-solid fa-pen"></i>
                                            </button>
                                            <button onclick="deleteCategory(<?= $cat['id'] ?>, <?= $cat['bike_count'] ?>)" class="btn btn-sm btn-outline-danger rounded-3 text-danger d-flex align-items-center justify-content-center border-danger" style="width: 34px; height: 34px;" title="Xóa">
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
// Nhận message từ URL để showAlert
const urlParams = new URLSearchParams(window.location.search);
const msg = urlParams.get('msg');
const error = urlParams.get('error');

if (msg === 'add_success') {
    Swal.fire({ title: 'Thành công!', text: 'Đã thêm danh mục mới vào hệ thống.', icon: 'success', confirmButtonColor: '#FF5722' });
} else if (msg === 'edit_success') {
    Swal.fire({ title: 'Thành công!', text: 'Tên danh mục đã được cập nhật.', icon: 'success', confirmButtonColor: '#FF5722' });
} else if (msg === 'delete_success') {
    Swal.fire({ title: 'Đã xóa!', text: 'Danh mục đã bị gỡ bỏ rỗng.', icon: 'success', confirmButtonColor: '#FF5722' });
} else if (error === 'has_bikes') {
    Swal.fire({ title: 'Lỗi - Không thể xóa!', text: 'Danh mục này đang chứa xe đạp của người bán. Hãy chuyển dữ liệu sang danh mục khác rồi mới có thể xóa!', icon: 'error', confirmButtonColor: '#dc3545' });
} else if (error === 'db_error') {
    Swal.fire({ title: 'Lỗi DB!', text: 'Có lỗi xảy ra trong quá trình cập nhật CSDL.', icon: 'error' });
}

// Hàm Xóa có bắt Orphan Data
function deleteCategory(id, bikeCount) {
    if (bikeCount > 0) {
        Swal.fire({
            title: 'Hệ thống từ chối xóa!', 
            text: `Danh mục này đang có ${bikeCount} chiếc xe. Bạn không thể xóa để tránh rác dữ liệu!`, 
            icon: 'warning',
            confirmButtonColor: '#6c757d'
        });
        return;
    }
    
    Swal.fire({
        title: 'Bạn có chắc chắn?',
        text: "Hành động này sẽ xóa vĩnh viễn danh mục!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#FF5722',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Đồng ý xóa!',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'modules/admin/admin_category_action.php?action=delete&id=' + id;
        }
    });
}

// Xử lý Sửa tên nhanh bằng Popup của Swal
function editCategory(id, currentName) {
    Swal.fire({
        title: 'Chỉnh sửa Tên Danh mục',
        input: 'text',
        inputValue: currentName,
        showCancelButton: true,
        confirmButtonText: 'Lưu thay đổi',
        confirmButtonColor: '#FF5722',
        cancelButtonText: 'Hủy',
        inputValidator: (value) => {
            if (!value) {
                return 'Tên danh mục không được để trống!'
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const newName = result.value;
            // Fake 1 Form submit bằng JS cực an toàn thay vì bắn GET
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'modules/admin/admin_category_action.php';
            
            const arr = [
                { name: 'action', value: 'edit' },
                { name: 'id', value: id },
                { name: 'name', value: newName }
            ];
            
            arr.forEach(item => {
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
