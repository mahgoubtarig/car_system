<?php
/**
 * تقرير المصروفات
 */
$dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $dateTo ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('expenses_report') ?> | <?= e('app_name') ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💸</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/rentals.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/reports.css') ?>">
</head>
<body class="dashboard-page">
    <div class="app-layout">
        <?php require BASE_PATH . '/app/views/layout/sidebar.php'; ?>
        <div class="main-wrapper">
            <?php require BASE_PATH . '/app/views/layout/header.php'; ?>
            <main class="main-content">
                
                <div class="page-header">
                    <div>
                        <a href="<?= url('/reports') ?>" class="back-link">← <?= e('back_to_reports') ?></a>
                        <h2 class="page-title">💸 <?= e('expenses_report') ?></h2>
                        <p class="page-subtitle"><?= e('expenses_report_subtitle') ?></p>
                    </div>
                    <div class="page-actions">
                        <button onclick="window.print()" class="btn btn-secondary">🖨️ <?= e('print') ?></button>
                    </div>
                </div>
                
                <!-- Filter -->
                <div class="card filters-card">
                    <form method="GET" class="filters-form">
                        <div class="filter-group">
                            <label><?= e('date_from') ?></label>
                            <input type="date" name="date_from" value="<?= e($dateFrom) ?>">
                        </div>
                        <div class="filter-group">
                            <label><?= e('date_to') ?></label>
                            <input type="date" name="date_to" value="<?= e($dateTo) ?>">
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">🔍 <?= e('apply') ?></button>
                        </div>
                    </form>
                </div>
                
                <!-- Summary -->
                <div class="stats-grid">
                    <div class="stat-card stat-danger">
                        <div class="stat-icon">💸</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_expenses') ?></span>
                            <strong class="stat-value"><?= number_format($summary['expenses'], 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- By Category -->
                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <h3>📂 <?= e('expenses_by_category') ?></h3>
                        </div>
                        <div class="card-body no-padding">
                            <?php if (empty($expensesByCategory)): ?>
                                <p class="empty-state"><?= e('no_data') ?></p>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th><?= e('category') ?></th>
                                                <th><?= e('records') ?></th>
                                                <th><?= e('total') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $catTotal = 0;
                                            foreach ($expensesByCategory as $cat): 
                                                $catTotal += (float)$cat['total'];
                                            ?>
                                                <tr>
                                                    <td><?= e(Lang::isArabic() ? $cat['name_ar'] : $cat['name_en']) ?></td>
                                                    <td><?= (int)$cat['count'] ?></td>
                                                    <td>
                                                        <strong class="text-danger">
                                                            <?= number_format((float)$cat['total'], 2) ?>
                                                        </strong>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="total-row">
                                                <th colspan="2"><?= e('total') ?></th>
                                                <th class="text-danger"><?= number_format($catTotal, 2) ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>🚗 <?= e('expenses_by_car') ?></h3>
                        </div>
                        <div class="card-body no-padding">
                            <?php if (empty($expensesByCar)): ?>
                                <p class="empty-state"><?= e('no_data') ?></p>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th><?= e('car') ?></th>
                                                <th><?= e('records') ?></th>
                                                <th><?= e('total') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($expensesByCar as $car): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= e($car['plate_number']) ?></strong>
                                                        <small class="text-muted"><?= e($car['brand'] . ' ' . $car['model']) ?></small>
                                                    </td>
                                                    <td><?= (int)$car['count'] ?></td>
                                                    <td>
                                                        <strong class="text-danger">
                                                            <?= number_format((float)$car['total'], 2) ?>
                                                        </strong>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
            </main>
            <?php require BASE_PATH . '/app/views/layout/footer.php'; ?>
        </div>
    </div>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
</body>
</html>