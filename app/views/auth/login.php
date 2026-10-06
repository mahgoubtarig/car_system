<!DOCTYPE html>
<html lang="<?= Lang::current() ?>" dir="<?= Lang::direction() ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= e('app_name') ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <title><?= e('login') ?> | <?= e('app_name') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚗</text></svg>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/login.css') ?>">
    
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            let theme = savedTheme;
            if (savedTheme === 'system') {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body class="login-page">

    <div class="login-bg">
        <div class="bg-shape bg-shape-1"></div>
        <div class="bg-shape bg-shape-2"></div>
        <div class="bg-shape bg-shape-3"></div>
    </div>
    
    <div class="login-topbar">
        <a href="?lang=ar" 
           class="btn-icon <?= Lang::isArabic() ? 'active' : '' ?>" 
           title="العربية"
           style="text-decoration: none;">
            🇸🇦 <span class="d-none-sm">العربية</span>
        </a>
        
        <a href="?lang=en" 
           class="btn-icon <?= !Lang::isArabic() ? 'active' : '' ?>" 
           title="English"
           style="text-decoration: none;">
            🇬🇧 <span class="d-none-sm">English</span>
        </a>
        
        <button type="button" class="btn-icon" id="themeToggle" title="<?= e('theme') ?>">
            <svg class="icon-sun" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line>
                <line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
            <svg class="icon-moon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
        </button>
    </div>
    
    <div class="login-container">
        <div class="login-card">
            
            <div class="login-logo">
                <div class="logo-icon">🚗</div>
                <h1 class="logo-title"><?= e('app_name') ?></h1>
                <p class="logo-subtitle"><?= e('login_subtitle') ?></p>
            </div>
            
            <!-- تم التعديل هنا: معالجة رسالة الخطأ واستبدال الدقائق -->
            <?php if (!empty($error) && is_string($error)): ?>
                <div class="alert alert-error" id="globalError">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <span>
                        <?php 
                        // 1. ترجمة الرسالة الرئيسية
                        $translatedMessage = e($error);
                        
                        // 2. التحقق مما إذا كان هناك بيانات إضافية (مثل عدد الدقائق) واستبدالها في النص
                        if (is_array($errorData) && !empty($errorData)) {
                            // نفترض أن العنصر الأول في المصفوفة هو عدد الدقائق
                            $minutes = $errorData[0]; 
                            
                            // البحث عن كلمة :minutes في النص المترجم واستبدالها بالرقم
                            if (strpos($translatedMessage, ':minutes') !== false) {
                                $translatedMessage = str_replace(':minutes', (string)$minutes, $translatedMessage);
                            } else {
                                // إذا لم توجد كلمة :minutes، أضف الرقم بجانب النص كالسابق (احتياطياً)
                                $translatedMessage .= ' — ' . $minutes;
                            }
                        }
                        
                        // طباعة الرسالة النهائية
                        echo $translatedMessage;
                        ?>
                    </span>
                </div>
            <?php endif; ?>
            
            <!-- تم التعديل هنا: إضافة مسار تسجيل الدخول إلى خاصية action -->
            <form id="loginForm" class="login-form" method="POST" action="<?= url('/login') ?>" novalidate>
                
                <?= Csrf::field() ?>
                
                <div class="form-group">
                    <label for="username" class="form-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <?= e('username') ?>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-input" 
                            placeholder="<?= e('username_placeholder') ?>"
                            autocomplete="username"
                            autofocus
                            required
                            maxlength="50"
                            value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        >
                        <span class="input-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                    </div>
                    <span class="form-error" id="usernameError"></span>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                        </svg>
                        <?= e('password') ?>
                    </label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="<?= e('password_placeholder') ?>"
                            autocomplete="current-password"
                            required
                            maxlength="255"
                        >
                        <button type="button" class="input-icon toggle-password" id="togglePassword" tabindex="-1">
                            <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <span class="form-error" id="passwordError"></span>
                </div>
                
                <div class="form-options">
                    <label class="checkbox-wrapper">
                        <input type="checkbox" name="remember" value="1">
                        <span class="checkbox-custom"></span>
                        <span><?= e('remember_me') ?></span>
                    </label>
                    <a href="#" class="link-forgot" onclick="return false;">
                        <?= e('forgot_password') ?>
                    </a>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">
                    <span class="btn-text"><?= e('login_button') ?></span>
                    <span class="btn-loader" style="display:none;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="spinner">
                            <circle cx="12" cy="12" r="10" opacity="0.25"></circle>
                            <path d="M12 2a10 10 0 0 1 10 10" opacity="1"></path>
                        </svg>
                        <?= e('logging_in') ?>
                    </span>
                </button>
            </form>
            
            <div class="login-security">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                </svg>
                <span><?= e('security_notice') ?></span>
            </div>
        </div>
        
        <footer class="login-footer">
            <p>
                &copy; <?= date('Y') ?> <?= e('app_name') ?> — <?= e('footer_copyright') ?>
                <span class="version">v1.0.0</span>
            </p>
        </footer>
    </div>
    
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= url('') ?>',
            locale: '<?= Lang::current() ?>',
            direction: '<?= Lang::direction() ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
            translations: {
                error_required_username: '<?= e('error_required_username') ?>',
                error_required_password: '<?= e('error_required_password') ?>',
                error_general: '<?= e('error_general') ?>',
                error_csrf: '<?= e('error_csrf') ?>',
                error_invalid_credentials: '<?= e('error_invalid_credentials') ?>',
                logging_in: '<?= e('logging_in') ?>',
                login_button: '<?= e('login_button') ?>',
            }
        };
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <!-- تم تعطيل login.js لأن النموذج يرسل POST عادي -->
    <!-- <script src="<?= url('/assets/js/login.js') ?>"></script> -->
    
    <script>
        // سكربت بسيط: عرض/إخفاء كلمة المرور + التركيز
        (function() {
            'use strict';
            document.addEventListener('DOMContentLoaded', function() {
                // تبديل عرض كلمة المرور
                const togglePassword = document.getElementById('togglePassword');
                const passwordInput = document.getElementById('password');
                
                if (togglePassword && passwordInput) {
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
                
                // التركيز على حقل اسم المستخدم
                const usernameInput = document.getElementById('username');
                if (usernameInput && !usernameInput.value) {
                    usernameInput.focus();
                }
            });
        })();
    </script>
</body>
</html>