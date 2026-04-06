<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized Access: Bạn không có quyền truy cập vào endpoint này!');
}

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    // 1. THÊM THƯƠNG HIỆU
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $origin = trim($_POST['origin'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        if ($name === '') {
            header("Location: ../../index.php?page=admin_brands&error=invalid_data");
            exit;
        }

        // Xử lý Upload Logo
        $logoName = '';
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $tmpPath = $_FILES['logo']['tmp_name'];
            $logoName = time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_FILES['logo']['name']);
            $destPath = __DIR__ . '/../../public/uploads/brands/' . $logoName;
            
            if (!is_dir(__DIR__ . '/../../public/uploads/brands/')) {
                mkdir(__DIR__ . '/../../public/uploads/brands/', 0777, true);
            }
            
            move_uploaded_file($tmpPath, $destPath);
        }

        $stmt = $conn->prepare("INSERT INTO brands (name, origin, description, image_url) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $origin, $description, $logoName]);
        
        header("Location: ../../index.php?page=admin_brands&msg=add_success");
        exit;
    }
    
    // 2. SỬA TÊN THƯƠNG HIỆU
    if ($action === 'edit') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        
        if ($id > 0 && $name !== '') {
            $stmt = $conn->prepare("UPDATE brands SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            
            // Tùy chọn: Nếu muốn đồng bộ tên brand trong bảng bikes thì chạy thêm update bảng bikes
            // Nhưng có thể không cần thiết nếu brand name đổi ít.
        }
        
        header("Location: ../../index.php?page=admin_brands&msg=edit_success");
        exit;
    }
    
    // 3. XÓA THƯƠNG HIỆU
    if ($action === 'delete') {
        $id = (int)($_GET['id'] ?? 0);
        
        if ($id > 0) {
            // Lấy tên brand để check bảng bikes
            $getNameStmt = $conn->prepare("SELECT name FROM brands WHERE id = ?");
            $getNameStmt->execute([$id]);
            $brandName = (string)$getNameStmt->fetchColumn();
            
            if ($brandName !== '') {
                // Thêm COLLATE utf8mb4_unicode_ci để tránh lỗi Illegal mix of collations
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM bikes WHERE brand COLLATE utf8mb4_unicode_ci = ?");
                $checkStmt->execute([$brandName]);
                $count = (int)$checkStmt->fetchColumn();
                
                if ($count > 0) {
                    header("Location: ../../index.php?page=admin_brands&error=has_bikes");
                    exit;
                }
            }
            
            $stmt = $conn->prepare("DELETE FROM brands WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        header("Location: ../../index.php?page=admin_brands&msg=delete_success");
        exit;
    }
    
    header("Location: ../../index.php?page=admin_brands");
    exit;

} catch (PDOException $e) {
    error_log("Admin Brand DB Error: " . $e->getMessage());
    header("Location: ../../index.php?page=admin_brands&error=db_error");
    exit;
}
