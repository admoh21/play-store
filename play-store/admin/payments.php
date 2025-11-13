<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) { header('Location: login.php'); exit; }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم - إدارة طرق الدفع</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>لوحة تحكم المتجر</h1>
            <nav class="main-nav">
                <a href="index.php">المنتجات</a><a href="categories.php">الأقسام</a>
                <a href="payments.php" class="active">طرق الدفع</a>
                <a href="../index.php" target="_blank">عرض الموقع</a><a href="logout.php">تسجيل الخروج</a>
            </nav>
        </header>
        <main class="dashboard-main">
            <div class="main-header">
                <h2>إدارة طرق الدفع</h2>
                <button id="add-payment-btn" class="btn btn-primary">إضافة طريقة دفع جديدة</button>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>الاسم المعروض</th><th>النوع</th><th>الحالة</th><th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="payments-table-body"></tbody>
                </table>
            </div>
        </main>
    </div>

    <div id="payment-modal" class="modal">
        <div class="modal-content">
            <button class="close-modal-btn close-btn">&times;</button>
            <h2 id="payment-modal-title">إضافة طريقة دفع</h2>
            <form id="payment-form">
                <input type="hidden" id="payment-id" name="id">
                <div class="form-group">
                    <label for="gateway-code">نوع طريقة الدفع:</label>
                    <select id="gateway-code" name="gateway_code" required>
                        <option value="">-- اختر --</option>
                        <option value="bank_transfer">تحويل بنكي</option>
                        <option value="stripe">Stripe (بطاقة)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="payment-name">الاسم (يظهر للعميل):</label>
                    <input type="text" id="payment-name" name="name" required placeholder="مثال: بنك الكريمي - شخصي">
                </div>
                <!-- الحقول المتغيرة -->
                <div id="fields_bank_transfer" class="gateway-fields">
                     <div class="form-group">
                        <label>تفاصيل الحساب:</label>
                        <textarea name="config_details[details]" rows="4" placeholder="اسم المستفيد: ..."></textarea>
                    </div>
                </div>
                <div id="fields_stripe" class="gateway-fields">
                    <div class="form-group">
                        <label>Publishable Key:</label>
                        <input type="text" name="config_details[api_key_public]">
                    </div>
                    <div class="form-group">
                        <label>Secret Key:</label>
                        <input type="password" name="config_details[api_key_secret]">
                    </div>
                </div>
                 <div class="form-group">
                    <label><input type="checkbox" id="payment-active" name="is_active" value="1"> تفعيل</label>
                </div>
                <div class="form-actions"><button type="submit" class="btn btn-primary">حفظ</button></div>
            </form>
        </div>
    </div>
    <script src="payments_script.js"></script>
</body>
</html>