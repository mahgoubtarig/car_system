<?php
/**
 * تفاصيل المستخدم
 */
if (empty($userData) || !is_array($userData)) {
    header('Location: ' . url('/users'));
    exit;
}

$userId = (int) $userData['id'];
$isOwnProfile = $userId === Auth::id();
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($userData['full_name']) ?> | <?= e('app_name') ?></title>
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
                
                <div class="breadcrumb">
                    <a href="<?= url('/users') ?>"><?= e('users') ?></a>
                    <span>›</span>
                    <span><?= e($userData['full_name']) ?></span>
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
                            <?php if ($userData['is_active']): ?>
                                <span class="badge badge-success">✅ <?= e('active') ?></span>
                            <?php else: ?>
                                <span class="badge badge-danger">🚫 <?= e('inactive') ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="user-subtitle">
                            <code>@<?= e($userData['username']) ?></code>
                            <?php if (!empty($userData['email'])): ?>
                                <span>•</span>
                                <span><?= e($userData['email']) ?></span>
                            <?php endif; ?>
                        </p>
                        
                        <div class="user-quick-info">
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('phone') ?></span>
                                <strong><?= e($userData['phone'] ?? '—') ?></strong>
                            </div>
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('last_login') ?></span>
                                <strong><?= !empty($userData['last_login']) ? e(format_datetime($userData['last_login'])) : e('never') ?></strong>
                            </div>
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('created_at') ?></span>
                                <strong><?= e(format_date($userData['created_at'] ?? '')) ?></strong>
                            </div>
                        </div>
                        
                        <div class="car-actions">
                            <a href="<?= url('/users/' . $userId . '/edit') ?>" class="btn btn-primary">✏️ <?= e('edit') ?></a>
                            
                            <?php if (!$isOwnProfile): ?>
                                <form method="POST" action="<?= url('/users/' . $userId . '/toggle-active') ?>" style="display:inline-block;">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-<?= $userData['is_active'] ? 'warning' : 'success' ?>">
                                        <?= $userData['is_active'] ? '🚫 ' . e('deactivate') : '✅ ' . e('activate') ?>
                                    </button>
                                </form>
                                
                                <button type="button" class="btn btn-secondary" onclick="showResetModal()">
                                    🔑 <?= e('reset_password') ?>
                                </button>
                                
                                <button type="button" class="btn btn-danger" onclick="showDeleteModal()">
                                    🗑️ <?= e('delete') ?>
                                </button>
                            <?php endif; ?>
                            
                            <a href="<?= url('/users') ?>" class="btn btn-secondary">← <?= e('back') ?></a>
                        </div>
                    </div>
                </div>
                
                <!-- Details Grid -->
                <div class="grid-2">
                    
                    <!-- معلومات الحساب -->
                    <div class="card">
                        <div class="card-header">
                            <h3>👤 <?= e('account_info') ?></h3>
                        </div>
                        <div class="card-body">
                            <table class="info-table">
                                <tr><th><?= e('full_name') ?></th><td><?= e($userData['full_name']) ?></td></tr>
                                <tr><th><?= e('username') ?></th><td><code><?= e($userData['username']) ?></code></td></tr>
                                <tr><th><?= e('role') ?></th><td><span class="badge badge-<?= e(role_badge($userData['role'])) ?>"><?= e($roles[$userData['role']] ?? $userData['role']) ?></span></td></tr>
                                <tr><th><?= e('phone') ?></th><td><?= e($userData['phone'] ?? '—') ?></td></tr>
                                <tr><th><?= e('email') ?></th><td><?= e($userData['email'] ?? '—') ?></td></tr>
                                <tr><th><?= e('status') ?></th><td><?= $userData['is_active'] ? '✅ ' . e('active') : '🚫 ' . e('inactive') ?></td></tr>
                                <tr><th><?= e('last_login') ?></th><td><?= !empty($userData['last_login']) ? e(format_datetime($userData['last_login'])) : e('never') ?></td></tr>
                                <tr><th><?= e('created_at') ?></th><td><?= e(format_datetime($userData['created_at'] ?? '')) ?></td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- الصلاحيات -->
                    <div class="card">
                        <div class="card-header">
                            <h3>🔐 <?= e('permissions') ?></h3>
                            <span class="card-subtitle"><?= count($permissions) ?> <?= e('permissions') ?></span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($permissions)): ?>
                                <p class="empty-state"><?= e('no_permissions') ?></p>
                            <?php else: ?>
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
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Recent Activity -->
                <div class="card">
                    <div class="card-header">
                        <h3>📋 <?= e('recent_activity') ?></h3>
                        <span class="card-subtitle"><?= count($recentActivity) ?> <?= e('records') ?></span>
                    </div>
                    <div class="card-body no-padding">
                        <?php if (empty($recentActivity)): ?>
                            <p class="empty-state"><?= e('no_activity') ?></p>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?= e('action') ?></th>
                                            <th><?= e('table') ?></th>
                                            <th>IP</th>
                                            <th><?= e('date') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentActivity as $log): ?>
                                            <tr>
                                                <td><span class="badge badge-info"><?= e($log['action'] ?? '') ?></span></td>
                                                <td><?= e($log['table_name'] ?? '') ?></td>
                                                <td><small><?= e($log['ip_address'] ?? '') ?></small></td>
                                                <td><?= e(format_datetime($log['created_at'] ?? '')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </main>
            <?php require BASE_PATH . '/app/views/layout/footer.php'; ?>
        </div>
    </div>
    
    <!-- Modal: Reset Password -->
    <div class="modal-overlay" id="resetModal" style="display:none;">
        <div class="modal modal-sm">
            <form method="POST" action="<?= url('/users/' . $userId . '/reset-password') ?>">
                <?= Csrf::field() ?>
                <div class="modal-header">
                    <h3>🔑 <?= e('reset_password') ?></h3>
                    <button type="button" class="modal-close" onclick="hideResetModal()">×</button>
                </div>
                <div class="modal-body">
                    <p><?= e('reset_password_msg') ?></p>
                    <div class="form-group">
                        <label for="new_password"><?= e('new_password') ?></label>
                        <input type="text" id="new_password" name="new_password" 
                               minlength="6" required
                               value="<?= e(bin2hex(random_bytes(4))) ?>">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideResetModal()"><?= e('cancel') ?></button>
                    <button type="submit" class="btn btn-primary">🔑 <?= e('confirm') ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal: Delete -->
    <?php if (!$isOwnProfile): ?>
        <div class="modal-overlay" id="deleteModal" style="display:none;">
            <div class="modal modal-sm">
                <form method="POST" action="<?= url('/users/' . $userId . '/delete') ?>">
                    <?= Csrf::field() ?>
                    <div class="modal-header">
                        <h3 class="text-danger">🗑️ <?= e('confirm_delete') ?></h3>
                        <button type="button" class="modal-close" onclick="hideDeleteModal()">×</button>
                    </div>
                    <div class="modal-body">
                        <p><?= e('confirm_delete_user') ?></p>
                        <p class="delete-customer-name"><strong><?= e($userData['full_name']) ?></strong></p>
                        <p class="text-danger"><small>⚠️ <?= e('delete_warning') ?></small></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="hideDeleteModal()"><?= e('cancel') ?></button>
                        <button type="submit" class="btn btn-danger">🗑️ <?= e('delete') ?></button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
        function showResetModal() { document.getElementById('resetModal').style.display = 'flex'; }
        function hideResetModal() { document.getElementById('resetModal').style.display = 'none'; }
        function showDeleteModal() { document.getElementById('deleteModal').style.display = 'flex'; }
        function hideDeleteModal() { document.getElementById('deleteModal').style.display = 'none'; }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') { hideResetModal(); hideDeleteModal(); }
        });
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>