<?php
/**
 * تقرير المدفوعات
 */
$dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $dateTo ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('payments_report') ?> | <?= e('app_name') ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💵</text></svg>">
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
                        <h2 class="page-title">💵 <?= e('payments_report') ?></h2>
                        <p class="page-subtitle"><?= e('payments_report_subtitle') ?></p>
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
                    <div class="stat-card stat-success">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_income') ?></span>
                            <strong class="stat-value"><?= number_format($summary['income'], 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- By Method -->
                <div class="card">
                    <div class="card-header">
                        <h3>💳 <?= e('payments_by_method') ?></h3>
                    </div>
                    <div class="card-body no-padding">
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?= e('method') ?></th>
                                        <th><?= e('count') ?></th>
                                        <th><?= e('total') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paymentsByMethod as $pm): 
                                        $icons = ['cash' => '💵', 'network' => '💳', 'bank_transfer' => '🏦'];
                                    ?>
                                        <tr>
                                            <td>
                                                <?= $icons[$pm['method']] ?? '' ?>
                                                <?= e($pm['method']) ?>
                                            </td>
                                            <td><?= (int)$pm['count'] ?></td>
                                            <td>
                                                <strong class="text-success">
                                                    <?= number_format((float)$pm['total'], 2) ?> <?= e('sar') ?>
                                                </strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- By Day -->
                <div class="card">
                    <div class="card-header">
                        <h3>📅 <?= e('payments_by_day') ?></h3>
                    </div>
                    <div class="card-body no-padding">
                        <div class="table-wrapper">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?= e('date') ?></th>
                                        <th><?= e('count') ?></th>
                                        <th>💵 <?= e('cash') ?></th>
                                        <th>💳 <?= e('network') ?></th>
                                        <th><?= e('total') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($paymentsByDay as $pd): ?>
                                        <tr>
                                            <td><?= e(format_date($pd['date'])) ?></td>
                                            <td><?= (int)$pd['count'] ?></td>
                                            <td class="text-success"><?= number_format((float)$pd['cash_total'], 2) ?></td>
                                            <td class="text-info"><?= number_format((float)$pd['network_total'], 2) ?></td>
                                            <td>
                                                <strong><?= number_format((float)$pd['total'], 2) ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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