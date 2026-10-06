<?php
/**
 * القائمة الجانبية
 */
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$user = Auth::user();
// ✅ التعديل: تأمين قيمة الدور لمنع الأخطاء في حال كانت الجلسة مفقودة
$userRole = $user['role'] ?? 'guest';

// عناصر القائمة
$menuItems = [
    [
        'label' => 'dashboard',
        'icon'  => 'home',
        'url'   => '/dashboard',
        'roles' => ['admin', 'accountant', 'employee'],
    ],
    [
        'label' => 'cars',
        'icon'  => 'car',
        'url'   => '/cars',
        'roles' => ['admin', 'accountant', 'employee'],
    ],
    [
        'label' => 'customers',
        'icon'  => 'users',
        'url'   => '/customers',
        'roles' => ['admin', 'accountant', 'employee'],
    ],
    [
        'label' => 'rentals',
        'icon'  => 'file-text',
        'url'   => '/rentals',
        'roles' => ['admin', 'accountant', 'employee'],
    ],
    [
        'label' => 'payments',
        'icon'  => 'credit-card',
        'url'   => '/payments',
        'roles' => ['admin', 'accountant'],
    ],
    [
        'label' => 'expenses',
        'icon'  => 'trending-down',
        'url'   => '/expenses',
        'roles' => ['admin', 'accountant'],
    ],
    [
        'label' => 'reports',
        'icon'  => 'bar-chart',
        'url'   => '/reports',
        'roles' => ['admin', 'accountant'],
    ],
    [
        'label' => 'settings',
        'icon'  => 'settings',
        'url'   => '/settings',
        'roles' => ['admin'],
    ],
        [
        'label' => 'users',
        'icon'  => 'user-check',
        'url'   => '/users',
        'roles' => ['admin'],
    ],
];
?>
<aside class="sidebar" id="sidebar">
    
    <!-- الشعار -->
    <div class="sidebar-header">
        <a href="<?= url('/dashboard') ?>" class="sidebar-brand">
            <span class="brand-icon">🚗</span>
            <span class="brand-text"><?= e('app_name') ?></span>
        </a>
    </div>
    
    <!-- القائمة -->
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <?php foreach ($menuItems as $item): ?>
                <?php 
                // ✅ التعديل: استخدام المتغير الآمن
                if (in_array($userRole, $item['roles'], true)): 
                ?>
                    <?php 
                    $isActive = strpos($currentPath, $item['url']) !== false;
                    ?>
                    <li class="nav-item <?= $isActive ? 'active' : '' ?>">
                        <a href="<?= url($item['url']) ?>" class="nav-link">
                            <span class="nav-icon"><?= icon($item['icon']) ?></span>
                            <span class="nav-text"><?= e($item['label']) ?></span>
                        </a>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
    
    <!-- أسفل القائمة -->
    <div class="sidebar-footer">
        <a href="<?= url('/logout') ?>" class="nav-link logout-link">
            <span class="nav-icon"><?= icon('log-out') ?></span>
            <span class="nav-text"><?= e('logout') ?></span>
        </a>
    </div>
    
</aside>

<!-- Overlay للجوال -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php
/**
 * دالة مساعدة لعرض الأيقونات (SVG)
 */
function icon(string $name): string
{
    $icons = [
        'home'         => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>',
        'car'          => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 16H9m10 0h3v-3.15a1 1 0 0 0-.84-.99L16 11l-2.7-3.6a1 1 0 0 0-.8-.4H5.24a2 2 0 0 0-1.8 1.1l-.8 1.63A6 6 0 0 0 2 12.42V16h2"></path><circle cx="6.5" cy="16.5" r="2.5"></circle><circle cx="16.5" cy="16.5" r="2.5"></circle></svg>',
        'users'        => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>',
        'file-text'    => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>',
        'credit-card'  => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>',
        'trending-down'=> '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"></polyline><polyline points="17 18 23 18 23 12"></polyline></svg>',
        'bar-chart'    => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="20" x2="12" y2="10"></line><line x1="18" y1="20" x2="18" y2="4"></line><line x1="6" y1="20" x2="6" y2="16"></line></svg>',
        'settings'     => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>',
        'log-out'      => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>',
            'user-check'   => '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><polyline points="17 11 19 13 23 9"></polyline></svg>',
        ];
    
    return $icons[$name] ?? '';
}
?>