<?php
declare(strict_types=1);

session_start();
header('Content-Type: application/json');

// Kiểm tra quyền truy cập (Người dùng phải đăng nhập)
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'unauthorized']);
    exit;
}

// Chỉ chấp nhận method POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$bike_id = isset($_POST['bike_id']) ? (int)$_POST['bike_id'] : 0;
$user_id = (int)$_SESSION['user_id'];

if ($bike_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid bike ID.']);
    exit;
}

try {
    /** @var PDO $conn */
    $conn = require_once __DIR__ . '/../config/db.php';

    // Kiểm tra xem xe đã được thêm vào yêu thích hay chưa
    $stmt = $conn->prepare("SELECT id FROM favorites WHERE user_id = ? AND bike_id = ?");
    $stmt->execute([$user_id, $bike_id]);
    $favorite = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($favorite) {
        // Đã có trong danh sách -> Bỏ yêu thích
        $deleteStmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND bike_id = ?");
        $deleteStmt->execute([$user_id, $bike_id]);
        echo json_encode(['status' => 'success', 'action' => 'removed']);
    } else {
        // Chưa có -> Thêm vào yêu thích
        $insertStmt = $conn->prepare("INSERT INTO favorites (user_id, bike_id) VALUES (?, ?)");
        $insertStmt->execute([$user_id, $bike_id]);
        echo json_encode(['status' => 'success', 'action' => 'added']);
    }
} catch (PDOException $e) {
    // Có lỗi về database
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred.']);
}
