<?php
// cart.php

require_once 'config.php';

// التحقق من تسجيل الدخول
if (!isLoggedIn()) {
    $temp_cart = $_SESSION['cart'] ?? [];
    session_destroy();
    session_start(); 
    $_SESSION['redirect_after_login'] = 'cart.php'; 
    $_SESSION['temp_cart'] = $temp_cart; 
    header('Location: index.php?action=login');
    exit();
}

// التعامل مع حذف منتج من السلة
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['id'])) {
    $product_id_to_remove = (int)$_GET['id'];
    if (!empty($_SESSION['cart'])) {
        $key = array_search($product_id_to_remove, $_SESSION['cart']);
        if ($key !== false) {
            unset($_SESSION['cart'][$key]);
        }
        $_SESSION['cart'] = array_values($_SESSION['cart']);
    }
    header('Location: cart.php'); 
    exit();
}

// جلب منتجات السلة من قاعدة البيانات
$cart_items = [];
$total_price = 0;
if (!empty($_SESSION['cart'])) {
    $cart_ids = implode(',', array_map('intval', $_SESSION['cart']));
    $query_string = "SELECT * FROM products WHERE id IN ($cart_ids)";
    $query = $conn->query($query_string);
    while ($row = $query->fetch_assoc()) {
        $cart_items[] = $row;
        $total_price += $row['price'];
    }
}

// جلب طرق الدفع المفعلة من قاعدة البيانات
$payment_methods_query = $conn->query("SELECT * FROM payment_methods WHERE is_active = TRUE ORDER BY id ASC");
$payment_methods = [];
while ($row = $payment_methods_query->fetch_assoc()) {
    $payment_methods[] = $row;
}

// الحصول على المفتاح العام لـ Stripe
$stripe_publishable_key = '';
foreach ($payment_methods as $method) {
    if ($method['gateway_code'] == 'stripe') {
        $stripe_publishable_key = $method['api_key_public'];
        break;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <title>إتمام الطلب - STORE_RXT</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-wrapper">
        <header class="site-header">
            <div class="container">
                <div class="logo-center">
                    <a href="index.php"><img src="https://cdn.salla.sa/cdn-cgi/image/fit=scale-down,width=400,height=400,onerror=redirect,format=auto/BrBYdl/sf7QnLfxddG2eDZx6MkvlUna8twyDvog1GNIeHEq.jpg" alt="STORE_RXT Logo"></a>
                </div>
            </div>
        </header>

        <main class="checkout-page">
            <div class="container">
                <h1>إتمام الطلب</h1>
                <div class="checkout-layout">
                    <div class="order-summary">
                        <h2>ملخص طلبك</h2>
                        <?php if (count($cart_items) > 0): ?>
                            <?php foreach ($cart_items as $item): ?>
                                <div class="summary-item">
                                    <div class="summary-item-image">
                                        <img src="<?= htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['name']) ?>">
                                    </div>
                                    <div class="summary-item-details">
                                        <span><?= htmlspecialchars($item['name']) ?></span>
                                        <a href="cart.php?action=remove&id=<?= $item['id'] ?>" class="remove-btn-small">إزالة</a>
                                    </div>
                                    <strong class="summary-item-price"><?= $item['price'] ?> ر.س.</strong>
                                </div>
                            <?php endforeach; ?>
                            <div class="summary-total">
                                <strong>الإجمالي النهائي</strong>
                                <strong><?= $total_price ?> ر.س.</strong>
                            </div>
                        <?php else: ?>
                            <div class="empty-cart-message">
                                <img src="https://salla.sa/site-assets/images/empty-cart.svg" alt="سلة فارغة">
                                <h2>سلتك فارغة!</h2>
                                <p>تصفح منتجاتنا الرائعة وأضفها إلى سلتك.</p>
                            </div>
                        <?php endif; ?>
                        <a href="index.php" class="continue-shopping-link">
                            &leftarrow; متابعة التسوق
                        </a>
                    </div>

                    <div class="payment-selection">
                        <?php if (count($cart_items) > 0): ?>
                            <h2>اختر طريقة الدفع</h2>
                            <form id="payment-form">
                                <div class="payment-options">
                                    <?php foreach ($payment_methods as $method): ?>
                                    <label class="payment-option">
                                        <input type="radio" name="payment_method" value="<?= $method['gateway_code'] ?>" required>
                                        <div class="payment-option-content">
                                            <img class="payment-logo" src="<?= htmlspecialchars($method['logo_url']) ?>" alt="<?= htmlspecialchars($method['name']) ?>">
                                            <div class="payment-option-text">
                                                <strong><?= htmlspecialchars($method['name']) ?></strong>
                                                <span><?= htmlspecialchars($method['description']) ?></span>
                                            </div>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                                <button type="submit" class="modal-btn checkout-btn">
                                    إتمام الدفع الآن
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const STRIPE_PUBLISHABLE_KEY = "<?= $stripe_publishable_key ?>";
    </script>
    <script src="checkout.js" defer></script>
</body>
</html>