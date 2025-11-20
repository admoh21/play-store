document.addEventListener('DOMContentLoaded', () => {
    // --- 1. متغيرات وإعدادات عامة ---
    const body = document.body;
    const isLoggedIn = body.dataset.isLoggedIn === 'true';
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const apiAuthURL = 'api/auth.php';
    const apiCartURL = 'api/cart_manager.php';
    const apiTestimonialURL = 'api/submit_testimonial.php';
    
    // --- 2. التحكم في القائمة الجانبية والبحث والغطاء ---
    const menuToggleBtn = document.getElementById('menu-toggle-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    const closeMenuBtn = document.getElementById('close-menu-btn');
    const overlay = document.getElementById('overlay');
    const searchOverlay = document.getElementById('search-overlay');
    const openSearchDesktop = document.getElementById('open-search-btn');
    const openSearchMobile = document.getElementById('open-search-btn-mobile');
    const closeSearchBtn = document.getElementById('close-search-btn');
    const categoriesBtnMobile = document.getElementById('categories-btn-mobile');

    function openMenu() { if(mobileMenu) { mobileMenu.classList.add('active'); overlay.classList.add('active'); body.style.overflow = 'hidden'; } }
    function closeMenu() { if(mobileMenu) { mobileMenu.classList.remove('active'); overlay.classList.remove('active'); body.style.overflow = ''; } }
    function openSearch() { if(searchOverlay) { searchOverlay.classList.add('active'); setTimeout(() => document.getElementById('search-input').focus(), 300); } }
    function closeSearch() { if(searchOverlay) { searchOverlay.classList.remove('active'); } }

    if(menuToggleBtn) menuToggleBtn.addEventListener('click', openMenu);
    if(categoriesBtnMobile) categoriesBtnMobile.addEventListener('click', openMenu);
    if(closeMenuBtn) closeMenuBtn.addEventListener('click', closeMenu);
    if(overlay) overlay.addEventListener('click', closeMenu);
    if(openSearchDesktop) openSearchDesktop.addEventListener('click', (e) => { e.preventDefault(); openSearch(); });
    if(openSearchMobile) openSearchMobile.addEventListener('click', openSearch);
    if(closeSearchBtn) closeSearchBtn.addEventListener('click', closeSearch);

    // --- 3. التحكم في نوافذ الدخول، التسجيل، والملف الشخصي ---
    const loginModal = document.getElementById('login-modal');
    const registerModal = document.getElementById('register-modal');
    const profileModal = document.getElementById('profile-modal');
    const userIcons = document.querySelectorAll('.user-icon');
    const showRegisterLink = document.getElementById('show-register-link');
    const showLoginLink = document.getElementById('show-login-link');
    const allCloseModalButtons = document.querySelectorAll('.modal .close-btn');


    function closeAllModals() {
        if(loginModal) loginModal.classList.remove('active');
        if(registerModal) registerModal.classList.remove('active');
        if(profileModal) profileModal.classList.remove('active');
    }
    
    function openLoginModal() { closeAllModals(); if(loginModal) loginModal.classList.add('active'); }
    function openRegisterModal() { closeAllModals(); if(registerModal) registerModal.classList.add('active'); }
    function openProfileModal() { closeAllModals(); if(profileModal) profileModal.classList.add('active'); }
    
    userIcons.forEach(icon => {
        icon.addEventListener('click', (e) => {
            e.preventDefault();
            if (isLoggedIn) {
                openProfileModal();
            } else {
                openLoginModal();
            }
        });
    });

    if(showRegisterLink) showRegisterLink.addEventListener('click', (e) => { e.preventDefault(); openRegisterModal(); });
    if(showLoginLink) showLoginLink.addEventListener('click', (e) => { e.preventDefault(); openLoginModal(); });
    allCloseModalButtons.forEach(button => button.addEventListener('click', closeAllModals));
    

    // --- 4. معالجة نماذج الدخول والتسجيل ---
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    
    async function handleAuthForm(form, action, errorDiv) {
        const formData = new FormData(form);
        formData.append('action', action);
        formData.append('csrf_token', csrfToken);
        errorDiv.textContent = '';
        try {
            const response = await fetch(apiAuthURL, { method: 'POST', body: formData });
            const result = await response.json();
            if (result.status === 'success') {
                showToast(result.message);
                if (action === 'login') {
                    // تحديث رمز CSRF إذا تم إرساله
                    if (result.csrf_token) {
                        csrfToken = result.csrf_token;
                        document.querySelector('meta[name="csrf-token"]').setAttribute('content', csrfToken);
                    }
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    openLoginModal();
                }
            } else {
                errorDiv.textContent = result.message;
            }
        } catch (error) {
            errorDiv.textContent = 'حدث خطأ في الشبكة.';
        }
    }

    if (loginForm) {
        const loginErrorDiv = document.getElementById('login-error');
        loginForm.addEventListener('submit', (e) => { e.preventDefault(); handleAuthForm(loginForm, 'login', loginErrorDiv); });
    }
    if (registerForm) {
        const registerErrorDiv = document.getElementById('register-error');
        registerForm.addEventListener('submit', (e) => { e.preventDefault(); handleAuthForm(registerForm, 'register', registerErrorDiv); });
    }

    // --- 5. نظام السلة التفاعلي ---
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
    const cartCounters = document.querySelectorAll('.cart-counter');
    
    function showToast(message) {
        let toast = document.querySelector('.toast-notification');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast-notification';
            document.body.appendChild(toast);
        }
        toast.textContent = message;
        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => { if (toast) toast.remove(); }, 300);
        }, 3000);
    }

    async function addToCart(productId) {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('csrf_token', csrfToken);
        try {
            const response = await fetch(apiCartURL, { method: 'POST', body: formData });
            const result = await response.json();

            if (typeof result.cart_count !== 'undefined') {
                cartCounters.forEach(counter => {
                    counter.textContent = result.cart_count;
                });
            }
            
            showToast(result.message);

            if (result.action_required === 'login') {
                openLoginModal();
            }
        } catch (error) {
            console.error('Cart Error:', error);
            showToast('حدث خطأ في الشبكة.');
        }
    }

    addToCartButtons.forEach(button => {
        button.addEventListener('click', () => {
            const productId = button.dataset.productId;
            addToCart(productId);
        });
    });

    // --- 6. تفعيل السلايدرات ---
    document.querySelectorAll('.products-slider').forEach(slider => {
        new Swiper(slider, {
            loop: false, spaceBetween: 20,
            breakpoints: { 320: { slidesPerView: 2, spaceBetween: 15 }, 768: { slidesPerView: 3 }, 1024: { slidesPerView: 4 } },
            navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        });
    });
    new Swiper('.testimonials-slider', {
        loop: true, spaceBetween: 20,
        breakpoints: { 320: { slidesPerView: 1, spaceBetween: 15 }, 768: { slidesPerView: 2 }, 1024: { slidesPerView: 3 } },
        navigation: { nextEl: '.swiper-button-next', prevEl: '.swiper-button-prev' },
        autoplay: { delay: 5000 },
    });
    
    // --- 7. زر الصعود للأعلى ---
    const scrollToTopBtn = document.getElementById('scroll-to-top-btn');
    if (scrollToTopBtn) {
        const progressPath = document.querySelector('.scroll-to-top-btn .scroll-progress path');
        if (progressPath) {
            const pathLength = progressPath.getTotalLength();
            progressPath.style.strokeDasharray = pathLength;
            progressPath.style.strokeDashoffset = pathLength;
            const updateProgress = () => {
                const scroll = window.scrollY;
                const height = document.documentElement.scrollHeight - window.innerHeight;
                if (height > 0) {
                    const progress = pathLength - (scroll * pathLength / height);
                    progressPath.style.strokeDashoffset = progress;
                }
                (scroll > 200) ? scrollToTopBtn.classList.add('active') : scrollToTopBtn.classList.remove('active');
            };
            window.addEventListener('scroll', updateProgress);
            scrollToTopBtn.addEventListener('click', () => { window.scrollTo({ top: 0, behavior: 'smooth' }); });
        }
    }

    // --- 8. منطق نموذج تقييم المتجر (مصحح) ---
    const testimonialForm = document.getElementById('testimonial-form');

    if (testimonialForm) {
        const stars = testimonialForm.querySelectorAll('.rating-group .stars span');
        const ratingInput = testimonialForm.querySelector('#rating-value');
        
        function colorStars(ratingValue) {
            stars.forEach(star => {
                star.classList.toggle('selected', star.dataset.value <= ratingValue);
            });
        }
        
        stars.forEach(star => {
            star.addEventListener('mouseover', function() {
                colorStars(this.dataset.value);
            });
            
            star.addEventListener('click', function() {
                ratingInput.value = this.dataset.value;
                colorStars(this.dataset.value);
            });
        });

        const starsContainer = testimonialForm.querySelector('.rating-group .stars');
        starsContainer.addEventListener('mouseleave', () => {
            colorStars(ratingInput.value);
        });

        testimonialForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            if (!ratingInput.value) {
                alert('الرجاء اختيار تقييم بالنجوم.');
                return;
            }
            
            const formData = new FormData(this);
            try {
                const response = await fetch(apiTestimonialURL, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                alert(result.message);
                
                if (result.status === 'success') {
                    window.location.reload();
                }
            } catch(error) {
                alert('حدث خطأ في الشبكة.');
            }
        });
    }
});


