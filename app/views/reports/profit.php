<?php
/**
 * تقرير الأرباح
 */
$dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $dateTo ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('profit_report') ?> | <?= e('app_name') ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💰</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/rentals.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/reports.css') ?>">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                        <h2 class="page-title">💰 <?= e('profit_report') ?></h2>
                        <p class="page-subtitle"><?= e('profit_report_subtitle') ?></p>
                    </div>
                    <div class="page-actions">
                        <a href="<?= url('/reports/export?type=profit&date_from=' . $dateFrom . '&date_to=' . $dateTo) ?>" 
                           class="btn btn-primary">📥 <?= e('export_csv') ?></a>
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
                    <div class="stat-card stat-success">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_income') ?></span>
                            <strong class="stat-value"><?= number_format($summary['income'], 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                    <div class="stat-card stat-danger">
                        <div class="stat-icon">💸</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_expenses') ?></span>
                            <strong class="stat-value"><?= number_format($summary['expenses'], 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                    <div class="stat-card stat-info">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('net_profit') ?></span>
                            <strong class="stat-value"><?= number_format($summary['profit'], 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Cars Profit Table -->
                <div class="card">
                    <div class="card-header">
                        <h3>🚗 <?= e('profit_per_car') ?></h3>
                    </div>
                    <div class="card-body no-padding">
                        <?php if (empty($carsProfit)): ?>
                            <p class="empty-state"><?= e('no_data') ?></p>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th><?= e('car') ?></th>
                                            <th><?= e('income') ?></th>
                                            <th><?= e('expenses') ?></th>
                                            <th><?= e('net_profit') ?></th>
                                            <th><?= e('margin') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $totalIncome = 0;
                                        $totalExpenses = 0;
                                        $totalProfit = 0;
                                        foreach ($carsProfit as $i => $car): 
                                            $income = (float)$car['income'];
                                            $expenses = (float)$car['expenses'];
                                            $profit = $income - $expenses;
                                            $margin = $income > 0 ? ($profit / $income) * 100 : 0;
                                            $totalIncome += $income;
                                            $totalExpenses += $expenses;
                                            $totalProfit += $profit;
                                        ?>
                                            <tr>
                                                <td><?= $i + 1 ?></td>
                                                <td>
                                                    <strong><?= e($car['plate_number']) ?></strong>
                                                    <small class="text-muted"><?= e($car['brand'] . ' ' . $car['model']) ?></small>
                                                </td>
                                                <td class="text-success"><?= number_format($income, 2) ?></td>
                                                <td class="text-danger"><?= number_format($expenses, 2) ?></td>
                                                <td>
                                                    <strong class="<?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                                        <?= number_format($profit, 2) ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $margin >= 0 ? 'success' : 'danger' ?>">
                                                        <?= number_format($margin, 1) ?>%
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot>
                                        <tr class="total-row">
                                            <th colspan="2"><?= e('total') ?></th>
                                            <th class="text-success"><?= number_format($totalIncome, 2) ?></th>
                                            <th class="text-danger"><?= number_format($totalExpenses, 2) ?></th>
                                            <th class="<?= $totalProfit >= 0 ? 'text-success' : 'text-danger' ?>">
                                                <?= number_format($totalProfit, 2) ?>
                                            </th>
                                            <th>—</th>
                                        </tr>
                                    </tfoot>
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