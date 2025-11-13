<?php
// api/manage_orders.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'غير مصرح لك.']);
    exit;
}

require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['order_id']) || !isset($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح.']);
    exit;
}

$order_id = (int)$_POST['order_id'];
$action = $_POST['action'];
$new_status = '';

if ($action == 'complete') {
    $new_status = 'completed';
} elseif ($action == 'cancel') {
    $new_status = 'cancelled';
}

if (!empty($new_status) && $order_id > 0) {
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND status = 'verification_pending'");
    $stmt->bind_param("si", $new_status, $order_id);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success', 'message' => 'تم تحديث حالة الطلب بنجاح.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'فشل تحديث حالة الطلب. قد يكون تم تحديثه بالفعل.']);
    }
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'إجراء غير معروف.']);
}

$conn->close();
?>