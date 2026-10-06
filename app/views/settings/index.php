<?php
/**
 * صفحة الإعدادات
 */
if (!Auth::isAdmin()) {
    header('Location: ' . url('/dashboard'));
    exit;
}

// Tabs
$activeTab = $_GET['tab'] ?? 'office';

// جلب الإعدادات من قاعدة البيانات مباشرة لتجنب أي نقص من الكنترولر
$settingModel = new Setting();
$settingsData = $settingModel->getAll();

// تهيئة المتغيرات
$office = $settingsData;
$notifications = $settingsData;
$tax = $settingsData;
$terms = $settingsData;
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('settings') ?> | <?= e('app_name') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚙️</text></svg>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/cars.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/rentals.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/settings.css') ?>">
    
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
<body class="dashboard-page">

    <div class="app-layout">
        
        <?php require BASE_PATH . '/app/views/layout/sidebar.php'; ?>
        
        <div class="main-wrapper">
            
            <?php require BASE_PATH . '/app/views/layout/header.php'; ?>
            
            <main class="main-content">
                
                <!-- Header -->
                <div class="page-header">
                    <div>
                        <h2 class="page-title">⚙️ <?= e('settings') ?></h2>
                        <p class="page-subtitle"><?= e('settings_subtitle') ?></p>
                    </div>
                </div>
                
                <!-- Flash -->
                <?php $successMsg = Auth::getFlash('success'); ?>
                <?php if (!empty($successMsg) && is_string($successMsg)): ?>
                    <div class="alert alert-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span><?= e($successMsg) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php $errorMsg = Auth::getFlash('error'); ?>
                <?php if (!empty($errorMsg) && is_string($errorMsg)): ?>
                    <div class="alert alert-error"><span><?= e($errorMsg) ?></span></div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm">👥</div>
                        <div>
                            <span class="stat-label-sm"><?= e('users') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['users'] ?? 0) ?></strong>
                        </div>
                    </div>
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm">🚗</div>
                        <div>
                            <span class="stat-label-sm"><?= e('cars') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['cars'] ?? 0) ?></strong>
                        </div>
                    </div>
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm">📄</div>
                        <div>
                            <span class="stat-label-sm"><?= e('rentals') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['rentals'] ?? 0) ?></strong>
                        </div>
                    </div>
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm">💰</div>
                        <div>
                            <span class="stat-label-sm"><?= e('db_size') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['db_size'] ?? 0, 2) ?> MB</strong>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="tabs">
                    <a href="?tab=office" class="tab <?= $activeTab === 'office' ? 'active' : '' ?>">
                        🏢 <?= e('office_info') ?>
                    </a>
                    <a href="?tab=notifications" class="tab <?= $activeTab === 'notifications' ? 'active' : '' ?>">
                        🔔 <?= e('notifications') ?>
                    </a>
                    <a href="?tab=tax" class="tab <?= $activeTab === 'tax' ? 'active' : '' ?>">
                        💰 <?= e('tax_settings') ?>
                    </a>
                    <a href="?tab=terms" class="tab <?= $activeTab === 'terms' ? 'active' : '' ?>">
                        📜 <?= e('contract_terms') ?>
                    </a>
                    <a href="?tab=backup" class="tab <?= $activeTab === 'backup' ? 'active' : '' ?>">
                        💾 <?= e('backup') ?>
                    </a>
                    <a href="?tab=log" class="tab <?= $activeTab === 'log' ? 'active' : '' ?>">
                        📋 <?= e('activity_log') ?>
                    </a>
                </div>
                
                <!-- ============================================ -->
                <!-- 1. Office Info -->
                <!-- ============================================ -->
                <?php if ($activeTab === 'office'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>🏢 <?= e('office_info') ?></h3>
                            <span class="card-subtitle"><?= e('office_info_desc') ?> (تظهر في الطباعة)</span>
                        </div>
                        <form method="POST" action="<?= url('/settings/office') ?>">
                            <?= Csrf::field() ?>
                            
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="office_name"><?= e('office_name_ar') ?></label>
                                        <input type="text" id="office_name" name="office_name" 
                                               value="<?= e($office['office_name'] ?? '') ?>"
                                               maxlength="100" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="office_name_en"><?= e('office_name_en') ?></label>
                                        <input type="text" id="office_name_en" name="office_name_en" 
                                               value="<?= e($office['office_name_en'] ?? '') ?>"
                                               maxlength="100">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="office_phone"><?= e('office_phone') ?></label>
                                        <input type="text" id="office_phone" name="office_phone" 
                                               value="<?= e($office['office_phone'] ?? '') ?>"
                                               maxlength="30">
                                    </div>
                                    <div class="form-group">
                                        <label for="office_email"><?= e('office_email') ?></label>
                                        <input type="email" id="office_email" name="office_email" 
                                               value="<?= e($office['office_email'] ?? '') ?>"
                                               maxlength="100">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group" style="grid-column: span 2;">
                                        <label for="office_address"><?= e('office_address') ?></label>
                                        <input type="text" id="office_address" name="office_address" 
                                               value="<?= e($office['office_address'] ?? '') ?>"
                                               maxlength="255">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="tax_number"><?= e('tax_number') ?></label>
                                        <input type="text" id="tax_number" name="tax_number" 
                                               value="<?= e($office['tax_number'] ?? '') ?>"
                                               maxlength="50">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    💾 <?= e('save') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- ============================================ -->
                <!-- 2. Notifications -->
                <!-- ============================================ -->
                <?php if ($activeTab === 'notifications'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>🔔 <?= e('notification_settings') ?></h3>
                            <span class="card-subtitle"><?= e('notification_settings_desc') ?></span>
                        </div>
                        <form method="POST" action="<?= url('/settings/notifications') ?>">
                            <?= Csrf::field() ?>
                            
                            <div class="card-body">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="notification_days_before_payment">
                                            <?= e('days_before_payment') ?>
                                        </label>
                                        <input type="number" 
                                               id="notification_days_before_payment" 
                                               name="notification_days_before_payment" 
                                               value="<?= (int)($notifications['notification_days_before_payment'] ?? 3) ?>"
                                               min="1" max="30">
                                        <small class="hint"><?= e('days_before_payment_hint') ?></small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="notification_days_before_doc_expiry">
                                            <?= e('days_before_doc_expiry') ?>
                                        </label>
                                        <input type="number" 
                                               id="notification_days_before_doc_expiry" 
                                               name="notification_days_before_doc_expiry" 
                                               value="<?= (int)($notifications['notification_days_before_doc_expiry'] ?? 30) ?>"
                                               min="1" max="90">
                                        <small class="hint"><?= e('days_before_doc_expiry_hint') ?></small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="notification_days_before_rental_end">
                                            <?= e('days_before_rental_end') ?>
                                        </label>
                                        <input type="number" 
                                               id="notification_days_before_rental_end" 
                                               name="notification_days_before_rental_end" 
                                               value="<?= (int)($notifications['notification_days_before_rental_end'] ?? 1) ?>"
                                               min="1" max="7">
                                        <small class="hint"><?= e('days_before_rental_end_hint') ?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    💾 <?= e('save') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- ============================================ -->
                <!-- 3. Tax Settings -->
                <!-- ============================================ -->
                <?php if ($activeTab === 'tax'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>💰 <?= e('tax_settings') ?></h3>
                            <span class="card-subtitle"><?= e('tax_settings_desc') ?></span>
                        </div>
                        <form method="POST" action="<?= url('/settings/tax') ?>">
                            <?= Csrf::field() ?>
                            
                            <div class="card-body">
                                <div class="form-group">
                                    <label class="checkbox-wrapper">
                                        <input type="checkbox" 
                                               id="vat_enabled" 
                                               name="vat_enabled" 
                                               value="1"
                                               <?= !empty($tax['vat_enabled']) ? 'checked' : '' ?>>
                                        <span class="checkbox-custom"></span>
                                        <span><?= e('enable_vat') ?></span>
                                    </label>
                                    <small class="hint"><?= e('enable_vat_hint') ?></small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="vat_rate"><?= e('vat_rate') ?> (%)</label>
                                    <input type="number" 
                                           id="vat_rate" 
                                           name="vat_rate" 
                                           value="<?= number_format((float)($tax['vat_rate'] ?? 15), 2, '.', '') ?>"
                                           step="0.01" 
                                           min="0" 
                                           max="100">
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    💾 <?= e('save') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- ============================================ -->
                <!-- 4. Contract Terms -->
                <!-- ============================================ -->
                <?php if ($activeTab === 'terms'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>📜 <?= e('contract_terms') ?></h3>
                            <span class="card-subtitle"><?= e('contract_terms_desc') ?> (تظهر في الطباعة للعميل)</span>
                        </div>
                        <form method="POST" action="<?= url('/settings/terms') ?>">
                            <?= Csrf::field() ?>
                            
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="contract_terms_ar"><?= e('contract_terms_ar') ?></label>
                                    <textarea id="contract_terms_ar" 
                                              name="contract_terms_ar" 
                                              rows="12"
                                              maxlength="5000"
                                              placeholder="أدخل الشروط والأحكام هنا لتظهر في العقد المطبوع..."><?= e($terms['contract_terms_ar'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="contract_terms_en"><?= e('contract_terms_en') ?></label>
                                    <textarea id="contract_terms_en" 
                                              name="contract_terms_en" 
                                              rows="8"
                                              maxlength="5000"><?= e($terms['contract_terms_en'] ?? '') ?></textarea>
                                </div>
                            </div>
                            
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    💾 <?= e('save') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
                <!-- ============================================ -->
                <!-- 5. Backup -->
                <!-- ============================================ -->
                <?php if ($activeTab === 'backup'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>💾 <?= e('backup') ?></h3>
                            <span class="card-subtitle"><?= e('backup_desc') ?></span>
                        </div>
                        <div class="card-body">
                            <div class="info-box">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span><?= e('backup_hint') ?></span>
                            </div>
                            
                            <div class="backup-actions">
                                <form method="POST" action="<?= url('/settings/backup') ?>" style="display:inline-block;">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        💾 <?= e('create_backup_now') ?>
                                    </button>
                                </form>
                            </div>
                            
                            <h4 style="margin-top:32px;"><?= e('backup_files') ?></h4>
                            <div class="backup-list">
                                <?php
                                $backupsDir = STORAGE_PATH . '/backups';
                                $backups = [];
                                if (is_dir($backupsDir)) {
                                    $files = glob($backupsDir . '/backup_*.sql');
                                    foreach ($files as $file) {
                                        $backups[] = [
                                            'name' => basename($file),
                                            'size' => filesize($file),
                                            'date' => filemtime($file),
                                        ];
                                    }
                                    usort($backups, fn($a, $b) => $b['date'] - $a['date']);
                                }
                                ?>
                                
                                <?php if (empty($backups)): ?>
                                    <p class="empty-state"><?= e('no_backups') ?></p>
                                <?php else: ?>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th><?= e('filename') ?></th>
                                                <th><?= e('size') ?></th>
                                                <th><?= e('date') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($backups as $b): ?>
                                                <tr>
                                                    <td><code><?= e($b['name']) ?></code></td>
                                                    <td><?= number_format($b['size'] / 1024, 2) ?> KB</td>
                                                    <td><?= date('Y-m-d H:i', $b['date']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- ============================================ -->
                <!-- 6. Activity Log -->
                <!-- ============================================ -->
                <?php if ($activeTab === 'log'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>📋 <?= e('activity_log') ?></h3>
                            <span class="card-subtitle"><?= e('activity_log_desc') ?></span>
                        </div>
                        <div class="card-body no-padding">
                            <?php if (empty($activityLog)): ?>
                                <p class="empty-state"><?= e('no_activity') ?></p>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th><?= e('user') ?></th>
                                                <th><?= e('action') ?></th>
                                                <th><?= e('table') ?></th>
                                                <th><?= e('date') ?></th>
                                                <th>IP</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($activityLog as $log): ?>
                                                <tr>
                                                    <td><?= e($log['user_name'] ?? '—') ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= e(status_badge($log['action'] ?? '')) ?>">
                                                            <?= e($log['action'] ?? '') ?>
                                                        </span>
                                                    </td>
                                                    <td><?= e($log['table_name'] ?? '') ?></td>
                                                    <td><?= e(format_datetime($log['created_at'] ?? '')) ?></td>
                                                    <td><small><?= e($log['ip_address'] ?? '') ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer">
                            <form method="POST" action="<?= url('/settings/clear-log') ?>" style="display:inline-block;"
                                  onsubmit="return confirm('<?= e('confirm_clear_log') ?>');">
                                <?= Csrf::field() ?>
                                <div style="display:flex; gap:10px; align-items:center;">
                                    <label><?= e('delete_logs_older_than') ?>:</label>
                                    <input type="number" name="days" value="30" min="1" max="365" style="width:80px;">
                                    <span><?= e('days') ?></span>
                                    <button type="submit" class="btn btn-danger">
                                        🗑️ <?= e('clear_old_logs') ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
            </main>
            
            <?php require BASE_PATH . '/app/views/layout/footer.php'; ?>
            
        </div>
    </div>
    
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= url('') ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
        };
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>