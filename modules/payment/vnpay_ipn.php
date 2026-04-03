<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/config_vnpay.php';

$inputData = array();
$returnData = array();

foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

$vnp_SecureHash = $inputData['vnp_SecureHash'];
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
$vnpTranId = $inputData['vnp_TransactionNo']; 
$vnp_BankCode = $inputData['vnp_BankCode'];
$vnp_Amount = $inputData['vnp_Amount'] / 100;
$Status = 0; // Là trạng thái thanh toán của giao dịch chưa có IPN lưu tại hệ thống của merchant chiều khởi tạo URL thanh toán.

$vnp_TxnRef = $inputData['vnp_TxnRef'];
$refParts = explode('_', $vnp_TxnRef);
$orderCode = $refParts[0];

try {
    //Check Orderid    
    //Check Amount
    //Check Status
    if ($secureHash == $vnp_SecureHash) {
        $conn = require __DIR__ . '/../../config/db.php';
        $conn->beginTransaction();

        $stmt = $conn->prepare("SELECT id, order_status, buyer_id, total_price FROM orders WHERE order_code = ? FOR UPDATE");
        $stmt->execute([$orderCode]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            if ($order["total_price"] == $vnp_Amount) {
                if ($order["order_status"] === 'waiting_payment') {
                    if ($inputData['vnp_ResponseCode'] == '00' || $inputData['vnp_TransactionStatus'] == '00') {
                        // Trạng thái thành công
                        $stmtUpdate = $conn->prepare("UPDATE orders SET order_status = 'paid' WHERE id = ?");
                        $stmtUpdate->execute([$order['id']]);

                        $content = "VNPAY Webhook Payment. Bank: $vnp_BankCode. TxnNo: $vnpTranId";
                        $stmtTrans = $conn->prepare("
                            INSERT INTO transactions 
                            (transaction_code, sender_id, receiver_id, amount, balance_before, balance_after, type, status, content) 
                            VALUES (?, ?, 0, ?, 0, 0, 'payment', 'success', ?)
                        ");
                        $stmtTrans->execute([
                            $vnpTranId,
                            $order['buyer_id'],
                            $vnp_Amount,
                            $content
                        ]);
                        $transactionId = $conn->lastInsertId();

                        $stmtUpdateOrderTrans = $conn->prepare("UPDATE orders SET transaction_id = ? WHERE id = ?");
                        $stmtUpdateOrderTrans->execute([$transactionId, $order['id']]);
                        
                    } else {
                        // Thất bại
                    }
                    $conn->commit();
                    $returnData['RspCode'] = '00';
                    $returnData['Message'] = 'Confirm Success';
                } else {
                    $conn->commit();
                    $returnData['RspCode'] = '02';
                    $returnData['Message'] = 'Order already confirmed';
                }
            } else {
                $conn->commit();
                $returnData['RspCode'] = '04';
                $returnData['Message'] = 'invalid amount';
            }
        } else {
            $conn->commit();
            $returnData['RspCode'] = '01';
            $returnData['Message'] = 'Order not found';
        }
    } else {
        $returnData['RspCode'] = '97';
        $returnData['Message'] = 'Invalid signature';
    }
} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    $returnData['RspCode'] = '99';
    $returnData['Message'] = 'Unknow error';
}
echo json_encode($returnData);
