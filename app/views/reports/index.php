<?php
/**
 * صفحة التقارير الرئيسية
 */
$dateFrom = $dateFrom ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $dateTo ?? date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('reports') ?> | <?= e('app_name') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📊</text></svg>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/rentals.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/reports.css') ?>">
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
                        <h2 class="page-title">📊 <?= e('reports') ?></h2>
                        <p class="page-subtitle"><?= e('reports_subtitle') ?></p>
                    </div>
                    <div class="page-actions">
                        <a href="<?= url('/reports/export?type=profit&date_from=' . $dateFrom . '&date_to=' . $dateTo) ?>" 
                           class="btn btn-secondary">
                            📥 <?= e('export_csv') ?>
                        </a>
                    </div>
                </div>
                
                <!-- Date Filter -->
                <div class="card filters-card">
                    <form method="GET" action="<?= url('/reports') ?>" class="filters-form">
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
                            <a href="<?= url('/reports') ?>" class="btn btn-secondary"><?= e('reset') ?></a>
                        </div>
                    </form>
                </div>
                
                <!-- Quick Links -->
                <div class="report-links">
                    <a href="<?= url('/reports/profit?date_from=' . $dateFrom . '&date_to=' . $dateTo) ?>" class="report-link">
                        <div class="report-link-icon" style="background:var(--success-light);">💰</div>
                        <div>
                            <strong><?= e('profit_report') ?></strong>
                            <span><?= e('profit_report_desc') ?></span>
                        </div>
                    </a>
                    
                    <a href="<?= url('/reports/expenses?date_from=' . $dateFrom . '&date_to=' . $dateTo) ?>" class="report-link">
                        <div class="report-link-icon" style="background:var(--danger-light);">💸</div>
                        <div>
                            <strong><?= e('expenses_report') ?></strong>
                            <span><?= e('expenses_report_desc') ?></span>
                        </div>
                    </a>
                    
                    <a href="<?= url('/reports/cars?date_from=' . $dateFrom . '&date_to=' . $dateTo) ?>" class="report-link">
                        <div class="report-link-icon" style="background:var(--primary-light);">🚗</div>
                        <div>
                            <strong><?= e('cars_report') ?></strong>
                            <span><?= e('cars_report_desc') ?></span>
                        </div>
                    </a>
                    
                    <a href="<?= url('/reports/payments?date_from=' . $dateFrom . '&date_to=' . $dateTo) ?>" class="report-link">
                        <div class="report-link-icon" style="background:var(--info-color); color:white;">💵</div>
                        <div>
                            <strong><?= e('payments_report') ?></strong>
                            <span><?= e('payments_report_desc') ?></span>
                        </div>
                    </a>
                </div>
                
                <!-- Summary Cards -->
                <div class="stats-grid">
                    <div class="stat-card stat-success">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('income') ?></span>
                            <strong class="stat-value"><?= number_format($summary['income'], 2) ?> <?= e('sar') ?></strong>
                            <span class="stat-detail"><?= $summary['rentals'] ?> <?= e('rental') ?></span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-danger">
                        <div class="stat-icon">💸</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('expenses') ?></span>
                            <strong class="stat-value"><?= number_format($summary['expenses'], 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-<?= $summary['profit'] >= 0 ? 'success' : 'danger' ?>">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('net_profit') ?></span>
                            <strong class="stat-value"><?= number_format($summary['profit'], 2) ?> <?= e('sar') ?></strong>
                            <span class="stat-detail"><?= number_format($summary['margin'], 1) ?>% <?= e('margin') ?></span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-info">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('period') ?></span>
                            <strong class="stat-value" style="font-size:1rem;">
                                <?= e(format_date($dateFrom)) ?> 
                                → 
                                <?= e(format_date($dateTo)) ?>
                            </strong>
                        </div>
                    </div>
                </div>
                
                <!-- Chart -->
                <div class="card">
                    <div class="card-header">
                        <h3>📈 <?= e('income_vs_expenses') ?></h3>
                        <span class="card-subtitle"><?= e('last_12_months') ?></span>
                    </div>
                    <div class="card-body">
                        <canvas id="reportsChart" height="300"></canvas>
                    </div>
                </div>
                
                <!-- Top Profitable Cars -->
                <div class="grid-2">
                    <div class="card">
                        <div class="card-header">
                            <h3>🏆 <?= e('top_profitable_cars') ?></h3>
                        </div>
                        <div class="card-body no-padding">
                            <?php if (empty($carsProfit)): ?>
                                <p class="empty-state"><?= e('no_data') ?></p>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th><?= e('car') ?></th>
                                                <th><?= e('income') ?></th>
                                                <th><?= e('profit') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $sorted = $carsProfit;
                                            usort($sorted, fn($a, $b) => ($b['income'] - $b['expenses']) <=> ($a['income'] - $a['expenses']));
                                            foreach (array_slice($sorted, 0, 5) as $car): 
                                                $profit = (float)$car['income'] - (float)$car['expenses'];
                                            ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= e($car['plate_number']) ?></strong>
                                                        <small class="text-muted"><?= e($car['brand'] . ' ' . $car['model']) ?></small>
                                                    </td>
                                                    <td><?= number_format((float)$car['income'], 2) ?></td>
                                                    <td class="<?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                                        <strong><?= number_format($profit, 2) ?></strong>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3>💸 <?= e('top_expense_categories') ?></h3>
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
                                            <?php foreach (array_slice($expensesByCategory, 0, 5) as $cat): ?>
                                                <tr>
                                                    <td>
                                                        <?= e(Lang::isArabic() ? $cat['name_ar'] : $cat['name_en']) ?>
                                                    </td>
                                                    <td><?= (int)$cat['count'] ?></td>
                                                    <td>
                                                        <strong class="text-danger">
                                                            <?= number_format((float)$cat['total'], 2) ?>
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
    
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= url('') ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
            chartData: <?= json_encode($monthlyData ?? [], JSON_UNESCAPED_UNICODE) ?>,
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('reportsChart');
            if (!canvas || !window.APP_CONFIG.chartData) return;
            
            const data = window.APP_CONFIG.chartData;
            const isRTL = document.documentElement.dir === 'rtl';
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            
            const textColor = isDark ? '#e0e0e0' : '#212529';
            const gridColor = isDark ? '#404040' : '#e1e4e8';
            
            new Chart(canvas, {
                type: 'line',
                data: {
                    labels: data.months.map(m => {
                        const [y, mo] = m.split('-');
                        const months = isRTL
                            ? ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر']
                            : ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                        return months[parseInt(mo) - 1] + ' ' + y.slice(2);
                    }),
                    datasets: [
                        {
                            label: isRTL ? 'الإيرادات' : 'Income',
                            data: data.income,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.4,
                            fill: true,
                        },
                        {
                            label: isRTL ? 'المصروفات' : 'Expenses',
                            data: data.expenses,
                            borderColor: '#ef4444',
                            backgroundColor: 'rgba(239, 68, 68, 0.1)',
                            tension: 0.4,
                            fill: true,
                        },
                        {
                            label: isRTL ? 'صافي الربح' : 'Net Profit',
                            data: data.profit,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            tension: 0.4,
                            borderDash: [5, 5],
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { labels: { color: textColor, font: { family: 'Cairo' } } },
                    },
                    scales: {
                        x: { ticks: { color: textColor, font: { family: 'Cairo' } }, grid: { color: gridColor } },
                        y: { ticks: { color: textColor, font: { family: 'Cairo' } }, grid: { color: gridColor }, beginAtZero: true },
                    },
                },
            });
        });
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>