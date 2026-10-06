/**
 * إدارة الثيم (Light / Dark / System)
 */
(function() {
    'use strict';
    
    const STORAGE_KEY = 'theme';
    const THEMES = ['light', 'dark', 'system'];
    
    const ThemeManager = {
        /**
         * الحصول على الثيم المحفوظ
         */
        getSaved() {
            return localStorage.getItem(STORAGE_KEY) || 'light';
        },
        
        /**
         * الثيم الفعلي المطبق
         */
        getEffective() {
            const saved = this.getSaved();
            if (saved === 'system') {
                return window.matchMedia('(prefers-color-scheme: dark)').matches 
                    ? 'dark' : 'light';
            }
            return saved;
        },
        
        /**
         * تطبيق الثيم
         */
        apply(theme) {
            if (!THEMES.includes(theme)) theme = 'light';
            
            // حفظ التفضيل
            localStorage.setItem(STORAGE_KEY, theme);
            
            // الثيم الفعلي
            const effective = theme === 'system'
                ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
                : theme;
            
            // تطبيق على DOM
            document.documentElement.setAttribute('data-theme', effective);
            document.documentElement.setAttribute('data-theme-preference', theme);
            
            // تحديث meta theme-color
            const metaTheme = document.querySelector('meta[name="theme-color"]');
            if (metaTheme) {
                metaTheme.setAttribute('content', effective === 'dark' ? '#0f1419' : '#f5f7fa');
            } else {
                const meta = document.createElement('meta');
                meta.name = 'theme-color';
                meta.content = effective === 'dark' ? '#0f1419' : '#f5f7fa';
                document.head.appendChild(meta);
            }
            
            // إرسال للخادم إذا كان المستخدم مسجلاً
            this.syncWithServer(theme);
        },
        
        /**
         * تبديل الثيم
         */
        toggle() {
            const current = this.getEffective();
            const next = current === 'dark' ? 'light' : 'dark';
            this.apply(next);
            return next;
        },
        
        /**
         * مزامنة مع الخادم
         */
        syncWithServer(theme) {
            // فقط إذا كان المستخدم مسجلاً (نتحقق من وجود APP_CONFIG)
            if (typeof window.APP_CONFIG === 'undefined') return;
            if (!window.APP_CONFIG.csrfToken) return;
            
            // إرسال الطلب (بدون انتظار)
            fetch(window.APP_CONFIG.baseUrl + '/api/theme', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.APP_CONFIG.csrfToken,
                },
                body: JSON.stringify({ theme }),
            }).catch(() => {
                // تجاهل الأخطاء (الشبكة مثلاً)
            });
        },
        
        /**
         * الاستماع لتغيير نظام التشغيل
         */
        watchSystemTheme() {
            const media = window.matchMedia('(prefers-color-scheme: dark)');
            media.addEventListener('change', () => {
                if (this.getSaved() === 'system') {
                    this.apply('system');
                }
            });
        },
        
        /**
         * تهيئة
         */
        init() {
            // تطبيق الثيم المحفوظ
            this.apply(this.getSaved());
            
            // مراقبة تغييرات النظام
            this.watchSystemTheme();
            
            // ربط زر التبديل
            const toggleBtn = document.getElementById('themeToggle');
            if (toggleBtn) {
                toggleBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.toggle();
                });
            }
        },
    };
    
    // تصدير عام
    window.ThemeManager = ThemeManager;
    
    // التهيئة عند جاهزية DOM
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => ThemeManager.init());
    } else {
        ThemeManager.init();
    }
})();