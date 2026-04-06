<?php
declare(strict_types=1);

// BẢO MẬT: Kiểm tra quyền Admin
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized Access: Bạn không có quyền truy cập vào endpoint này!');
}

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// Nhận action từ POST (Thêm, Sửa) hoặc GET (Xóa)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    // 1. THÊM DANH MỤC
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($name === '') {
            header("Location: ../../index.php?page=admin_categories&error=invalid_data");
            exit;
        }

        // Xử lý Upload Icon (nếu có)
        // Hiện tại xử lý cơ bản, nếu DB hỗ trợ thì lưu Tên File, nếu không thì lưu ảnh default.
        $iconName = '';
        if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['icon']['tmp_name'];
            $iconName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_FILES['icon']['name']);
            $destPath = __DIR__ . '/../../public/uploads/categories/' . $iconName;
            
            // Tạo thư mục nếu chưa tồn tại
            if (!is_dir(__DIR__ . '/../../public/uploads/categories/')) {
                mkdir(__DIR__ . '/../../public/uploads/categories/', 0777, true);
            }
            
            move_uploaded_file($tmpPath, $destPath);
        }

        // Lệnh INSERT an toàn với PDO, Fallback bỏ cột description/image_url nếu schema cũ chưa Migrate
        try {
            $stmt = $conn->prepare("INSERT INTO categories (name, description, image_url) VALUES (?, ?, ?)");
            $stmt->execute([$name, $description, $iconName]);
        } catch (PDOException $e) {
            // Tự fallback gọi Insert cơ bản
            $stmt2 = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt2->execute([$name]);
        }
        
        header("Location: ../../index.php?page=admin_categories&msg=add_success");
        exit;
    }
    
    // 2. SỬA TÊN DANH MỤC
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if ($id > 0 && $name !== '') {
            $stmt = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
        }
        
        header("Location: ../../index.php?page=admin_categories&msg=edit_success");
        exit;
    }
    
    // 3. XÓA DANH MỤC
    if ($action === 'delete') {
        $id = (int)($_GET['id'] ?? 0);
        
        // Cảnh báo Orphan Data: Kiểm tra có xe nào bị dính với khóa ngoại category_id này không
        if ($id > 0) {
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM bikes WHERE category_id = ?");
            $checkStmt->execute([$id]);
            $count = (int)$checkStmt->fetchColumn();
            
            if ($count > 0) {
                header("Location: ../../index.php?page=admin_categories&error=has_bikes");
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        header("Location: ../../index.php?page=admin_categories&msg=delete_success");
        exit;
    }
    
    // Mặc định
    header("Location: ../../index.php?page=admin_categories");
    exit;

} catch (PDOException $e) {
    error_log("Admin Category DB Error: " . $e->getMessage());
    header("Location: ../../index.php?page=admin_categories&error=db_error");
    exit;
}
