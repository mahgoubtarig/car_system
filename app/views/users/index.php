<?php
/**
 * قائمة المستخدمين
 */
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('users') ?> | <?= e('app_name') ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👥</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/rentals.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/users.css') ?>">
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
                
                <div class="page-header">
                    <div>
                        <h2 class="page-title">👥 <?= e('users') ?></h2>
                        <p class="page-subtitle"><?= e('users_subtitle') ?></p>
                    </div>
                    <div class="page-actions">
                        <a href="<?= url('/users/create') ?>" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <?= e('add_user') ?>
                        </a>
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
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm">👥</div>
                        <div>
                            <span class="stat-label-sm"><?= e('total_users') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['total']) ?></strong>
                        </div>
                    </div>
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--danger-light);">👑</div>
                        <div>
                            <span class="stat-label-sm"><?= e('admins') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['admins']) ?></strong>
                        </div>
                    </div>
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--info-color);color:white;">💰</div>
                        <div>
                            <span class="stat-label-sm"><?= e('accountants') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['accountants']) ?></strong>
                        </div>
                    </div>
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--success-light);">✅</div>
                        <div>
                            <span class="stat-label-sm"><?= e('active') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['active']) ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card filters-card">
                    <form method="GET" action="<?= url('/users') ?>" class="filters-form">
                        <div class="filter-group">
                            <label><?= e('search') ?></label>
                            <input type="text" name="search" value="<?= e($filters['search']) ?>" 
                                   placeholder="<?= e('search_users_placeholder') ?>">
                        </div>
                        <div class="filter-group">
                            <label><?= e('role') ?></label>
                            <select name="role">
                                <option value=""><?= e('all') ?></option>
                                <?php foreach ($roles as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= $filters['role'] === $key ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><?= e('status') ?></label>
                            <select name="is_active">
                                <option value=""><?= e('all') ?></option>
                                <option value="1" <?= $filters['is_active'] === '1' ? 'selected' : '' ?>><?= e('active') ?></option>
                                <option value="0" <?= $filters['is_active'] === '0' ? 'selected' : '' ?>><?= e('inactive') ?></option>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">🔍 <?= e('search') ?></button>
                            <a href="<?= url('/users') ?>" class="btn btn-secondary"><?= e('reset') ?></a>
                        </div>
                    </form>
                </div>
                
                <!-- Table -->
                <div class="card">
                    <div class="card-body no-padding">
                        <?php if (empty($users)): ?>
                            <div class="empty-state-large">
                                <div class="empty-icon">👥</div>
                                <h3><?= e('no_users') ?></h3>
                                <a href="<?= url('/users/create') ?>" class="btn btn-primary"><?= e('add_first_user') ?></a>
                            </div>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?= e('user') ?></th>
                                            <th><?= e('username') ?></th>
                                            <th><?= e('role') ?></th>
                                            <th><?= e('phone') ?></th>
                                            <th><?= e('last_login') ?></th>
                                            <th><?= e('status') ?></th>
                                            <th><?= e('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($users as $u): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-cell">
                                                        <div class="user-avatar-sm user-avatar-<?= e($u['role']) ?>">
                                                            <?= e(initials($u['full_name'])) ?>
                                                        </div>
                                                        <div>
                                                            <strong><?= e($u['full_name']) ?></strong>
                                                            <small><?= e($u['email'] ?? '') ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><code><?= e($u['username']) ?></code></td>
                                                <td>
                                                    <span class="badge badge-<?= e(role_badge($u['role'])) ?>">
                                                        <?= e($roles[$u['role']] ?? $u['role']) ?>
                                                    </span>
                                                </td>
                                                <td><?= e($u['phone'] ?? '—') ?></td>
                                                <td>
                                                    <?php if (!empty($u['last_login'])): ?>
                                                        <small><?= e(format_datetime($u['last_login'])) ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted"><?= e('never') ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($u['is_active']): ?>
                                                        <span class="badge badge-success">✅ <?= e('active') ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger">🚫 <?= e('inactive') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="actions">
                                                        <a href="<?= url('/users/' . (int)$u['id']) ?>" class="btn-icon" title="<?= e('view') ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                                <circle cx="12" cy="12" r="3"></circle>
                                                            </svg>
                                                        </a>
                                                        <a href="<?= url('/users/' . (int)$u['id'] . '/edit') ?>" class="btn-icon" title="<?= e('edit') ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </td>
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
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>