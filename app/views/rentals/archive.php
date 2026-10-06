<?php
/**
 * صفحة سجل العقود (الأرشيف)
 */
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('rentals_archive') ?> | <?= e('app_name') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🗄️</text></svg>">
    
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
                
                <!-- رأس الصفحة مع أزرار التنقل بين العقود النشطة والأرشيف -->
                <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                    <div>
                        <h2 class="page-title"><?= e('rentals_archive_title') ?></h2>
                        <p class="page-subtitle"><?= e('rentals_archive_subtitle') ?></p>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <a href="<?= url('/rentals') ?>" class="btn btn-secondary">
                            <?= e('back_to_active_rentals') ?>
                        </a>
                    </div>
                </div>
                
                <!-- فلاتر البحث -->
                <div class="card" style="margin-bottom: 24px;">
                    <div class="card-body">
                        <form method="GET" action="<?= url('/rentals/archive') ?>" style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                            <div class="form-group" style="flex: 1; min-width: 280px; margin-bottom: 0;">
                                <label for="search"><?= e('search_in_archive') ?></label>
                                <input type="text" id="search" name="search" value="<?= e($_GET['search'] ?? '') ?>" placeholder="<?= e('archive_search_placeholder') ?>">
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-primary"><?= e('search') ?></button>
                                <?php if (!empty($_GET['search'])): ?>
                                    <a href="<?= url('/rentals/archive') ?>" class="btn btn-secondary"><?= e('clear_search') ?></a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- جدول البيانات -->
                <div class="card">
                    <div class="card-body no-padding">
                        <?php if (empty($rentals)): ?>
                            <div class="empty-state" style="padding: 50px; text-align: center;">
                                <div style="font-size: 3rem; margin-bottom: 10px;">📭</div>
                                <h3><?= e('no_archived_rentals') ?></h3>
                                <p class="text-muted"><?= e('no_archived_rentals_subtitle') ?></p>
                            </div>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?= e('contract_no') ?></th>
                                            <th><?= e('contract_date') ?></th>
                                            <th><?= e('customer') ?></th>
                                            <th><?= e('car') ?></th>
                                            <th><?= e('rental_period') ?></th>
                                            <th><?= e('total_amount') ?></th>
                                            <th><?= e('status') ?></th>
                                            <th><?= e('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rentals as $rental): ?>
                                            <tr>
                                                <td>
                                                    <strong><code><?= e($rental['contract_no']) ?></code></strong>
                                                </td>
                                                <td>
                                                    <span style="font-weight: 500; color: #374151;">
                                                        <?= e(format_datetime($rental['created_at'] ?? '')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?= e($rental['customer_name'] ?? '—') ?></strong>
                                                </td>
                                                <td>
                                                    <?= e($rental['brand'] ?? '') ?> <?= e($rental['model'] ?? '') ?>
                                                    <br><small class="text-muted"><?= e($rental['plate_number'] ?? '') ?></small>
                                                </td>
                                                <td>
                                                    <small><?= e('from_date') ?>: <?= e(format_datetime($rental['pickup_datetime'])) ?></small><br>
                                                    <small><?= e('to_date') ?>: <?= e(format_datetime($rental['return_due_datetime'])) ?></small>
                                                </td>
                                                <td>
                                                    <strong><?= number_format((float)$rental['total_amount'], 2) ?></strong> <?= e('sar') ?>
                                                </td>
                                                <td>
                                                    <?php if ($rental['status'] === 'completed'): ?>
                                                        <span class="badge badge-success">✅ <?= e('completed') ?></span>
                                                    <?php elseif ($rental['status'] === 'cancelled'): ?>
                                                        <span class="badge badge-danger">❌ <?= e('cancelled') ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary"><?= e($rental['status']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div style="display: flex; gap: 8px;">
                                                        <!-- زر عرض التفاصيل -->
                                                        <a href="<?= url('/rentals/' . $rental['id']) ?>" 
                                                           class="btn-icon btn-icon-primary" 
                                                           title="<?= e('view_details') ?>">
                                                            👁️
                                                        </a>
                                                        <!-- زر الطباعة -->
                                                        <a href="<?= url('/rentals/' . $rental['id'] . '/print') ?>" 
                                                           target="_blank" 
                                                           class="btn-icon btn-icon-success" 
                                                           title="<?= e('print_contract') ?>">
                                                            🖨️
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- ترقيم الصفحات (Pagination) -->
                            <?php if ($totalPages > 1): ?>
                                <div class="pagination" style="padding: 20px; display: flex; justify-content: center; gap: 8px; align-items: center;">
                                    <?php if ($currentPage > 1): ?>
                                        <a href="?page=<?= $currentPage - 1 ?>&search=<?= urlencode($_GET['search'] ?? '') ?>" class="btn btn-sm btn-secondary"><?= e('previous') ?></a>
                                    <?php endif; ?>
                                    
                                    <span style="font-size: 0.9rem; color: #666;"><?= e('page_word') ?> <?= $currentPage ?> <?= e('of') ?> <?= $totalPages ?></span>
                                    
                                    <?php if ($currentPage < $totalPages): ?>
                                        <a href="?page=<?= $currentPage + 1 ?>&search=<?= urlencode($_GET['search'] ?? '') ?>" class="btn btn-sm btn-secondary"><?= e('next') ?></a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            
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