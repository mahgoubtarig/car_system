<?php
/**
 * قائمة المصروفات
 */
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('expenses') ?> | <?= e('app_name') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💸</text></svg>">
    
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
                        <h2 class="page-title">💸 <?= e('expenses') ?></h2>
                        <p class="page-subtitle"><?= e('expenses_subtitle') ?></p>
                    </div>
                    <div class="page-actions">
                        <a href="<?= url('/expenses/create') ?>" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <?= e('add_expense') ?>
                        </a>
                    </div>
                </div>
                
                <!-- Flash -->
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
                    <div class="stat-card stat-info">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_expenses') ?></span>
                            <strong class="stat-value"><?= number_format($stats['total'], 2) ?> <?= e('sar') ?></strong>
                            <span class="stat-detail"><?= number_format($stats['count']) ?> <?= e('records') ?></span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('this_month') ?></span>
                            <strong class="stat-value"><?= number_format($stats['this_month'], 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-danger">
                        <div class="stat-icon">📆</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('today') ?></span>
                            <strong class="stat-value"><?= number_format($stats['today'], 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-success">
                        <div class="stat-icon">🏷️</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('categories') ?></span>
                            <strong class="stat-value"><?= count($stats['by_category']) ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card filters-card">
                    <form method="GET" action="<?= url('/expenses') ?>" class="filters-form">
                        <div class="filter-group">
                            <label><?= e('search') ?></label>
                            <input type="text" name="search" 
                                   value="<?= e($filters['search']) ?>" 
                                   placeholder="<?= e('search_expenses_placeholder') ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label><?= e('car') ?></label>
                            <select name="car_id">
                                <option value=""><?= e('all_cars') ?></option>
                                <?php foreach ($cars as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" 
                                        <?= $filters['car_id'] == $c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['plate_number']) ?> — <?= e($c['brand']) ?> <?= e($c['model']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label><?= e('category') ?></label>
                            <select name="category_id">
                                <option value=""><?= e('all_categories') ?></option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= (int)$cat['id'] ?>"
                                        <?= $filters['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                        <?= e(Lang::isArabic() ? $cat['name_ar'] : $cat['name_en']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label><?= e('date_from') ?></label>
                            <input type="date" name="date_from" value="<?= e($filters['date_from']) ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label><?= e('date_to') ?></label>
                            <input type="date" name="date_to" value="<?= e($filters['date_to']) ?>">
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">🔍 <?= e('search') ?></button>
                            <a href="<?= url('/expenses') ?>" class="btn btn-secondary"><?= e('reset') ?></a>
                        </div>
                    </form>
                </div>
                
                <!-- Total -->
                <?php if ($totalAmount > 0): ?>
                    <div class="alert alert-info">
                        <strong><?= e('total_filtered') ?>:</strong>
                        <?= number_format($totalAmount, 2) ?> <?= e('sar') ?>
                        (<?= number_format($total) ?> <?= e('records') ?>)
                    </div>
                <?php endif; ?>
                
                <!-- Table -->
                <div class="card">
                    <div class="card-body no-padding">
                        <?php if (empty($expenses)): ?>
                            <div class="empty-state-large">
                                <div class="empty-icon">💸</div>
                                <h3><?= e('no_expenses') ?></h3>
                                <p><?= e('no_expenses_subtitle') ?></p>
                                <a href="<?= url('/expenses/create') ?>" class="btn btn-primary">
                                    <?= e('add_first_expense') ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?= e('date') ?></th>
                                            <th><?= e('car') ?></th>
                                            <th><?= e('category') ?></th>
                                            <th><?= e('description') ?></th>
                                            <th><?= e('vendor') ?></th>
                                            <th><?= e('amount') ?></th>
                                            <th><?= e('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expenses as $e_item): ?>
                                            <tr>
                                                <td><?= e(format_date($e_item['expense_date'])) ?></td>
                                                <td>
                                                    <a href="<?= url('/cars/' . (int)$e_item['car_id']) ?>" class="table-link">
                                                        <strong><?= e($e_item['plate_number'] ?? '') ?></strong>
                                                    </a>
                                                    <small class="text-muted">
                                                        <?= e(($e_item['brand'] ?? '') . ' ' . ($e_item['model'] ?? '')) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?= e(Lang::isArabic() 
                                                            ? ($e_item['category_name_ar'] ?? '') 
                                                            : ($e_item['category_name_en'] ?? '')) ?>
                                                    </span>
                                                </td>
                                                <td><?= e($e_item['description'] ?? '') ?></td>
                                                <td><?= e($e_item['vendor'] ?? '—') ?></td>
                                                <td>
                                                    <strong class="text-danger">
                                                        <?= number_format((float)$e_item['amount'], 2) ?>
                                                    </strong> 
                                                    <?= e('sar') ?>
                                                </td>
                                                <td>
                                                    <div class="actions">
                                                        <a href="<?= url('/expenses/' . (int)$e_item['id']) ?>" 
                                                           class="btn-icon" title="<?= e('view') ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                                <circle cx="12" cy="12" r="3"></circle>
                                                            </svg>
                                                        </a>
                                                        <a href="<?= url('/expenses/' . (int)$e_item['id'] . '/edit') ?>" 
                                                           class="btn-icon" title="<?= e('edit') ?>">
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
                            
                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <div class="pagination-wrapper">
                                    <div class="pagination-info">
                                        <?= e('showing') ?> <?= count($expenses) ?> <?= e('of') ?> <?= $total ?>
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