<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - إدارة الأقسام</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>لوحة تحكم المتجر</h1>
            <nav class="main-nav">
                <a href="index.php">المنتجات</a>
                <a href="categories.php" class="active">الأقسام</a>
                <a href="payments.php" target="_blank"> ( البنكي )طرق الدفع</a>

                <a href="../index.php" target="_blank">عرض الموقع</a>
                <a href="logout.php">تسجيل الخروج</a>
            </nav>
        </header>

        <main class="dashboard-main">
            <div class="main-header">
                <h2>إدارة الأقسام</h2>
                <button id="add-category-btn" class="btn btn-primary">إضافة قسم جديد</button>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>اسم القسم</th>
                            <th>الوصف</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="categories-table-body">
                        <!-- البيانات ستأتي من JavaScript -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- النافذة المنبثقة لإدارة الأقسام -->
    <div id="category-modal" class="modal">
        <div class="modal-content">
            <button class="close-modal-btn close-btn">&times;</button>
            <h2 id="category-modal-title">إضافة قسم جديد</h2>
            <form id="category-form">
                <input type="hidden" id="category-id" name="id">
                <div class="form-group">
                    <label for="category-name">اسم القسم:</label>
                    <input type="text" id="category-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="category-description">وصف القسم (سيظهر تحت العنوان في الموقع):</label>
                    <textarea id="category-description" name="description" rows="3"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">حفظ القسم</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- كود JavaScript الخاص بالصفحة -->
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const apiURL = '../api/manage_categories.php';
        const tableBody = document.getElementById('categories-table-body');
        const modal = document.getElementById('category-modal');
        const modalTitle = document.getElementById('category-modal-title');
        const categoryForm = document.getElementById('category-form');
        const addCategoryBtn = document.getElementById('add-category-btn');
        const closeModalBtn = modal.querySelector('.close-modal-btn');

        async function fetchCategories() {
            try {
                const response = await fetch(apiURL);
                if (!response.ok) throw new Error('Network response was not ok');
                const categories = await response.json();
                
                tableBody.innerHTML = '';
                if (categories.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="3">لا توجد أقسام حاليًا.</td></tr>';
                    return;
                }
                
                categories.forEach(cat => {
                    const row = `
                        <tr>
                            <td>${cat.name}</td>
                            <td>${cat.description || '-'}</td>
                            <td>
                                <button class="action-btn edit-btn" data-id="${cat.id}">تعديل</button>
                                <button class="action-btn delete-btn" data-id="${cat.id}">حذف</button>
                            </td>
                        </tr>
                    `;
                    tableBody.innerHTML += row;
                });
            } catch (error) {
                console.error("Error fetching categories:", error);
                tableBody.innerHTML = '<tr><td colspan="3">فشل في تحميل الأقسام.</td></tr>';
            }
        }

        const openModal = () => modal.style.display = 'flex';
        const closeModal = () => modal.style.display = 'none';

        addCategoryBtn.addEventListener('click', () => {
            categoryForm.reset();
            document.getElementById('category-id').value = '';
            modalTitle.textContent = 'إضافة قسم جديد';
            openModal();
        });
        closeModalBtn.addEventListener('click', closeModal);

        categoryForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(categoryForm);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch(apiURL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                alert(result.message);
                if (result.status === 'success') {
                    closeModal();
                    fetchCategories();
                }
            } catch (error) {
                alert('حدث خطأ أثناء حفظ القسم.');
            }
        });

        tableBody.addEventListener('click', async (e) => {
            const target = e.target;
            if (target.classList.contains('delete-btn')) {
                const id = target.dataset.id;
                if (confirm('هل أنت متأكد؟ حذف القسم سيحذف كل المنتجات المرتبطة به.')) {
                    try {
                        const response = await fetch(`${apiURL}?id=${id}`, { method: 'DELETE' });
                        const result = await response.json();
                        alert(result.message);
                        if (result.status === 'success') fetchCategories();
                    } catch(error) {
                        alert('فشل حذف القسم.');
                    }
                }
            }
            if (target.classList.contains('edit-btn')) {
                const id = target.dataset.id;
                const response = await fetch(apiURL);
                const categories = await response.json();
                const catToEdit = categories.find(c => c.id == id);
                if (catToEdit) {
                    document.getElementById('category-id').value = catToEdit.id;
                    document.getElementById('category-name').value = catToEdit.name;
                    document.getElementById('category-description').value = catToEdit.description;
                    modalTitle.textContent = 'تعديل القسم';
                    openModal();
                }
            }
        });

        fetchCategories();
    });
    </script>
</body>
</html>