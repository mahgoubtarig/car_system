<?php
/**
 * قائمة العقود
 */
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('rentals') ?> | <?= e('app_name') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📝</text></svg>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/cars.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/rentals.css') ?>">
    
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
                        <h2 class="page-title"><?= e('rentals') ?></h2>
                        <p class="page-subtitle"><?= e('rentals_subtitle') ?></p>
                    </div>
                    <div class="page-actions" style="display: flex; gap: 10px; align-items: center;">
                        <!-- ✅ زر الأرشيف الجديد -->
                        <a href="<?= url('/rentals/archive') ?>" class="btn btn-secondary" title="سجل العقود المنتهية والملغاة">
                            🗄️ سجل الأرشيف
                        </a>
                        
                        <a href="<?= url('/rentals/create') ?>" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <?= e('create_rental') ?>
                        </a>
                    </div>
                </div>
                
                <!-- Flash -->
                <?php $successMsg = Auth::getFlash('success'); ?>
                <?php if (!empty($successMsg) && is_string($successMsg)): ?>
                    <div class="alert alert-success">
                        <span><?= e($successMsg) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php $errorMsg = Auth::getFlash('error'); ?>
                <?php if (!empty($errorMsg) && is_string($errorMsg)): ?>
                    <div class="alert alert-error">
                        <span><?= e($errorMsg) ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="stats-grid stats-grid-compact">
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm">📝</div>
                        <div>
                            <span class="stat-label-sm"><?= e('total_rentals') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['total'] ?? 0)) ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--success-light);">🟢</div>
                        <div>
                            <span class="stat-label-sm"><?= e('active') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['active'] ?? 0)) ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--info-light);">📅</div>
                        <div>
                            <span class="stat-label-sm"><?= e('ending_today') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['ending_today'] ?? 0)) ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--danger-light);">⚠️</div>
                        <div>
                            <span class="stat-label-sm"><?= e('overdue') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['overdue'] ?? 0)) ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card filters-card">
                    <form method="GET" action="<?= url('/rentals') ?>" class="filters-form">
                        <div class="filter-group">
                            <label><?= e('search') ?></label>
                            <input type="text" name="search" 
                                   value="<?= e($filters['search'] ?? '') ?>" 
                                   placeholder="<?= e('search_rentals_placeholder') ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label><?= e('status') ?></label>
                            <select name="status">
                                <option value=""><?= e('all') ?></option>
                                <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>
                                    <?= e('active') ?>
                                </option>
                                <option value="reserved" <?= ($filters['status'] ?? '') === 'reserved' ? 'selected' : '' ?>>
                                    <?= e('reserved') ?>
                                </option>
                                <option value="completed" <?= ($filters['status'] ?? '') === 'completed' ? 'selected' : '' ?>>
                                    <?= e('completed') ?>
                                </option>
                                <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>
                                    <?= e('cancelled') ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label><?= e('date_from') ?></label>
                            <input type="date" name="date_from" value="<?= e($filters['date_from'] ?? '') ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label><?= e('date_to') ?></label>
                            <input type="date" name="date_to" value="<?= e($filters['date_to'] ?? '') ?>">
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <?= e('search') ?>
                            </button>
                            <a href="<?= url('/rentals') ?>" class="btn btn-secondary">
                                <?= e('reset') ?>
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Table -->
                <div class="card">
                    <div class="card-body no-padding">
                        <?php if (empty($rentals)): ?>
                            <div class="empty-state-large">
                                <div class="empty-icon">📝</div>
                                <h3><?= e('no_rentals') ?></h3>
                                <p><?= e('no_rentals_subtitle') ?></p>
                                <a href="<?= url('/rentals/create') ?>" class="btn btn-primary">
                                    <?= e('create_first_rental') ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?= e('contract_no') ?></th>
                                            <th><?= e('customer') ?></th>
                                            <th><?= e('car') ?></th>
                                            <th><?= e('pickup_datetime') ?></th>
                                            <th><?= e('return_due_datetime') ?></th>
                                            <th><?= e('total_amount') ?></th>
                                            <th><?= e('status') ?></th>
                                            <th><?= e('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rentals as $r): ?>
                                            <tr>
                                                <td>
                                                    <a href="<?= url('/rentals/' . (int)$r['id']) ?>" class="table-link">
                                                        <code><?= e($r['contract_no'] ?? '') ?></code>
                                                    </a>
                                                </td>
                                                <td>
                                                    <strong><?= e($r['customer_name'] ?? '') ?></strong>
                                                    <small class="text-muted"><?= e($r['customer_phone'] ?? '') ?></small>
                                                </td>
                                                <td>
                                                    <strong><?= e($r['plate_number'] ?? '') ?></strong>
                                                    <small class="text-muted"><?= e(($r['brand'] ?? '') . ' ' . ($r['model'] ?? '')) ?></small>
                                                </td>
                                                <td><?= e(format_date($r['pickup_datetime'] ?? '')) ?></td>
                                                <td><?= e(format_date($r['return_due_datetime'] ?? '')) ?></td>
                                                <td>
                                                    <strong><?= number_format((float)($r['total_amount'] ?? 0), 2) ?></strong> 
                                                    <?= e('sar') ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= e(status_badge($r['status'] ?? '')) ?>">
                                                        <?= e($r['status'] ?? '') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="actions">
                                                        <a href="<?= url('/rentals/' . (int)$r['id']) ?>" 
                                                           class="btn-icon" 
                                                           title="<?= e('view') ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                                <circle cx="12" cy="12" r="3"></circle>
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (($totalPages ?? 1) > 1): ?>
                                <div class="pagination-wrapper">
                                    <div class="pagination-info">
                                        <?= e('showing') ?> <?= count($rentals) ?> <?= e('of') ?> <?= (int)$total ?>
                                    </div>
                                    <div class="pagination">
                                        <?php if ($currentPage > 1): ?>
                                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" class="page-link">←</a>
                                        <?php endif; ?>
                                        
                                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                                               class="page-link <?= $i === $currentPage ? 'active' : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($currentPage < $totalPages): ?>
                                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage + 1])) ?>" class="page-link">→</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
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