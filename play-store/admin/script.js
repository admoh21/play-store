document.addEventListener('DOMContentLoaded', () => {
    const apiURL = '../api/manage_products.php';
    const tableBody = document.getElementById('products-table-body');
    const modal = document.getElementById('product-modal');
    const modalTitle = document.getElementById('modal-title');
    const productForm = document.getElementById('product-form');
    const addProductBtn = document.getElementById('add-product-btn');
    const closeModalBtn = modal.querySelector('.close-modal-btn');

    async function fetchProducts() {
        try {
            const response = await fetch(apiURL);
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            const products = await response.json();
            
            tableBody.innerHTML = '';
            if (products.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5">لا توجد منتجات.</td></tr>';
                return;
            }

            products.forEach(product => {
                const imageUrl = product.image_url ? `../${product.image_url}` : 'placeholder.png'; // صورة افتراضية
                const row = `
                    <tr>
                        <td><img src="${imageUrl}" alt="${product.name}"></td>
                        <td>${product.name}</td>
                        <td>${product.price} ر.س.</td>
                        <td>${product.category_name || 'غير محدد'}</td>
                        <td>
                            <button class="action-btn edit-btn" data-id="${product.id}">تعديل</button>
                            <button class="action-btn delete-btn" data-id="${product.id}">حذف</button>
                        </td>
                    </tr>
                `;
                tableBody.innerHTML += row;
            });
        } catch (error) {
            console.error('Error fetching products:', error);
            tableBody.innerHTML = `<tr><td colspan="5">فشل في تحميل المنتجات.</td></tr>`;
        }
    }

    const openModal = () => modal.style.display = 'flex';
    const closeModal = () => modal.style.display = 'none';

    addProductBtn.addEventListener('click', () => {
        productForm.reset();
        document.getElementById('product-id').value = '';
        modalTitle.textContent = 'إضافة منتج جديد';
        document.getElementById('product-image').required = true;
        openModal();
    });
    closeModalBtn.addEventListener('click', closeModal);

    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(productForm);

        try {
            const response = await fetch(apiURL, {
                method: 'POST',
                body: formData 
            });
            const result = await response.json();
            alert(result.message);
            if (result.status === 'success') {
                closeModal();
                fetchProducts();
            }
        } catch (error) {
            console.error('Error saving product:', error);
            alert('حدث خطأ فادح أثناء حفظ المنتج. تحقق من console.');
        }
    });

    tableBody.addEventListener('click', async (e) => {
        const target = e.target;
        if (target.classList.contains('delete-btn')) {
            const id = target.dataset.id;
            if (confirm('هل أنت متأكد من أنك تريد حذف هذا المنتج؟')) {
                try {
                    const response = await fetch(`${apiURL}?id=${id}`, { method: 'DELETE' });
                    const result = await response.json();
                    alert(result.message);
                    if (result.status === 'success') fetchProducts();
                } catch (error) {
                    console.error('Error deleting product:', error);
                }
            }
        }

        if (target.classList.contains('edit-btn')) {
            const id = target.dataset.id;
            const response = await fetch(apiURL);
            const products = await response.json();
            const productToEdit = products.find(p => p.id == id);
            
            if (productToEdit) {
                document.getElementById('product-id').value = productToEdit.id;
                document.getElementById('product-name').value = productToEdit.name;
                document.getElementById('product-category').value = productToEdit.category_id;
                document.getElementById('product-price').value = productToEdit.price;
                document.getElementById('product-original-price').value = productToEdit.original_price;
                document.getElementById('current-image-url').value = productToEdit.image_url;
                document.getElementById('product-image').required = false;
                modalTitle.textContent = 'تعديل المنتج';
                openModal();
            }
        }
    });

    fetchProducts();
});