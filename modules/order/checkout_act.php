<?php
declare(strict_types=1);
session_start();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Bạn chưa đăng nhập.']);
    exit;
}

$conn = require __DIR__ . '/../../config/db.php';
$myId = (int)$_SESSION['user_id'];

// Lấy dữ liệu Input
$bikeId = isset($_POST['bike_id']) ? (int)$_POST['bike_id'] : 0;
$recipientName = trim((string)($_POST['recipient_name'] ?? ''));
$recipientPhone = trim((string)($_POST['recipient_phone'] ?? ''));
$shippingAddress = trim((string)($_POST['shipping_address'] ?? ''));
$shippingMethod = trim((string)($_POST['shipping_method'] ?? 'standard'));
$paymentMethod = trim((string)($_POST['payment_method'] ?? 'vietqr'));

if ($bikeId <= 0 || $recipientName === '' || $recipientPhone === '' || $shippingAddress === '') {
    echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin giao hàng.']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Kiểm tra tồn tại xe (Khóa FOR UPDATE)
    $stmtBike = $conn->prepare("SELECT id, user_id, price, status FROM bikes WHERE id = ? FOR UPDATE");
    $stmtBike->execute([$bikeId]);
    $bike = $stmtBike->fetch(PDO::FETCH_ASSOC);

    if (!$bike || $bike['status'] !== 'available') {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Chiếc xe này không còn khả dụng để mua.']);
        exit;
    }

    if ($bike['user_id'] == $myId) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Bạn không thể tự mua lại xe của mình.']);
        exit;
    }

    // 2. Tính toán tổng tiền
    $basePrice = (float)$bike['price'];
    $shippingFee = 0;
    
    if ($shippingMethod === 'standard') {
        $shippingFee = 50000;
    } elseif ($shippingMethod === 'express') {
        $shippingFee = 150000;
    } elseif ($shippingMethod === 'pickup') {
        $shippingFee = 0;
    } else {
        $shippingMethod = 'standard';
        $shippingFee = 50000;
    }

    $totalPrice = $basePrice + $shippingFee;
    
    // Status Logic
    // Nếu COD -> chờ giao nhận, xem như đã chốt mua thành công ở vòng này.
    // Nếu VietQR -> waiting_payment (chờ Admin hoặc web hook Bank xác nhận tiền rớt).
    $orderStatus = ($paymentMethod === 'cod') ? 'waiting_payment' : 'waiting_payment';
    // Đã thay đổi logic chuẩn: Mọi đơn mới sinh ra đều chờ xác nhận/thanh toán.
    // Nếu muốn COD thành 'paid' thì sửa tuỳ ý, ở đây tôi chốt chung 1 rule chờ Admin xác nhận.

    // 3. Khởi tạo mã Đơn Order Code
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomStr = substr(str_shuffle($chars), 0, 4);
    $orderCode = 'ORD' . time() . $randomStr;
    $sellerId = (int)$bike['user_id'];

    // 4. Lưu dữ liệu Đơn hàng
    $stmtOrder = $conn->prepare("
        INSERT INTO orders 
        (order_code, bike_id, buyer_id, seller_id, total_price, shipping_address, shipping_method, payment_method, recipient_name, recipient_phone, order_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtOrder->execute([
        $orderCode,
        $bikeId,
        $myId,
        $sellerId,
        $totalPrice,
        $shippingAddress,
        $shippingMethod,
        $paymentMethod,
        $recipientName,
        $recipientPhone,
        $orderStatus
    ]);

    // 5. Cập nhật Status Xe
    $stmtUpdateBike = $conn->prepare("UPDATE bikes SET status = 'pending_delivery' WHERE id = ?");
    $stmtUpdateBike->execute([$bikeId]);

    $conn->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => 'Đã tạo đơn hàng thành công.',
        'order_code' => $orderCode
    ]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Checkout Order Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi phát sinh dữ liệu mua hàng. Xin thử lại.']);
}
