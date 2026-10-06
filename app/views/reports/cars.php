<?php
/**
 * تقرير السيارات
 */
$dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $dateTo ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('cars_report') ?> | <?= e('app_name') ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚗</text></svg>">
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
                        <h2 class="page-title">🚗 <?= e('cars_report') ?></h2>
                        <p class="page-subtitle"><?= e('cars_report_subtitle') ?></p>
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
                
                <!-- Table -->
                <div class="card">
                    <div class="card-header">
                        <h3>📊 <?= e('cars_performance') ?></h3>
                    </div>
                    <div class="card-body no-padding">
                        <?php if (empty($carsReport)): ?>
                            <p class="empty-state"><?= e('no_data') ?></p>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?= e('car') ?></th>
                                            <th><?= e('type') ?></th>
                                            <th><?= e('total_rentals') ?></th>
                                            <th><?= e('rental_days') ?></th>
                                            <th><?= e('income') ?></th>
                                            <th><?= e('expenses') ?></th>
                                            <th><?= e('net_profit') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($carsReport as $car): 
                                            $income = (float)$car['income'];
                                            $expenses = (float)$car['expenses'];
                                            $profit = $income - $expenses;
                                        ?>
                                            <tr>
                                                <td>
                                                    <a href="<?= url('/cars/' . (int)$car['id']) ?>" class="table-link">
                                                        <strong><?= e($car['plate_number']) ?></strong>
                                                    </a>
                                                    <small class="text-muted"><?= e($car['brand'] . ' ' . $car['model']) ?></small>
                                                </td>
                                                <td>
                                                    <?= e(Lang::isArabic() 
                                                        ? ($car['type_name_ar'] ?? '—') 
                                                        : ($car['type_name_en'] ?? '—')) ?>
                                                </td>
                                                <td><?= (int)$car['total_rentals'] ?></td>
                                                <td><?= (int)$car['rental_days'] ?> <?= e('days') ?></td>
                                                <td class="text-success"><?= number_format($income, 2) ?></td>
                                                <td class="text-danger"><?= number_format($expenses, 2) ?></td>
                                                <td>
                                                    <strong class="<?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                                        <?= number_format($profit, 2) ?>
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
                
            </main>
            <?php require BASE_PATH . '/app/views/layout/footer.php'; ?>
        </div>
    </div>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
</body>
</html>