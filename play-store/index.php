<?php
require_once 'config.php';

// جلب بيانات المستخدم الحالي إذا كان مسجلاً
$currentUser = getCurrentUser($conn);

// التأكد من أن السلة هي مصفوفة قبل حساب عددها
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
$cart_count = count($_SESSION['cart']);

// --- بداية تحسين الأداء ---

// 1. جلب جميع الفئات أولاً
$categories_query = $conn->query("SELECT * FROM categories WHERE name != 'أحدث المنتجات' ORDER BY display_order ASC");
$categories = [];
$category_ids = [];
if ($categories_query) {
    while ($row = $categories_query->fetch_assoc()) {
        $row['products'] = []; // تهيئة مصفوفة المنتجات
        $categories[$row['id']] = $row;
        $category_ids[] = $row['id'];
    }
}

// 2. جلب أحدث 8 منتجات لكل فئة في استعلام واحد
if (!empty($category_ids)) {
    // هذا الاستعلام المعقد يستخدم متغيرات MySQL لجلب أحدث 8 منتجات فقط لكل فئة
    // وهو أكثر كفاءة بكثير من الاستعلام داخل حلقة
    $ids_string = implode(',', $category_ids);
    $products_query_sql = "
        SELECT p.* FROM (
            SELECT
                p.*,
                @product_rank := IF(@current_category = p.category_id, @product_rank + 1, 1) AS product_rank,
                @current_category := p.category_id
            FROM products p
            CROSS JOIN (SELECT @product_rank := 0, @current_category := 0) vars
            WHERE p.category_id IN ($ids_string)
            ORDER BY p.category_id, p.id DESC
        ) p
        WHERE p.product_rank <= 8";

    $products_query = $conn->query($products_query_sql);

    if($products_query) {
        while ($product = $products_query->fetch_assoc()) {
            if (isset($categories[$product['category_id']])) {
                $categories[$product['category_id']]['products'][] = $product;
            }
        }
    }
}
// إعادة تحويل المصفوفة إلى الشكل الأصلي
$categories = array_values($categories);
// --- نهاية تحسين الأداء ---

// جلب أحدث 8 منتجات بشكل منفصل
$latest_products_query = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 8");
$latest_products = [];
if($latest_products_query) {
    while ($row = $latest_products_query->fetch_assoc()) {
        $latest_products[] = $row;
    }
}

// جلب أحدث 6 تقييمات عامة للمتجر
$testimonials_query = $conn->query("SELECT * FROM testimonials ORDER BY created_at DESC LIMIT 6");
$testimonials = [];
if($testimonials_query) {
    while ($row = $testimonials_query->fetch_assoc()) {
        $testimonials[] = $row;
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
    <title>STORE_RXT</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <link rel="stylesheet" href="style.css">
     <!-- <style></style> -->
</head>
<body data-is-logged-in="<?= isLoggedIn() ? 'true' : 'false' ?>">

    
    <div id="search-overlay" class="search-overlay">
        <button id="close-search-btn" class="close-btn">&times;</button>
        <div class="search-content">
            <form id="search-form">
                <input type="search" id="search-input" placeholder="ابحث عن منتجك المفضل..."></form>
                <div id="search-results-container"></div>
            </div>
        </div>
    
    <aside id="mobile-menu" class="mobile-menu">
        <div class="mobile-menu-header">
            <h3>القائمة الرئيسية</h3>
            <button id="close-menu-btn" class="close-btn">&times;</button>
        </div>
        <ul>
            <li><a href="#">شحن ببجي موبايل</a></li>
            <li><a href="#">الشحن المحدود فري فاير</a></li>
            <li><a href="#">حسابات فري فاير</a></li>
            <li><a href="#">الإشتراكات الرقميه</a></li>
            <li><a href="#" class="user-icon">تسجيل الدخول / الملف الشخصي</a></li>
        </ul>
    </aside>
    <div id="overlay" class="overlay"></div>
    
    <?php if (!isLoggedIn()): ?>
        <div id="login-modal" class="modal">
            <div class="modal-content">
                <button class="close-modal-btn close-btn">&times;</button>
                <h2>تسجيل الدخول</h2>
                <div class="modal-error-message" id="login-error"></div>
                <form id="login-form">
                    <input type="email" name="email" placeholder="البريد الإلكتروني" required>
                    <input type="password" name="password" placeholder="كلمة المرور" required>
                    <button type="submit" class="modal-btn">دخول</button>
                </form>
                <p>ليس لديك حساب؟ <a href="#" id="show-register-link">أنشئ حسابًا جديدًا</a></p>
            </div>
        </div>

        <div id="register-modal" class="modal">
            <div class="modal-content">
                <button class="close-modal-btn close-btn">&times;</button>
                <h2>إنشاء حساب جديد</h2>
                <div class="modal-error-message" id="register-error"></div>
                <form id="register-form">
                    <input type="text" name="full_name" placeholder="الاسم الكامل" required>
                    <input type="email" name="email" placeholder="البريد الإلكتروني" required>
                    <input type="password" name="password" placeholder="كلمة المرور" required>
                    <button type="submit" class="modal-btn">إنشاء الحساب</button>
                </form>
                <p>لديك حساب بالفعل؟ <a href="#" id="show-login-link">تسجيل الدخول</a></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($currentUser): ?>
        <div id="profile-modal" class="modal">
            <div class="modal-content">
                <button class="close-modal-btn close-btn">&times;</button>
                <h2>ملفي الشخصي</h2>
                <div class="profile-info">
                    <p><strong>الاسم:</strong> <?= htmlspecialchars($currentUser['full_name']) ?></p>
                    <p><strong>البريد الإلكتروني:</strong> <?= htmlspecialchars($currentUser['email']) ?></p>
                </div>
                <a href="logout.php" class="modal-btn logout-btn">تسجيل الخروج</a>
            </div>
        </div>

    <?php endif; ?>

    
        <!--  -->
<div id="quick-shop-modal" class="modal">
        <div class="modal-content quick-shop-content">
            <button class="close-modal-btn close-btn">&times;</button>
            <div id="quick-shop-details">
                <!-- سيتم ملء هذه المنطقة ديناميكيًا بواسطة JavaScript -->
                <div class="product-image-placeholder"></div>
                <h3 class="product-title-placeholder">اسم المنتج</h3>
                <div class="product-price-placeholder">السعر</div>
            </div>
            <div class="quick-shop-actions">
                <button id="quick-shop-add-to-cart" class="btn btn-secondary">إضافة للسلة والمتابعة</button>
                <button id="quick-shop-buy-now" class="btn btn-primary">شراء مباشر</button>
            </div>
        </div>
    </div>
    <!--  -->

    <div class="page-wrapper">
        <header class="site-header">
            <div class="container">
                <div class="header-right">
                     <button id="menu-toggle-btn" class="menu-toggle">
                        <div>&#9776;</div>
                    </button>
                </div>
                <div class="logo">
                        <a href="index.php">
                            <img src="logo.png" alt="">
                        </a>
                    </div>

                <div class="header-left">
                    <a href="#" id="open-search-btn" class="header-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </a>
                    <a href="cart.php" class="header-icon cart-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                        <span class="cart-counter"><?= $cart_count ?></span>
                    </a>
                    <a href="#" class="header-icon user-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </a>
                </div>
            </div>
        </header>

        <main>
            <!-- <section class="hero-section"><div class="hero-image-wrapper"><img src="https://cdn.salla.sa/form-builder/pBNbBRQo2LiXrKX0fXkHUgbGnEeJRM0ZdCpzVgrZ.jpg" alt="Hero Banner"></div></section> -->
            
            <section class="section">
                <div class="container">
                    <div class="section-header">
                        <h2>أحدث المنتجات</h2>
                        <a href="category.php?show=latest" class="view-all-link">View All</a>
                    </div>
                    <div class="swiper products-slider">
                        <div class="swiper-wrapper">
                            <?php if (count($latest_products) > 0): foreach ($latest_products as $product): ?>
                                <div class="swiper-slide">
                                    <div class="product-card">
                                        <!-- <a href="product.php?id=<?= $product['id'] ?>" class="product-card-link"> -->
                                            <div class="product-image"><img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>"></div>
                                            <div class="product-info-top">
                                                <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                                                <div class="product-price"><?= $product['price'] ?> $ </div>
                                            </div>
                                        </a>
                                        <div class="product-info-bottom">
                                            <button class="add-to-cart-btn" data-product-id="<?= $product['id'] ?>">أضف للسلة</button>
                                        </div>                                      
                                    </div>
                                </div>
                            <?php endforeach; else: ?>
                                <p class="no-items-message">لا توجد منتجات حاليًا.</p>
                            <?php endif; ?>
                        </div>
                        <div class="swiper-button-prev"></div>
                        <div class="swiper-button-next"></div>
                    </div>
                </div>
            </section>
            
            <?php foreach ($categories as $category): ?>
                <section class="section">
                    <div class="container">
                        <div class="section-header">
                            <h2><?= htmlspecialchars($category['name']) ?></h2>
                            <a href="category.php?id=<?= $category['id'] ?>" class="view-all-link">View All</a>
                        </div>
                        <div class="swiper products-slider">
                            <div class="swiper-wrapper">
                                <?php if (count($category['products']) > 0): foreach ($category['products'] as $product): ?>
                                    <div class="swiper-slide">
                                       <div class="product-card">
                                            <!-- <a href="product.php?id=<?= $product['id'] ?>" class="product-card-link"> -->
                                                <div class="product-image"><img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>"></div>
                                                <div class="product-info-top">
                                                    <h3 class="product-title"><?= htmlspecialchars($product['name']) ?></h3>
                                                    <div class="product-price"><?= $product['price'] ?> $ </div>
                                                </div>
                                            </a>
                                            <div class="product-info-bottom">
                                                <button class="add-to-cart-btn" data-product-id="<?= $product['id'] ?>">أضف للسلة</button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; else: ?>
                                     <p class="no-items-message">لا توجد منتجات في هذا القسم حاليًا.</p>
                                <?php endif; ?>
                            </div>

                            <div class="swiper-button-prev"></div>
                            <div class="swiper-button-next"></div>
                        </div>
                    </div>
                </section>
            <?php endforeach; ?>
<!-- 
هنا كود اراء العملاء            
-->

<!-- 
تقييم هنا 
-->
        </main>

        <footer class="site-footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-contact">
                        <h3>تواصل معنا</h3>
                        <a href="#">واتساب</a>
                        <a href="#">جوال</a>
                        <a href="#">ايميل</a>
                    </div>
                    <div class="footer-payments">
                        <h3>طرق الدفع</h3>
                        <div class="payment-icons">
                            <img src="https://cdn.salla.network/images/payment/mada_mini.png" alt="Mada">
                            <img src="https://cdn.assets.salla.network/themes/1753517624/1.119.0/images/mastercard2.png" alt="Mastercard">
                            <img src="https://cdn.assets.salla.network/themes/1753517624/1.119.0/images/visa2.png" alt="Visa">
                            <img src="https://cdn.salla.network/images/payment/stc_pay_mini.png" alt="STC Pay">
                            <img src="https://cdn.assets.salla.network/themes/1753517624/1.119.0/images/apple_pay.png" alt="Apple Pay">
                        </div>
                    </div>
                </div>
                <div class="footer-copyright">
                    <p> Devloper : Mohammed Adeeb | 2025</p>
                </div>
            </div>
        </footer>
    </div>
    
    <!-- *** الشريط السفلي الذي تم حذفه عن طريق الخطأ - تمت إعادته هنا *** -->
    <nav class="mobile-bottom-nav">
        <a href="index.php" class="nav-item active">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h7.5" />
            </svg>
            <span>الرئيسية</span>
        </a>
        <button id="categories-btn-mobile" class="nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <span>التصنيفات</span>
        </button>
        <a href="cart.php" class="nav-item cart-nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.658-.463 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
            </svg>
            <span>السلة</span>
            <span class="cart-counter"><?= $cart_count ?></span>
        </a>
        <button class="nav-item user-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
            </svg>
            <span>الدخول</span>
        </button>
        <button id="open-search-btn-mobile" class="nav-item">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
            </svg>
            <span>بحث</span>
        </button>
    </nav>
    <button id="scroll-to-top-btn" class="scroll-to-top-btn">
        <svg class="scroll-progress" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"/>
        </svg>
    </button>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="script.js"></script>
</body>
</html>