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
$bikeId = isset($_POST['bike_id']) ? (int)$_POST['bike_id'] : 0;

if ($bikeId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ.']);
    exit;
}

try {
    $conn->beginTransaction();

    // 1. Kiểm tra thông tin xe và tình trạng (Khoá row chống mua trùng - FOR UPDATE)
    $stmtBike = $conn->prepare("SELECT id, user_id, price, status FROM bikes WHERE id = ? FOR UPDATE");
    $stmtBike->execute([$bikeId]);
    $bike = $stmtBike->fetch(PDO::FETCH_ASSOC);

    if (!$bike) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Chiếc xe không tồn tại.']);
        exit;
    }
    if ($bike['status'] !== 'available') {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Xe này đã được mua hoặc tạm ẩn!']);
        exit;
    }
    if ($bike['user_id'] == $myId) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Bạn không thể tự mua xe của mình.']);
        exit;
    }

    $price = (float)$bike['price'];
    $sellerId = (int)$bike['user_id'];

    // 2. Kiểm tra Ví Người Mua (FOR UPDATE)
    $stmtWallet = $conn->prepare("SELECT balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $stmtWallet->execute([$myId]);
    $myWallet = $stmtWallet->fetch(PDO::FETCH_ASSOC);

    if (!$myWallet) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy ví thanh toán của bạn.']);
        exit;
    }

    $buyerBalanceBefore = (float)$myWallet['balance'];
    if ($buyerBalanceBefore < $price) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Số dư không đủ. Vui lòng nạp thêm.']);
        exit;
    }

    // 3. Trừ tiền Buyer
    $buyerBalanceAfter = $buyerBalanceBefore - $price;
    $stmtSub = $conn->prepare("UPDATE wallets SET balance = ? WHERE user_id = ?");
    $stmtSub->execute([$buyerBalanceAfter, $myId]);

    // 4. Cộng tiền vào System Wallet (ID = 0) (FOR UPDATE)
    $stmtSys = $conn->prepare("SELECT balance FROM wallets WHERE user_id = 0 FOR UPDATE");
    $stmtSys->execute();
    $sysWallet = $stmtSys->fetch(PDO::FETCH_ASSOC);
    if (!$sysWallet) {
        // Dự phòng nếu chưa có ID=0
        $conn->exec("INSERT IGNORE INTO wallets (user_id, balance) VALUES (0, 0)");
        $sysBalanceBefore = 0.0;
    } else {
        $sysBalanceBefore = (float)$sysWallet['balance'];
    }
    
    $sysBalanceAfter = $sysBalanceBefore + $price;
    $stmtAddSys = $conn->prepare("UPDATE wallets SET balance = ? WHERE user_id = 0");
    $stmtAddSys->execute([$sysBalanceAfter]);

    // 5. Tạo Mã Giao Dịch
    // CT + Timestamp + Random 4 chars
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $randomStr = substr(str_shuffle($chars), 0, 4);
    $transactionCode = 'CT' . time() . $randomStr;

    // 6. Lưu db Transactions (Type: payment)
    $content = "Thanh toán hệ thống escrow cho xe ID: " . $bikeId;
    $stmtTrans = $conn->prepare("
        INSERT INTO transactions 
        (transaction_code, sender_id, receiver_id, amount, balance_before, balance_after, type, status, content) 
        VALUES (?, ?, ?, ?, ?, ?, 'payment', 'success', ?)
    ");
    $stmtTrans->execute([
        $transactionCode,
        $myId,
        0, // Gửi vào Hệ thống
        $price,
        $buyerBalanceBefore,
        $buyerBalanceAfter,
        $content
    ]);
    
    $transactionId = (int)$conn->lastInsertId();

    // 7. Tạo Order
    $orderCode = 'ORD' . time() . rand(10,99);
    $stmtOrder = $conn->prepare("
        INSERT INTO orders 
        (order_code, bike_id, transaction_id, buyer_id, seller_id, total_price, order_status)
        VALUES (?, ?, ?, ?, ?, ?, 'paid')
    ");
    $stmtOrder->execute([
        $orderCode,
        $bikeId,
        $transactionId,
        $myId,
        $sellerId,
        $price
    ]);

    // 8. Cập nhật trạng thái xe thành pending_delivery
    $stmtUpdateBike = $conn->prepare("UPDATE bikes SET status = 'pending_delivery' WHERE id = ?");
    $stmtUpdateBike->execute([$bikeId]);

    // 9. Xác nhận Transaction
    $conn->commit();

    echo json_encode([
        'status' => 'success', 
        'message' => 'Thanh toán thành công qua hệ thống tạm giữ.',
        'transaction_code' => $transactionCode
    ]);

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    // Log hệ thống: error_log($e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Đã xảy ra lỗi hệ thống. Không có khoản tiền nào bị trừ.']);
}
