<?php
// product.php
require_once 'config.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id == 0) { header('Location: index.php'); exit; }

// جلب بيانات المنتج
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) { header('Location: index.php'); exit; }

// جلب مراجعات هذا المنتج مع أسماء المستخدمين
$reviews_query = $conn->prepare("
    SELECT r.rating, r.comment, r.created_at, u.full_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.product_id = ? 
    ORDER BY r.created_at DESC
");
$reviews_query->bind_param("i", $product_id);
$reviews_query->execute();
$reviews = $reviews_query->get_result()->fetch_all(MYSQLI_ASSOC);
$reviews_query->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <title><?= htmlspecialchars($product['name']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-wrapper">
        <!-- (يمكنك إضافة الهيدر هنا) -->

        <main class="product-page">
            <div class="container">
                <div class="product-layout">
                    <div class="product-gallery">
                        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                    </div>
                    <div class="product-details">
                        <h1><?= htmlspecialchars($product['name']) ?></h1>
                        <div class="product-page-price"><?= $product['price'] ?> ر.س.</div>
                        <p class="product-description">هنا يأتي وصف المنتج. يمكننا إضافة حقل للوصف في قاعدة البيانات لاحقًا.</p>
                        <button class="modal-btn add-to-cart-btn" data-product-id="<?= $product['id'] ?>">أضف إلى السلة</button>
                    </div>
                </div>

                <!-- قسم المراجعات والتعليقات -->
                <div class="reviews-section">
                    <h2>التقييمات والمراجعات</h2>
                    <div class="reviews-list">
                        <?php if (count($reviews) > 0): ?>
                            <?php foreach ($reviews as $review): ?>
                                <div class="testimonial-card">
                                    <div class="testimonial-image"><img src="https://cdn.assets.salla.network/prod/stores/themes/default/assets/images/avatar_male.png" alt="User Avatar"></div>
                                    <div class="review-content">
                                        <h4 class="testimonial-author"><?= htmlspecialchars($review['full_name']) ?></h4>
                                        <div class="testimonial-rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?><span class="star <?= ($i <= $review['rating']) ? 'filled' : '' ?>">★</span><?php endfor; ?>
                                        </div>
                                        <p class="testimonial-text"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>لا توجد مراجعات لهذا المنتج حتى الآن. كن أول من يضع مراجعة!</p>
                        <?php endif; ?>
                    </div>

                    <!-- نموذج إضافة مراجعة (يظهر للمستخدم المسجل فقط) -->
                    <?php if (isLoggedIn()): ?>
                        <div class="add-review-form">
                            <h3>أضف مراجعتك</h3>
                            <form id="review-form">
                                <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                <div class="form-group rating-group">
                                    <label>تقييمك:</label>
                                    <div class="stars">
                                        <span data-value="5">★</span>
                                        <span data-value="4">★</span>
                                        <span data-value="3">★</span>
                                        <span data-value="2">★</span>
                                        <span data-value="1">★</span>
                                    </div>
                                    <input type="hidden" name="rating" id="rating-value" required>
                                </div>
                                <div class="form-group">
                                    <textarea name="comment" rows="4" placeholder="اكتب تعليقك هنا..." required></textarea>
                                </div>
                                <button type="submit" class="modal-btn">إرسال المراجعة</button>
                            </form>
                        </div>
                    <?php else: ?>
                        <p class="login-prompt">يجب عليك <a href="#" class="open-login-modal">تسجيل الدخول</a> لتتمكن من إضافة مراجعة.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>

        <!-- (يمكنك إضافة الفوتر هنا) -->
    </div>

    <!-- (يجب إضافة كود النوافذ المنبثقة هنا إذا لم يكن الهيدر موجودًا) -->
    <script src="script.js"></script>
</body>
</html>