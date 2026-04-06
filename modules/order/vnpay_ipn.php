<?php
declare(strict_types=1);
require_once __DIR__ . '/../../config/config_vnpay.php';

$inputData = array();
$returnData = array();

foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

$vnp_SecureHash = $inputData['vnp_SecureHash'] ?? '';
unset($inputData['vnp_SecureHashType']);
unset($inputData['vnp_SecureHash']);

ksort($inputData);
$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode((string)$value);
    } else {
        $hashData = $hashData . urlencode($key) . "=" . urlencode((string)$value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, VNP_HASH_SECRET);

if ($secureHash === $vnp_SecureHash) {
    $vnp_Amount = isset($_GET['vnp_Amount']) ? (float)$_GET['vnp_Amount'] / 100 : 0;
    $vnp_BankCode = $_GET['vnp_BankCode'] ?? '';
    $vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';
    $vnp_PayDateStr = $_GET['vnp_PayDate'] ?? ''; // Format: YYYYMMDDHHMMSS
    $vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
    $vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';
    
    // Parse Date
    $vnp_PayDate = date('Y-m-d H:i:s');
    if (strlen($vnp_PayDateStr) === 14) {
        $vnp_PayDate = date('Y-m-d H:i:s', strtotime($vnp_PayDateStr));
    }

    // Lấy orderCode từ TxnRef (ORDxxx_12345)
    $orderCodeParts = explode('_', $vnp_TxnRef);
    $orderCode = $orderCodeParts[0];

    try {
        $conn = require __DIR__ . '/../../config/db.php';
        
        $stmt = $conn->prepare("SELECT id, order_status, total_price, buyer_id FROM orders WHERE order_code = ? LIMIT 1");
        $stmt->execute([$orderCode]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            if ((float)$order['total_price'] == $vnp_Amount) {
                if ($order['order_status'] !== 'paid') {
                    if ($vnp_ResponseCode == '00') {
                        // Thành công
                        $conn->beginTransaction();
                        
                        $stmtUpdate = $conn->prepare("UPDATE orders SET order_status = 'paid' WHERE id = ?");
                        $stmtUpdate->execute([$order['id']]);
                        
                        // Insert transactions (với các cột mới theo yêu cầu)
                        $stmtTrans = $conn->prepare("
                            INSERT INTO transactions 
                            (transaction_code, sender_id, amount, vnp_transaction_no, vnp_bank_code, vnp_pay_date, type, status, content) 
                            VALUES (?, ?, ?, ?, ?, ?, 'payment', 'success', ?)
                        ");
                        // Tạo mã GD nội bộ
                        $sysTransCode = 'TRX' . time() . random_int(100, 999);
                        $content = "Thanh toán giao dịch VNPAY cho đơn hàng " . $orderCode;
                        
                        $stmtTrans->execute([
                            $sysTransCode,
                            $order['buyer_id'],
                            $vnp_Amount,
                            $vnp_TransactionNo,
                            $vnp_BankCode,
                            $vnp_PayDate,
                            $content
                        ]);
                        
                        $conn->commit();
                        $returnData['RspCode'] = '00';
                        $returnData['Message'] = 'Confirm Success';
                    } else {
                        // GD bị hủy hoặc lỗi nhưng k cập nhật ở đây hoặc chỉ cập nhật fail
                        $stmtUpdate = $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?");
                        $stmtUpdate->execute([$order['id']]);
                        $returnData['RspCode'] = '00';
                        $returnData['Message'] = 'Confirm Success';
                    }
                } else {
                    $returnData['RspCode'] = '02';
                    $returnData['Message'] = 'Order already confirmed';
                }
            } else {
                $returnData['RspCode'] = '04';
                $returnData['Message'] = 'Invalid amount';
            }
        } else {
            $returnData['RspCode'] = '01';
            $returnData['Message'] = 'Order not found';
        }
    } catch (Exception $e) {
        $returnData['RspCode'] = '99';
        $returnData['Message'] = 'Unknown error';
    }
} else {
    $returnData['RspCode'] = '97';
    $returnData['Message'] = 'Invalid signature';
}

header('Content-Type: application/json');
echo json_encode($returnData);
exit;
