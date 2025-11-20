<?php
// bank-transfer-instructions.php
require_once 'config.php';
if (!isLoggedIn()) { header('Location: index.php?action=login'); exit; }
$order_id = (int)($_GET['order_id'] ?? 0);
$user_id = $_SESSION['user_id'];
$message = '';

// جلب الطلب
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$order) { header('Location: index.php'); exit; }

// جلب تفاصيل طريقة الدفع
$stmt = $conn->prepare("SELECT * FROM payment_methods WHERE id = ?");
$stmt->bind_param("i", $order['payment_method_id']);
$stmt->execute();
$payment_method = $stmt->get_result()->fetch_assoc();
$stmt->close();
$bank_details = json_decode($payment_method['config_details'], true);

// التعامل مع رفع الإيصال
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['receipt'])) {

    // التحقق من رمز CSRF
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf_token)) {
        $message = "خطأ: طلب غير صالح. يرجى تحديث الصفحة والمحاولة مرة أخرى.";
    }
    // إذا كان الرمز صحيحًا، استمر في معالجة الملف
    elseif (isset($_FILES['receipt']) && $_FILES['receipt']['error'] == UPLOAD_ERR_OK) {
        $file = $_FILES['receipt'];
        $max_size = 5 * 1024 * 1024; // 5 MB
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $allowed_mime_types = ['image/jpeg', 'image/png', 'image/gif'];

        $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

        // التحقق من حجم الملف
        if ($file['size'] > $max_size) {
            $message = "خطأ: حجم الملف كبير جدًا. الحد الأقصى 5 ميجابايت.";
        }
        // التحقق من امتداد الملف
        elseif (!in_array($file_extension, $allowed_extensions)) {
            $message = "خطأ: امتداد الملف غير مسموح به. الامتدادات المسموح بها: " . implode(', ', $allowed_extensions);
        }
        else {
            // التحقق من نوع MIME الفعلي للملف
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->file($file['tmp_name']);

            if (!in_array($mime_type, $allowed_mime_types)) {
                $message = "خطأ: نوع الملف غير صالح.";
            } else {
                $target_dir = "uploads/receipts/";
                if (!file_exists($target_dir)) {
                    // استخدام أذونات أكثر أمانًا
                    mkdir($target_dir, 0755, true);
                }

                // إنشاء اسم ملف فريد وآمن
                $new_filename = "order_" . $order_id . "_" . uniqid() . '.' . $file_extension;
                $target_file = $target_dir . $new_filename;

                if (move_uploaded_file($file["tmp_name"], $target_file)) {
                    $receipt_path = $target_file; // المسار آمن الآن
                    $stmt = $conn->prepare("UPDATE orders SET transaction_proof = ?, status = 'verification_pending' WHERE id = ?");
                    $stmt->bind_param("si", $receipt_path, $order_id);
                    $stmt->execute();
                    $stmt->close();
                    $message = "تم رفع الإيصال بنجاح! طلبك الآن قيد المراجعة.";
                    // تحديث بيانات الطلب
                    $order['status'] = 'verification_pending';
                    $order['transaction_proof'] = $receipt_path;
                } else {
                    $message = "حدث خطأ أثناء رفع الملف.";
                }
            }
        }
    } elseif (isset($_FILES['receipt'])) {
        // التعامل مع أخطاء الرفع الأخرى
        switch ($_FILES['receipt']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "الملف أكبر من الحد المسموح به في الخادم.";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "الملف أكبر من الحد المسموح به في النموذج.";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "لم يتم رفع أي ملف.";
                break;
            default:
                $message = "حدث خطأ غير متوقع أثناء الرفع.";
                break;
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <title>تعليمات الدفع - طلب رقم #<?= $order_id ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-wrapper">
        <header class="site-header"><!-- ... --></header>
        <main class="static-page">
            <div class="container">
                <div class="instructions-box">
                    <h1>طلبك قيد الانتظار (رقم #<?= $order_id ?>)</h1>
                    <?php if ($order['status'] == 'pending_payment'): ?>
                        <p class="lead">لإتمام الشراء، يرجى تحويل المبلغ <strong><?= $order['total_amount'] ?> ر.س.</strong> إلى الحساب التالي:</p>
                        <div class="bank-details"><pre><?= htmlspecialchars($bank_details['details']) ?></pre></div>
                        <div class="important-notice">
                            <h3>بعد التحويل</h3>
                            <p>يرجى رفع صورة واضحة من إيصال التحويل باستخدام النموذج أدناه.</p>
                        </div>
                        <form method="POST" enctype="multipart/form-data" class="receipt-form">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            <label for="receipt">اختر ملف الإيصال:</label>
                            <input type="file" name="receipt" id="receipt" required accept="image/*">
                            <button type="submit" class="modal-btn">رفع وتأكيد الدفع</button>
                        </form>
                    <?php else: ?>
                        <div class="success-message">
                            <h3>تم استلام إيصالك بنجاح!</h3>
                            <p>طلبك الآن قيد المراجعة من قبل فريقنا. سيتم تأكيده خلال الساعات القادمة.</p>
                            <p>يمكنك متابعة حالة طلبك من صفحة "طلباتي".</p>
                        </div>
                    <?php endif; ?>
                    <?php if ($message): ?><p class="form-message"><?= $message ?></p><?php endif; ?>
                    <a href="index.php" class="modal-btn continue-btn">العودة للمتجر</a>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
