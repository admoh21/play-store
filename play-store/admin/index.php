<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
require_once '../config.php';
$categories_query = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = [];
while ($row = $categories_query->fetch_assoc()) {
    $categories[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - إدارة المنتجات</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>لوحة تحكم المتجر</h1>
            <nav class="main-nav">
                <a href="index.php" class="active">المنتجات</a>
                <a href="categories.php">الأقسام</a>
                <a href="orders.php"> الطلبات </a>
                <a href="payments.php" target="_blank"> ( البنكي )طرق الدفع</a>
                <a href="../index.php" target="_blank">عرض الموقع</a>
                <a href="logout.php">تسجيل الخروج (<?= htmlspecialchars($_SESSION['admin_username']) ?>)</a>
            </nav>
        </header>

        <main class="dashboard-main">
            <div class="main-header">
                <h2>إدارة المنتجات</h2>
                <button id="add-product-btn" class="btn btn-primary">إضافة منتج جديد</button>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>اسم المنتج</th>
                            <th>السعر</th>
                            <th>القسم</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="products-table-body">
                        <!-- البيانات تأتي من JavaScript -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- النافذة المنبثقة -->
    <div id="product-modal" class="modal">
        <div class="modal-content">
            <button class="close-modal-btn close-btn">&times;</button>
            <h2 id="modal-title">إضافة منتج جديد</h2>
            <form id="product-form" enctype="multipart/form-data">
                <input type="hidden" id="product-id" name="id">
                <div class="form-group">
                    <label for="product-name">اسم المنتج:</label>
                    <input type="text" id="product-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="product-category">القسم:</label>
                    <select id="product-category" name="category_id" required>
                        <option value="">-- اختر قسم --</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="product-price">السعر (ر.س):</label>
                    <input type="number" id="product-price" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="product-original-price">السعر الأصلي (اختياري):</label>
                    <input type="number" id="product-original-price" name="original_price" step="0.01">
                </div>
                <div class="form-group">
                    <label for="product-image">صورة المنتج:</label>
                    <input type="file" id="product-image" name="image" accept="image/png, image/jpeg, image/gif">
                    <input type="hidden" id="current-image-url" name="current_image_url">
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">حفظ المنتج</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="script.js"></script>
</body>
</html>