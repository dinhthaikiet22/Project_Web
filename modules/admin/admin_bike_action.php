<?php
declare(strict_types=1);

// 1. KHỞI TẠO SESSION & BẢO MẬT TUYỆT ĐỐI
session_start();

// Kiểm tra quyền: Chỉ cho phép admin truy cập
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized Access: Bạn không có quyền thực hiện thao tác này!');
}

// 2. KẾT NỐI DATABASE
/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// 3. NHẬN VÀ KIỂM TRA THAM SỐ TỪ URL
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0 || empty($action)) {
    header("Location: ../../index.php?page=admin_bikes&error=invalid_request");
    exit;
}

try {
    // 4. XỬ LÝ LOGIC THEO TỪNG ACTION (PDO)
    $sql = "";
    $params = [$id];

    switch ($action) {
        case 'approve':
            // Duyệt tin đăng -> Chuyển thành trạng thái hoạt động (active)
            $sql = "UPDATE bikes SET status = 'active' WHERE id = ?";
            break;
            
        case 'ban':
            // Khóa/từ chối tin đăng -> Chuyển thành trạng thái cấm (banned)
            $sql = "UPDATE bikes SET status = 'banned' WHERE id = ?";
            break;
            
        case 'delete':
            // Xóa cứng khỏi database
            $sql = "DELETE FROM bikes WHERE id = ?";
            break;
            
        default:
            header("Location: ../../index.php?page=admin_bikes&error=unknown_action");
            exit;
    }

    if ($sql !== "") {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
    }
    
    // 5. PHẢN HỒI (REDIRECT) THÀNH CÔNG VỀ FRONTEND
    header("Location: ../../index.php?page=admin_bikes&msg=success");
    exit;
    
} catch (PDOException $e) {
    // Ghi log lỗi nếu cần thiết và redirect về kèm mã lỗi để Frontend xử lý (không show trực tiếp mã lỗi)
    error_log("Admin action fail: " . $e->getMessage());
    header("Location: ../../index.php?page=admin_bikes&error=db_error");
    exit;
}
