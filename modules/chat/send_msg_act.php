<?php
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$conn = require __DIR__ . '/../../config/db.php';
$myId = (int)$_SESSION['user_id'];

$receiverId = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$bikeId = isset($_POST['bike_id']) ? (int)$_POST['bike_id'] : null;
$message = trim((string)($_POST['message'] ?? ''));

if ($receiverId <= 0 || $message === '') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

// Convert empty/0 bike_id to null for DB insert
if ($bikeId !== null && $bikeId <= 0) {
    $bikeId = null;
}

try {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, bike_id, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
    $stmt->execute([$myId, $receiverId, $bikeId, $message]);
    
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
