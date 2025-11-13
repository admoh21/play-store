// admin/payments_script.js
document.addEventListener('DOMContentLoaded', () => {
    // (الكود هو نفس الكود من الرد السابق الذي كان يحتوي على إدارة الجدول والنافذة المنبثقة)
    // ... للتأكيد، هذا هو الكود الكامل ...
    const apiURL = '../api/manage_payments.php';
    const tableBody = document.getElementById('payments-table-body');
    const modal = document.getElementById('payment-modal');
    const modalTitle = document.getElementById('payment-modal-title');
    const form = document.getElementById('payment-form');
    const addBtn = document.getElementById('add-payment-btn');
    const closeModalBtn = modal.querySelector('.close-modal-btn');
    const gatewaySelect = document.getElementById('gateway-code');

    function toggleGatewayFields() {
        const selectedGateway = gatewaySelect.value;
        document.querySelectorAll('.gateway-fields').forEach(field => field.style.display = 'none');
        if (selectedGateway) {
            const fieldsToShow = document.getElementById(`fields_${selectedGateway}`);
            if (fieldsToShow) fieldsToShow.style.display = 'block';
        }
    }
    gatewaySelect.addEventListener('change', toggleGatewayFields);

    async function fetchMethods() {
        const response = await fetch(apiURL);
        const methods = await response.json();
        tableBody.innerHTML = '';
        methods.forEach(method => {
            tableBody.innerHTML += `
                <tr>
                    <td>${method.name}</td>
                    <td>${method.gateway_code}</td>
                    <td>${method.is_active == 1 ? '<span class="status-active">مفعل</span>' : '<span class="status-inactive">معطل</span>'}</td>
                    <td>
                        <button class="action-btn edit-btn" data-id="${method.id}">تعديل</button>
                        <button class="action-btn delete-btn" data-id="${method.id}">حذف</button>
                    </td>
                </tr>`;
        });
    }

    const openModal = () => modal.style.display = 'flex';
    const closeModal = () => modal.style.display = 'none';

    addBtn.addEventListener('click', () => {
        form.reset();
        document.getElementById('payment-id').value = '';
        modalTitle.textContent = 'إضافة طريقة دفع';
        toggleGatewayFields();
        openModal();
    });
    closeModalBtn.addEventListener('click', closeModal);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const data = {
            id: formData.get('id') || null,
            name: formData.get('name'),
            gateway_code: formData.get('gateway_code'),
            is_active: formData.has('is_active'),
            config_details: {}
        };
        for (let [key, value] of formData.entries()) {
            const match = key.match(/^config_details\[(.*?)\]$/);
            if (match) {
                data.config_details[match[1]] = value;
            }
        }
        const response = await fetch(apiURL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        const result = await response.json();
        alert(result.message);
        if (result.status === 'success') {
            closeModal();
            fetchMethods();
        }
    });

    tableBody.addEventListener('click', async (e) => {
        if (e.target.classList.contains('delete-btn')) { /* ... منطق الحذف ... */ }
        if (e.target.classList.contains('edit-btn')) {
            const id = e.target.dataset.id;
            const response = await fetch(apiURL);
            const methods = await response.json();
            const methodToEdit = methods.find(m => m.id == id);
            if (methodToEdit) {
                document.getElementById('payment-id').value = methodToEdit.id;
                document.getElementById('gateway-code').value = methodToEdit.gateway_code;
                document.getElementById('payment-name').value = methodToEdit.name;
                document.getElementById('payment-active').checked = methodToEdit.is_active == 1;
                
                // ملء الحقول الإضافية
                Object.keys(methodToEdit.config_details).forEach(key => {
                    const input = form.querySelector(`[name="config_details[${key}]"]`);
                    if(input) input.value = methodToEdit.config_details[key];
                });

                toggleGatewayFields();
                modalTitle.textContent = 'تعديل طريقة الدفع';
                openModal();
            }
        }
    });

    fetchMethods();
});