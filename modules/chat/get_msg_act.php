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
$lastMsgId = isset($_POST['last_msg_id']) ? (int)$_POST['last_msg_id'] : 0;

if ($receiverId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid receiver']);
    exit;
}

try {
    // If last_msg_id is 0, we can fetch all or last N messages. Let's fetch last 50.
    if ($lastMsgId == 0) {
        $stmt = $conn->prepare("
            SELECT id, sender_id, message, created_at
            FROM messages
            WHERE ((sender_id = ? AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = ?))
            ORDER BY id DESC
            LIMIT 50
        ");
        $stmt->execute([$myId, $receiverId, $receiverId, $myId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rows = array_reverse($rows); // order ascending chronological
    } else {
        // Fetch only new messages
        $stmt = $conn->prepare("
            SELECT id, sender_id, message, created_at
            FROM messages
            WHERE ((sender_id = ? AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = ?))
              AND id > ?
            ORDER BY id ASC
        ");
        $stmt->execute([$myId, $receiverId, $receiverId, $myId, $lastMsgId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Mark as read for received msgs
    if (!empty($rows)) {
        $updateStmt = $conn->prepare("
            UPDATE messages SET is_read = 1 
            WHERE receiver_id = :my_id AND sender_id = :rx_id AND is_read = 0
        ");
        $updateStmt->execute([':my_id' => $myId, ':rx_id' => $receiverId]);
    }
    
    echo json_encode(['status' => 'success', 'messages' => $rows]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
