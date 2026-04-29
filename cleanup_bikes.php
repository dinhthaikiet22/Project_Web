<?php
require __DIR__ . '/config/db.php';
try {
    $stmt = $conn->prepare("UPDATE bikes SET status = 'available' WHERE id IN (SELECT bike_id FROM orders WHERE order_status = 'waiting_payment')");
    $stmt->execute();
    echo "So luong xe duoc giai cuu: " . $stmt->rowCount() . "\n";
} catch (Exception $e) {
    echo "Loi: " . $e->getMessage();
}
