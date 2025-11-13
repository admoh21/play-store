<?php
// api/initiate-universal-payment.php

require_once '../config.php';

// Composer autoload for Stripe library
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'يجب تسجيل الدخول لإتمام عملية الدفع.']);
    exit;
}
if (empty($_SESSION['cart'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'سلة المشتريات فارغة.']);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$gateway_code = $data->payment_method ?? '';
if (empty($gateway_code)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'الرجاء اختيار طريقة الدفع.']);
    exit;
}

// جلب إعدادات البوابة المختارة من قاعدة البيانات
$stmt = $conn->prepare("SELECT * FROM payment_methods WHERE gateway_code = ? AND is_active = TRUE");
$stmt->bind_param("s", $gateway_code);
$stmt->execute();
$gateway = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$gateway) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'طريقة الدفع المختارة غير متاحة حاليًا.']);
    exit;
}

// حساب الإجمالي وجلب بيانات المستخدم
$total_amount = 0;
$cart_ids = implode(',', array_map('intval', $_SESSION['cart']));
$query_string = "SELECT * FROM products WHERE id IN ($cart_ids)";
$query = $conn->query($query_string);
$cart_items = [];
while ($product = $query->fetch_assoc()) {
    $cart_items[] = $product;
    $total_amount += $product['price'];
}
$currentUser = getCurrentUser($conn);

// إنشاء الطلب في قاعدة بياناتنا بحالة "بانتظار الدفع"
$status = 'banned_payment'; 
$stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, payment_method_id) VALUES (?, ?, ?, ?)");
$stmt->bind_param("idsi", $currentUser['id'], $total_amount, $status, $gateway['id']);
$stmt->execute();
$order_id = $stmt->insert_id;
$stmt->close();
// إضافة المنتجات إلى جدول order_items
foreach ($cart_items as $item) {
    $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, 1, ?)");
    $stmt->bind_param("iid", $order_id, $item['id'], $item['price']);
    $stmt->execute();
    $stmt->close();
}


// --- المنطق الموجه حسب البوابة المختارة ---

if ($gateway['gateway_code'] == 'stripe') {
    if (!class_exists('\Stripe\Stripe')) {
        echo json_encode(['status' => 'error', 'message' => 'مكتبة Stripe غير مثبتة.']); exit;
    }
    \Stripe\Stripe::setApiKey($gateway['api_key_secret']);
    $line_items = [];
    foreach ($cart_items as $item) {
        $line_items[] = [
            'price_data' => ['currency' => 'sar', 'product_data' => ['name' => $item['name']], 'unit_amount' => $item['price'] * 100],
            'quantity' => 1,
        ];
    }
    try {
        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $line_items,
            'mode' => 'payment',
            'success_url' => 'http://localhost/my-store-php/success.php?order_id=' . $order_id,
            'cancel_url' => 'http://localhost/my-store-php/cart.php',
            'metadata' => ['order_id' => $order_id]
        ]);
        echo json_encode(['status' => 'success', 'type' => 'stripe', 'sessionId' => $checkout_session->id]);
    } catch(Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'خطأ من Stripe: ' . $e->getMessage()]);
    }

} elseif ($gateway['gateway_code'] == 'binance_pay') {
    // (منطق Binance Pay المستقبلي)
    echo json_encode(['status' => 'error', 'message' => 'Binance Pay غير مدعوم حاليًا.']);
    
} elseif ($gateway['gateway_code'] == 'bank_transfer') {
    // توجيه لصفحة تعليمات التحويل البنكي
    echo json_encode(['status' => 'success', 'type' => 'redirect', 'payment_url' => 'bank-transfer-instructions.php?order_id=' . $order_id]);
}

$conn->close();
?>