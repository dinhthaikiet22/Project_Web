<?php
declare(strict_types=1);
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

require_once __DIR__ . '/../../config/config.php';

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
$paymentMethod = trim((string)($_POST['payment_method'] ?? 'vnpay'));

if (!in_array($paymentMethod, ['vnpay', 'cod'])) {
    $paymentMethod = 'vnpay';
}

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
    $orderStatus = 'waiting_payment';

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

    // 6. Xử lý VNPAY
    if ($paymentMethod === 'vnpay') {
        require_once __DIR__ . '/../../config/config_vnpay.php';
        
        $vnp_TxnRef = $orderCode . '_' . time(); // Đảm bảo duy nhất
        $vnp_OrderInfo = "Thanh_toan_don_hang_" . $orderCode;
        $vnp_OrderType = 'billpayment';
        $vnp_Amount = $totalPrice * 100;
        $vnp_Locale = 'vn';
        $vnp_BankCode = '';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
        
        $vnp_CreateDate = date('YmdHis');
        $vnp_ExpireDate = date('YmdHis', strtotime('+15 minutes', strtotime($vnp_CreateDate)));
        
        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => VNP_TMN_CODE,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => $vnp_CreateDate,
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => VNP_RETURN_URL,
            "vnp_TxnRef" => $vnp_TxnRef,
            "vnp_ExpireDate" => $vnp_ExpireDate
        );
        
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashdata .= '&' . urlencode($key) . "=" . urlencode((string)$value);
            } else {
                $hashdata .= urlencode($key) . "=" . urlencode((string)$value);
                $i = 1;
            }
            $query .= urlencode($key) . "=" . urlencode((string)$value) . '&';
        }
        
        $vnp_Url = VNP_URL . "?" . rtrim($query, '&');
        if (VNP_HASH_SECRET !== "") {
            $vnpSecureHash = hash_hmac('sha512', $hashdata, VNP_HASH_SECRET);
            $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
        }
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Đang chuyển hướng VNPAY...',
            'payment_url' => $vnp_Url,
            'order_code' => $orderCode
        ]);
        exit;
    }

    if ($paymentMethod === 'cod') {
        echo json_encode([
            'status' => 'success', 
            'message' => 'Đã tạo đơn hàng thành công.',
            'order_code' => $orderCode,
            'redirect_url' => BASE_URL . '?page=order_success&code=' . $orderCode
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success', 
        'message' => 'Đã tạo đơn hàng thành công.',
        'order_code' => $orderCode
    ]);
    exit;

} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Checkout Order Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Lỗi phát sinh dữ liệu mua hàng. Xin thử lại.']);
    exit;
}
