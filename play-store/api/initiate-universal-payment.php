<?php
// api/initiate-universal-payment.php

require_once '../config.php';

// Composer autoload for Stripe library
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'الطريقة غير مسموح بها.']);
    exit;
}

// التحقق من رمز CSRF من الرأس
$csrf_token = trim($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
if (!verify_csrf_token($csrf_token)) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'طلب غير صالح أو جلسة منتهية. يرجى تحديث الصفحة والمحاولة مرة أخرى.']);
    exit;
}


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
$status = 'pending_payment';
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
        // تحديد عنوان URL الأساسي ديناميكيًا
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        $host = $_SERVER['HTTP_HOST'];
        $base_dir = dirname(dirname($_SERVER['SCRIPT_NAME'])); // للوصول إلى الجذر
        $base_url = rtrim($protocol . $host . $base_dir, '/');

        $checkout_session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $line_items,
            'mode' => 'payment',
            'success_url' => $base_url . '/success.php?order_id=' . $order_id,
            'cancel_url' => $base_url . '/cart.php',
            'metadata' => ['order_id' => $order_id]
        ]);
        echo json_encode(['status' => 'success', 'type' => 'stripe', 'sessionId' => $checkout_session->id]);
    } catch(Exception $e) {
        // لا تعرض تفاصيل الخطأ للمستخدم
        error_log("Stripe Error: " . $e->getMessage()); // سجل الخطأ للمطور
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'حدث خطأ أثناء معالجة الدفع. يرجى المحاولة مرة أخرى.']);
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
