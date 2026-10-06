<?php
/**
 * إضافة مستخدم جديد
 */
$formData = is_array($old ?? null) ? $old : [];
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('add_user') ?> | <?= e('app_name') ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👤</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/rentals.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/users.css') ?>">
</head>
<body class="dashboard-page">
    <div class="app-layout">
        <?php require BASE_PATH . '/app/views/layout/sidebar.php'; ?>
        <div class="main-wrapper">
            <?php require BASE_PATH . '/app/views/layout/header.php'; ?>
            <main class="main-content">
                
                <div class="page-header">
                    <div>
                        <a href="<?= url('/users') ?>" class="back-link">← <?= e('back_to_users') ?></a>
                        <h2 class="page-title">👤 <?= e('add_user') ?></h2>
                        <p class="page-subtitle"><?= e('add_user_subtitle') ?></p>
                    </div>
                </div>
                
                <?php if (!empty($errors['general']) && is_string($errors['general'])): ?>
                    <div class="alert alert-error"><span><?= e($errors['general']) ?></span></div>
                <?php endif; ?>
                
                <form method="POST" action="<?= url('/users') ?>" class="user-form">
                    <?= Csrf::field() ?>
                    
                    <div class="form-sections">
                        
                        <!-- 1. المعلومات الأساسية -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">1</span>
                                <?= e('basic_info') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full_name"><?= e('full_name') ?> <span class="required">*</span></label>
                                    <input type="text" id="full_name" name="full_name" 
                                           value="<?= e($formData['full_name'] ?? '') ?>"
                                           maxlength="100" required autofocus
                                           class="<?= !empty($errors['full_name']) ? 'error' : '' ?>">
                                    <?php if (!empty($errors['full_name'])): ?>
                                        <span class="form-error"><?= e($errors['full_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone"><?= e('phone') ?></label>
                                    <input type="tel" id="phone" name="phone" 
                                           value="<?= e($formData['phone'] ?? '') ?>"
                                           maxlength="20" placeholder="05xxxxxxxx">
                                </div>
                                <div class="form-group">
                                    <label for="email"><?= e('email') ?></label>
                                    <input type="email" id="email" name="email" 
                                           value="<?= e($formData['email'] ?? '') ?>"
                                           maxlength="100"
                                           class="<?= !empty($errors['email']) ? 'error' : '' ?>">
                                    <?php if (!empty($errors['email'])): ?>
                                        <span class="form-error"><?= e($errors['email']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 2. بيانات الدخول -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">2</span>
                                <?= e('login_credentials') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username"><?= e('username') ?> <span class="required">*</span></label>
                                    <input type="text" id="username" name="username" 
                                           value="<?= e($formData['username'] ?? '') ?>"
                                           maxlength="50" required
                                           pattern="[a-zA-Z0-9_.]+"
                                           autocomplete="off"
                                           class="<?= !empty($errors['username']) ? 'error' : '' ?>">
                                    <small class="hint"><?= e('username_hint') ?></small>
                                    <?php if (!empty($errors['username'])): ?>
                                        <span class="form-error"><?= e($errors['username']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="password"><?= e('password') ?> <span class="required">*</span></label>
                                    <div class="password-wrapper">
                                        <input type="password" id="password" name="password" 
                                               minlength="6" required
                                               autocomplete="new-password"
                                               class="<?= !empty($errors['password']) ? 'error' : '' ?>">
                                        <button type="button" class="toggle-password" onclick="togglePassword('password', this)">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                    <small class="hint"><?= e('password_hint') ?></small>
                                    <?php if (!empty($errors['password'])): ?>
                                        <span class="form-error"><?= e($errors['password']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label for="password_confirm"><?= e('confirm_password') ?> <span class="required">*</span></label>
                                    <input type="password" id="password_confirm" name="password_confirm" 
                                           minlength="6" required
                                           autocomplete="new-password"
                                           class="<?= !empty($errors['password_confirm']) ? 'error' : '' ?>">
                                    <?php if (!empty($errors['password_confirm'])): ?>
                                        <span class="form-error"><?= e($errors['password_confirm']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 3. الدور والصلاحيات -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">3</span>
                                <?= e('role_and_permissions') ?>
                            </h3>
                            
                            <div class="role-selection">
                                <?php 
                                $roleData = [
                                    'admin' => [
                                        'icon' => '👑',
                                        'title' => 'مدير',
                                        'desc' => 'صلاحيات كاملة على النظام',
                                        'color' => 'danger',
                                    ],
                                    'accountant' => [
                                        'icon' => '💰',
                                        'title' => 'محاسب',
                                        'desc' => 'إدارة مالية وتقارير',
                                        'color' => 'info',
                                    ],
                                    'employee' => [
                                        'icon' => '🧑‍💼',
                                        'title' => 'موظف',
                                        'desc' => 'إدارة السيارات والعقود',
                                        'color' => 'success',
                                    ],
                                ];
                                ?>
                                <?php foreach ($roleData as $roleKey => $role): ?>
                                    <label class="role-card role-<?= $role['color'] ?>">
                                        <input type="radio" 
                                               name="role" 
                                               value="<?= $roleKey ?>"
                                               <?= ($formData['role'] ?? 'employee') === $roleKey ? 'checked' : '' ?>>
                                        <div class="role-card-content">
                                            <div class="role-icon"><?= $role['icon'] ?></div>
                                            <div class="role-info">
                                                <strong><?= $role['title'] ?></strong>
                                                <span><?= $role['desc'] ?></span>
                                            </div>
                                            <div class="role-check">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                </svg>
                                            </div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            
                            <?php if (!empty($errors['role'])): ?>
                                <span class="form-error"><?= e($errors['role']) ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- 4. الحالة -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">4</span>
                                <?= e('status') ?>
                            </h3>
                            
                            <div class="form-group">
                                <label class="checkbox-wrapper">
                                    <input type="checkbox" 
                                           id="is_active" 
                                           name="is_active" 
                                           value="1"
                                           <?= !isset($formData['is_active']) || !empty($formData['is_active']) ? 'checked' : '' ?>>
                                    <span class="checkbox-custom"></span>
                                    <span><?= e('active_account') ?></span>
                                </label>
                                <small class="hint"><?= e('active_account_hint') ?></small>
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="form-actions">
                        <a href="<?= url('/users') ?>" class="btn btn-secondary"><?= e('cancel') ?></a>
                        <button type="submit" class="btn btn-primary btn-lg">💾 <?= e('save_user') ?></button>
                    </div>
                </form>
                
            </main>
            <?php require BASE_PATH . '/app/views/layout/footer.php'; ?>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId, btn) {
            const field = document.getElementById(fieldId);
            const type = field.type === 'password' ? 'text' : 'password';
            field.type = type;
            btn.style.color = type === 'text' ? 'var(--primary-color)' : '';
        }
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>