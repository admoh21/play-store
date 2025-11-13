<?php
require_once 'config.php';

// تحديد أي قسم سيتم عرضه
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$show_latest = isset($_GET['show']) && $_GET['show'] == 'latest';

$category_name = "المنتجات";
$category_description = "تصفح جميع منتجاتنا المميزة.";
$products = [];

if ($show_latest) {
    // حالة خاصة لعرض أحدث المنتجات
    $category_name = "أحدث المنتجات";
    $category_description = "اكتشف آخر ما تم إضافته إلى متجرنا.";
    $products_query = $conn->query("SELECT * FROM products ORDER BY created_at DESC");
    if ($products_query) {
        while ($row = $products_query->fetch_assoc()) {
            $products[] = $row;
        }
    }
} elseif ($category_id > 0) {
    // جلب بيانات القسم المحدد
    $stmt = $conn->prepare("SELECT name, description FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $category_result = $stmt->get_result();
    if ($category = $category_result->fetch_assoc()) {
        $category_name = $category['name'];
        $category_description = $category['description'];
    }
    $stmt->close();

    // جلب جميع منتجات هذا القسم
    $stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $products_result = $stmt->get_result();
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
} else {
    // إذا لم يتم تحديد قسم، يمكننا عرض جميع المنتجات أو إعادة التوجيه
    header('Location: index.php');
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($category_name) ?> - STORE_RXT</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-wrapper">
        <!-- يمكنك إضافة الهيدر هنا ليكون متناسقًا -->
        <header class="site-header">
            <div class="container">
                <div class="header-right">
                    <a href="index.php" class="header-icon back-button">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                </div>
                <div class="logo-center">
                    <a href="index.php"><img src="logo.png" alt="STORE_RXT Logo"></a>
                </div>
                <div class="header-left">
                    <a href="cart.php" class="header-icon cart-icon">
                        <!-- أيقونة السلة هنا -->
                    </a>
                </div>
            </div>
        </header>

        <main class="category-page">
            <div class="container">
                <div class="category-page-header">
                    <h1><?= htmlspecialchars($category_name) ?></h1>
                    <p><?= htmlspecialchars($category_description) ?></p>
                </div>

                <div class="category-products-grid">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <a href="product.php?id=<?= $product['id'] ?>" class="product-card-link">
                                    <div class="product-image">
                                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                                    </div>
                                    <div class="product-info-top">
                                        <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                                        <div class="product-price"><?= $product['price'] ?> ر.س.</div>
                                    </div>
                                </a>
                                <div class="product-info-bottom">
                                    <button class="add-to-cart-btn" data-product-id="<?= $product['id'] ?>">أضف للسلة</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-items-message full-width">لا توجد منتجات في هذا القسم حاليًا.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
        <!-- يمكنك إضافة الفوتر هنا -->
        <footer class="site-footer">
           <!-- ... -->
        </footer>
    </div>
    <!-- يجب إضافة script.js هنا أيضًا لتفعيل زر "أضف للسلة" -->
    <script src="script.js"></script>
</body>
</html>