// checkout.js
document.addEventListener('DOMContentLoaded', () => {
    const paymentForm = document.getElementById('payment-form');
    const checkoutButton = document.querySelector('.checkout-btn');
    const paymentOptions = document.querySelectorAll('.payment-option');

    // إضافة تأثير بصري عند اختيار طريقة الدفع
    paymentOptions.forEach(option => {
        option.addEventListener('click', () => {
            paymentOptions.forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');
        });
    });


    if (paymentForm) {
        paymentForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            checkoutButton.textContent = 'جاري المعالجة...';
            checkoutButton.disabled = true;

            const selectedMethodInput = document.querySelector('input[name="payment_method"]:checked');
            if (!selectedMethodInput) {
                alert('الرجاء اختيار طريقة الدفع.');
                checkoutButton.textContent = 'إتمام الدفع الآن';
                checkoutButton.disabled = false;
                return;
            }
            const selectedMethod = selectedMethodInput.value;
            
            try {
                const response = await fetch('api/initiate-universal-payment.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ payment_method: selectedMethod })
                });

                if (!response.ok) {
                    throw new Error('فشل في الاتصال بالخادم.');
                }
                
                const result = await response.json();

                if (result.status === 'success') {
                    if (result.type === 'stripe') {
                        if (typeof STRIPE_PUBLISHABLE_KEY !== 'undefined' && STRIPE_PUBLISHABLE_KEY) {
                            const stripe = Stripe(STRIPE_PUBLISHABLE_KEY);
                            stripe.redirectToCheckout({ sessionId: result.sessionId });
                        } else {
                             alert('خطأ في إعدادات Stripe. يرجى مراجعة المشرف.');
                        }
                    } else if (result.type === 'redirect') {
                        window.location.href = result.payment_url;
                    }
                } else {
                    alert('خطأ: ' + result.message);
                    checkoutButton.disabled = false;
                    checkoutButton.textContent = 'إتمام الدفع الآن';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('حدث خطأ في الشبكة. يرجى المحاولة مرة أخرى.');
                checkoutButton.disabled = false;
                checkoutButton.textContent = 'إتمام الدفع الآن';
            }
        });
    }
});