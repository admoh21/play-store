<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';

// جلب الطلبات التي تنتظر المراجعة
$pending_orders_query = $conn->query("
    SELECT 
        o.id AS order_id, o.total_amount, o.created_at, o.transaction_proof,
        u.full_name AS user_name, u.email AS user_email, p.name AS payment_method_name
    FROM orders AS o
    JOIN users AS u ON o.user_id = u.id
    JOIN payment_methods AS p ON o.payment_method_id = p.id
    WHERE o.status = 'verification_pending'
    ORDER BY o.created_at ASC
");
$pending_orders = [];
if ($pending_orders_query) {
    while ($row = $pending_orders_query->fetch_assoc()) {
        $pending_orders[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم - إدارة الطلبات</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>لوحة تحكم المتجر</h1>
            <nav class="main-nav">
                <a href="index.php">المنتجات</a><a href="categories.php">الأقسام</a>
                <a href="payments.php">طرق الدفع</a><a href="orders.php" class="active">الطلبات</a>
                <a href="../index.php" target="_blank">عرض الموقع</a><a href="logout.php">تسجيل الخروج</a>
            </nav>
        </header>

        <main class="dashboard-main">
            <div class="main-header">
                <h2>الطلبات المعلقة (بانتظار التأكيد)</h2>
            </div>
            <div class="table-wrapper">
                <?php if (count($pending_orders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>رقم الطلب</th><th>العميل</th><th>الإجمالي</th>
                            <th>طريقة الدفع</th><th>تاريخ الطلب</th><th>إيصال التحويل</th><th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_orders as $order): ?>
                        <tr>
                            <td>#<?= $order['order_id'] ?></td>
                            <td>
                                <?= htmlspecialchars($order['user_name']) ?><br>
                                <small><?= htmlspecialchars($order['user_email']) ?></small>
                            </td>
                            <td><?= $order['total_amount'] ?> ر.س.</td>
                            <td><?= htmlspecialchars($order['payment_method_name']) ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($order['created_at'])) ?></td>
                            <td>
                                <?php if (!empty($order['transaction_proof'])): ?>
                                    <a href="../<?= htmlspecialchars($order['transaction_proof']) ?>" target="_blank" class="view-receipt-link">عرض الإيصال</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-success complete-order-btn" data-order-id="<?= $order['order_id'] ?>">تأكيد الدفع</button>
                                <button class="btn btn-danger cancel-order-btn" data-order-id="<?= $order['order_id'] ?>">رفض الطلب</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                    <p class="no-items-message">لا توجد طلبات معلقة حاليًا.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- *** التصحيح الرئيسي هنا: إضافة رابط ملف الجافاسكريبت *** -->
    <script src="orders_script.js"></script>
</body>
</html>