<?php
/**
 * تعديل مستخدم
 */
if (empty($userData) || !is_array($userData)) {
    header('Location: ' . url('/users'));
    exit;
}

$userId = (int) $userData['id'];
$formData = array_merge($userData, is_array($old ?? null) ? $old : []);
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('edit_user') ?> | <?= e('app_name') ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>✏️</text></svg>">
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
                        <a href="<?= url('/users/' . $userId) ?>" class="back-link">← <?= e('back_to_user') ?></a>
                        <h2 class="page-title">✏️ <?= e('edit_user') ?></h2>
                        <p class="page-subtitle"><?= e($userData['full_name']) ?></p>
                    </div>
                </div>
                
                <?php if (!empty($errors['general']) && is_string($errors['general'])): ?>
                    <div class="alert alert-error"><span><?= e($errors['general']) ?></span></div>
                <?php endif; ?>
                
                <form method="POST" action="<?= url('/users/' . $userId . '/edit') ?>" class="user-form">
                    <?= Csrf::field() ?>
                    
                    <div class="form-sections">
                        
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
                                           maxlength="100" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone"><?= e('phone') ?></label>
                                    <input type="tel" id="phone" name="phone" 
                                           value="<?= e($formData['phone'] ?? '') ?>"
                                           maxlength="20">
                                </div>
                                <div class="form-group">
                                    <label for="email"><?= e('email') ?></label>
                                    <input type="email" id="email" name="email" 
                                           value="<?= e($formData['email'] ?? '') ?>"
                                           maxlength="100">
                                </div>
                            </div>
                        </div>
                        
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
                                           maxlength="50" required>
                                </div>
                            </div>
                            
                            <div class="info-box">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span><?= e('password_change_hint') ?></span>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="password"><?= e('new_password') ?></label>
                                    <input type="password" id="password" name="password" 
                                           minlength="6"
                                           autocomplete="new-password">
                                    <small class="hint"><?= e('leave_empty_to_keep') ?></small>
                                </div>
                                <div class="form-group">
                                    <label for="password_confirm"><?= e('confirm_password') ?></label>
                                    <input type="password" id="password_confirm" name="password_confirm" 
                                           minlength="6"
                                           autocomplete="new-password">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">3</span>
                                <?= e('role_and_permissions') ?>
                            </h3>
                            
                            <div class="role-selection">
                                <?php 
                                $roleData = [
                                    'admin' => ['icon' => '👑', 'title' => 'مدير', 'desc' => 'صلاحيات كاملة', 'color' => 'danger'],
                                    'accountant' => ['icon' => '💰', 'title' => 'محاسب', 'desc' => 'إدارة مالية', 'color' => 'info'],
                                    'employee' => ['icon' => '🧑‍💼', 'title' => 'موظف', 'desc' => 'إدارة تشغيلية', 'color' => 'success'],
                                ];
                                ?>
                                <?php foreach ($roleData as $roleKey => $role): ?>
                                    <label class="role-card role-<?= $role['color'] ?>">
                                        <input type="radio" 
                                               name="role" 
                                               value="<?= $roleKey ?>"
                                               <?= ($formData['role'] ?? '') === $roleKey ? 'checked' : '' ?>>
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
                        </div>
                        
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">4</span>
                                <?= e('status') ?>
                            </h3>
                            
                            <div class="form-group">
                                <label class="checkbox-wrapper">
                                    <input type="checkbox" 
                                           name="is_active" 
                                           value="1"
                                           <?= !empty($formData['is_active']) ? 'checked' : '' ?>>
                                    <span class="checkbox-custom"></span>
                                    <span><?= e('active_account') ?></span>
                                </label>
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="form-actions">
                        <a href="<?= url('/users/' . $userId) ?>" class="btn btn-secondary"><?= e('cancel') ?></a>
                        <button type="submit" class="btn btn-primary btn-lg">💾 <?= e('save_changes') ?></button>
                    </div>
                </form>
                
            </main>
            <?php require BASE_PATH . '/app/views/layout/footer.php'; ?>
        </div>
    </div>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>