<?php
/**
 * ĐẾM SỐ TIN NHẮN CHƯA ĐỌC
 * ----------------------------------------------------
 * Tối ưu hóa truy vấn SQL gọn nhẹ để đếm số hộp thư chưa đọc
 * dùng cho hiển thị Badge trên thanh header.
 */
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');

// Chỉ tính toán khi người dùng đã đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = require __DIR__ . '/../../config/db.php';
$myId = (int)$_SESSION['user_id'];

try {
    // Đếm số lượng các ID tin nhắn mà mình là người nhận và có is_read = 0
    $stmt = $conn->prepare("SELECT COUNT(id) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$myId]);
    $unreadCount = (int)$stmt->fetchColumn();
    
    echo json_encode(['status' => 'success', 'unread_count' => $unreadCount]);
} catch (PDOException $e) {
    // Báo lỗi 500 nêú truy vấn fail
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Lỗi truy xuất dữ liệu đếm tin nhắn']);
}
