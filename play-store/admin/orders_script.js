// admin/orders_script.js
document.addEventListener('DOMContentLoaded', () => {
    const tableBody = document.querySelector('table tbody');

    if (tableBody) {
        tableBody.addEventListener('click', async (e) => {
            const target = e.target;
            let action = null;
            
            if (target.classList.contains('complete-order-btn')) {
                action = 'complete';
            } else if (target.classList.contains('cancel-order-btn')) {
                action = 'cancel';
            }

            if (action) {
                const orderId = target.dataset.orderId;
                const confirmationMessage = action === 'complete' 
                    ? 'هل أنت متأكد من أنك استلمت المبلغ وتريد تأكيد هذا الطلب؟'
                    : 'هل أنت متأكد من أنك تريد رفض هذا الطلب؟';
                
                if (confirm(confirmationMessage)) {
                    target.closest('td').querySelectorAll('button').forEach(btn => btn.disabled = true);

                    try {
                        const formData = new FormData();
                        formData.append('order_id', orderId);
                        formData.append('action', action);

                        // *** التصحيح الرئيسي هنا: إضافة ../ للمسار ***
                        const response = await fetch('../api/manage_orders.php', {
                            method: 'POST',
                            body: formData
                        });

                        // التأكد من أن الرد ليس خطأ 404 أو 500
                        if (!response.ok) {
                            throw new Error(`Server responded with status: ${response.status}`);
                        }
                        
                        const result = await response.json();
                        
                        if (result.status === 'success') {
                            alert(result.message);
                            const row = target.closest('tr');
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 300);
                        } else {
                            alert('خطأ من الخادم: ' + (result.message || 'فشل تحديث الطلب.'));
                            target.closest('td').querySelectorAll('button').forEach(btn => btn.disabled = false);
                        }
                    } catch (error) {
                        console.error("Fetch Error:", error);
                        alert('حدث خطأ في الشبكة أو في استجابة الخادم. تحقق من الـ console لمزيد من التفاصيل.');
                        target.closest('td').querySelectorAll('button').forEach(btn => btn.disabled = false);
                    }
                }
            }
        });
    }
});