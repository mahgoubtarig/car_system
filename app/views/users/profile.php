<?php
/**
 * الملف الشخصي للمستخدم
 */
if (empty($userData) || !is_array($userData)) {
    header('Location: ' . url('/dashboard'));
    exit;
}

$userId = (int) $userData['id'];
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('my_profile') ?> | <?= e('app_name') ?></title>
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
                        <h2 class="page-title">👤 <?= e('my_profile') ?></h2>
                        <p class="page-subtitle"><?= e('profile_subtitle') ?></p>
                    </div>
                </div>
                
                <?php $successMsg = Auth::getFlash('success'); ?>
                <?php if (!empty($successMsg) && is_string($successMsg)): ?>
                    <div class="alert alert-success"><span><?= e($successMsg) ?></span></div>
                <?php endif; ?>
                
                <?php $errorMsg = Auth::getFlash('error'); ?>
                <?php if (!empty($errorMsg) && is_string($errorMsg)): ?>
                    <div class="alert alert-error"><span><?= e($errorMsg) ?></span></div>
                <?php endif; ?>
                
                <!-- User Header -->
                <div class="user-detail-header">
                    <div class="user-avatar-xl user-avatar-<?= e($userData['role']) ?>">
                        <?= e(initials($userData['full_name'], 2)) ?>
                    </div>
                    <div class="user-detail-info">
                        <div class="user-title-row">
                            <h1><?= e($userData['full_name']) ?></h1>
                            <span class="badge badge-<?= e(role_badge($userData['role'])) ?>">
                                <?= e($roles[$userData['role']] ?? $userData['role']) ?>
                            </span>
                        </div>
                        <p class="user-subtitle">
                            <code>@<?= e($userData['username']) ?></code>
                        </p>
                    </div>
                </div>
                
                <div class="grid-2">
                    
                    <!-- معلومات الحساب -->
                    <div class="card">
                        <div class="card-header"><h3>👤 <?= e('account_info') ?></h3></div>
                        <form method="POST" action="<?= url('/profile/update') ?>">
                            <?= Csrf::field() ?>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="full_name"><?= e('full_name') ?></label>
                                    <input type="text" id="full_name" name="full_name" 
                                           value="<?= e($userData['full_name']) ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="phone"><?= e('phone') ?></label>
                                    <input type="tel" id="phone" name="phone" 
                                           value="<?= e($userData['phone'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="email"><?= e('email') ?></label>
                                    <input type="email" id="email" name="email" 
                                           value="<?= e($userData['email'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label><?= e('username') ?></label>
                                    <input type="text" value="<?= e($userData['username']) ?>" disabled>
                                    <small class="hint"><?= e('username_locked') ?></small>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">💾 <?= e('save_changes') ?></button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- تغيير كلمة المرور -->
                    <div class="card">
                        <div class="card-header"><h3>🔐 <?= e('change_password') ?></h3></div>
                        <form method="POST" action="<?= url('/profile/change-password') ?>">
                            <?= Csrf::field() ?>
                            <div class="card-body">
                                <div class="form-group">
                                    <label for="current_password"><?= e('current_password') ?></label>
                                    <input type="password" id="current_password" name="current_password" required>
                                </div>
                                <div class="form-group">
                                    <label for="new_password"><?= e('new_password') ?></label>
                                    <input type="password" id="new_password" name="new_password" minlength="6" required>
                                </div>
                                <div class="form-group">
                                    <label for="confirm_password"><?= e('confirm_password') ?></label>
                                    <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary">🔑 <?= e('change_password') ?></button>
                            </div>
                        </form>
                    </div>
                    
                </div>
                
                <!-- Permissions -->
                <div class="card">
                    <div class="card-header">
                        <h3>🔐 <?= e('my_permissions') ?></h3>
                        <span class="card-subtitle"><?= count($permissions) ?> <?= e('permissions') ?></span>
                    </div>
                    <div class="card-body">
                        <ul class="permissions-list">
                            <?php foreach ($permissions as $perm): ?>
                                <li>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    <span><?= e($perm) ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
            </main>
            <?php require BASE_PATH . '/app/views/layout/footer.php'; ?>
        </div>
    </div>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>