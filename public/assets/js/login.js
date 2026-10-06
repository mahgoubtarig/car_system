/**
 * منطق شاشة تسجيل الدخول
 */
(function() {
    'use strict';
    
    const t = (key) => (window.APP_CONFIG.translations || {})[key] || key;
    
    document.addEventListener('DOMContentLoaded', function() {
        
        const form = document.getElementById('loginForm');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        const usernameError = document.getElementById('usernameError');
        const passwordError = document.getElementById('passwordError');
        const loginBtn = document.getElementById('loginBtn');
        const btnText = loginBtn.querySelector('.btn-text');
        const btnLoader = loginBtn.querySelector('.btn-loader');
        const togglePassword = document.getElementById('togglePassword');
        
        // ============================================================
        // تبديل عرض كلمة المرور
        // ============================================================
        if (togglePassword) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                
                const eye = this.querySelector('.icon-eye');
                const eyeOff = this.querySelector('.icon-eye-off');
                
                if (type === 'text') {
                    eye.style.display = 'none';
                    eyeOff.style.display = 'block';
                } else {
                    eye.style.display = 'block';
                    eyeOff.style.display = 'none';
                }
            });
        }
        
        // ============================================================
        // إزالة رسائل الخطأ عند الكتابة
        // ============================================================
        usernameInput.addEventListener('input', () => {
            usernameInput.classList.remove('error');
            usernameError.textContent = '';
        });
        
        passwordInput.addEventListener('input', () => {
            passwordInput.classList.remove('error');
            passwordError.textContent = '';
        });
        
        // ============================================================
        // التحقق من صحة المدخلات
        // ============================================================
        function validateForm() {
            let isValid = true;
            
            const username = usernameInput.value.trim();
            const password = passwordInput.value;
            
            if (!username) {
                usernameError.textContent = t('error_required_username');
                usernameInput.classList.add('error');
                isValid = false;
            }
            
            if (!password) {
                passwordError.textContent = t('error_required_password');
                passwordInput.classList.add('error');
                isValid = false;
            }
            
            return isValid;
        }
        
        // ============================================================
        // حالة الزر
        // ============================================================
        function setLoading(loading) {
            loginBtn.disabled = loading;
            usernameInput.disabled = loading;
            passwordInput.disabled = loading;
            
            if (loading) {
                btnText.style.display = 'none';
                btnLoader.style.display = 'inline-flex';
            } else {
                btnText.style.display = 'inline';
                btnLoader.style.display = 'none';
            }
        }
        
        // ============================================================
        // إرسال النموذج
        // ============================================================
        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            // إزالة الأخطاء السابقة
            document.querySelectorAll('.form-error').forEach(el => el.textContent = '');
            document.querySelectorAll('.form-input').forEach(el => el.classList.remove('error'));
            const globalError = document.getElementById('globalError');
            if (globalError) globalError.remove();
            
            if (!validateForm()) {
                return;
            }
            
            setLoading(true);
            
            try {
                const formData = new FormData(form);
                
                const response = await fetch(form.action || window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                });
                
                const data = await response.json();
                
                if (data.success) {
                    btnText.textContent = '✓ تم الدخول';
                    setTimeout(() => {
                        window.location.href = data.redirect || '/dashboard';
                    }, 300);
                } else {
                    setLoading(false);
                    
                    if (data.errors) {
                        if (data.errors.username) {
                            usernameError.textContent = data.errors.username;
                            usernameInput.classList.add('error');
                        }
                        if (data.errors.password) {
                            passwordError.textContent = data.errors.password;
                            passwordInput.classList.add('error');
                        }
                    }
                    
                    // عرض الخطأ العام
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-error';
                    alert.id = 'globalError';
                    alert.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span>${data.message || t('error_general')}</span>
                    `;
                    form.parentNode.insertBefore(alert, form);
                }
                
            } catch (error) {
                setLoading(false);
                console.error('Login error:', error);
                alert('حدث خطأ في الاتصال بالخادم');
            }
        });
        
        // التركيز على حقل اسم المستخدم
        if (usernameInput && !usernameInput.value) {
            usernameInput.focus();
        }
    });
})();