<?php
/**
 * الشريط العلوي
 */
$user = Auth::user();
?>
<header class="topbar" id="topbar">
    
    <!-- زر القائمة للجوال -->
    <button type="button" class="topbar-btn menu-toggle" id="menuToggle" aria-label="Toggle menu">
        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    
    <!-- العنوان -->
    <h1 class="topbar-title"><?= e('dashboard') ?></h1>
    
    <!-- المساحة الفارغة -->
    <div class="topbar-spacer"></div>
    
    <!-- البحث السريع -->
    <div class="topbar-search">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
        <input type="text" placeholder="<?= e('search') ?>..." id="globalSearch">
    </div>
    
    <!-- تبديل اللغة -->
    <a href="?lang=<?= Lang::isArabic() ? 'en' : 'ar' ?>" class="topbar-btn" title="<?= e('language') ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="2" y1="12" x2="22" y2="12"></line>
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
        </svg>
        <span class="d-none-sm"><?= Lang::isArabic() ? 'EN' : 'ع' ?></span>
    </a>
    
    <!-- تبديل الثيم -->
    <button type="button" class="topbar-btn" id="themeToggle" title="<?= e('theme') ?>">
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
    
    <!-- التنبيهات -->
    <div class="dropdown">
        <button type="button" class="topbar-btn notification-btn" id="notificationsToggle">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span class="notification-badge" id="notificationCount" style="display:none;">0</span>
        </button>
        <div class="dropdown-menu notifications-menu" id="notificationsMenu">
            <div class="dropdown-header">
                <span><?= e('notifications') ?></span>
            </div>
            <div class="dropdown-body" id="notificationsList">
                <p class="empty-state"><?= e('no_notifications') ?></p>
            </div>
        </div>
    </div>
    
    <!-- الملف الشخصي -->
    <div class="dropdown">
        <button type="button" class="topbar-btn user-btn" id="userToggle">
            <span class="user-avatar"><?= mb_substr($user['full_name'] ?? 'U', 0, 1) ?></span>
            <span class="user-name d-none-sm"><?= e($user['full_name'] ?? '') ?></span>
        </button>
        <div class="dropdown-menu user-menu" id="userMenu">
            <div class="user-info">
                <span class="user-avatar-large"><?= mb_substr($user['full_name'] ?? 'U', 0, 1) ?></span>
                <div>
                    <strong><?= e($user['full_name'] ?? '') ?></strong>
                    <small><?= e($user['role'] ?? '') ?></small>
                </div>
            </div>
            <hr>
            <a href="<?= url('/profile') ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <?= e('profile') ?>
            </a>
            <a href="<?= url('/logout') ?>" class="text-danger">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <?= e('logout') ?>
            </a>
        </div>
    </div>
    
</header>