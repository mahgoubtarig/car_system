<!DOCTYPE html>
<html lang="<?= Lang::current() ?>" dir="<?= Lang::direction() ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('dashboard') ?> | <?= e('app_name') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚗</text></svg>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>">
    
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
                
                <!-- ترحيب -->
                <div class="welcome-banner">
                    <div>
                        <h2><?= e('welcome') ?>، <?= e($user['full_name'] ?? '') ?> 👋</h2>
                        <p><?= e('dashboard_welcome_msg') ?></p>
                    </div>
                    <div class="welcome-date">
                        <span><?= date('l') ?></span>
                        <strong><?= date('Y-m-d') ?></strong>
                    </div>
                </div>
                
                <!-- بطاقات الإحصائيات الرئيسية -->
                <div class="stats-grid">
                    
                    <div class="stat-card stat-primary">
                        <div class="stat-icon">🚗</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_cars') ?></span>
                            <strong class="stat-value"><?= number_format($stats['total_cars']) ?></strong>
                            <span class="stat-detail">
                                <?= e('available') ?>: <?= $stats['available_cars'] ?> • 
                                <?= e('rented') ?>: <?= $stats['rented_cars'] ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-success">
                        <div class="stat-icon">📄</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('active_rentals') ?></span>
                            <strong class="stat-value"><?= number_format($stats['active_rentals']) ?></strong>
                            <span class="stat-detail">
                                <?= e('ending_today') ?>: <?= $stats['rentals_ending_today'] ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-info">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('month_income') ?></span>
                            <strong class="stat-value"><?= number_format($stats['month_income'], 2) ?> <?= e('sar') ?></strong>
                            <span class="stat-detail">
                                <?= e('today') ?>: <?= number_format($stats['today_payments'], 2) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('month_profit') ?></span>
                            <strong class="stat-value <?= $stats['month_profit'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($stats['month_profit'], 2) ?> <?= e('sar') ?>
                            </strong>
                            <span class="stat-detail">
                                <?= e('expenses') ?>: <?= number_format($stats['month_expenses'], 2) ?>
                            </span>
                        </div>
                    </div>
                    
                </div>
                
                <!-- بطاقات ثانوية -->
                <div class="stats-grid stats-grid-secondary">
                    
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm">👥</div>
                        <div>
                            <span class="stat-label-sm"><?= e('customers') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['total_customers']) ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm">💵</div>
                        <div>
                            <span class="stat-label-sm"><?= e('today_cash') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['today_cash'], 2) ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm">💳</div>
                        <div>
                            <span class="stat-label-sm"><?= e('today_network') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['today_network'], 2) ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card-sm stat-card-danger">
                        <div class="stat-icon-sm">⚠️</div>
                        <div>
                            <span class="stat-label-sm"><?= e('overdue_payments') ?></span>
                            <strong class="stat-value-sm"><?= number_format($stats['overdue_payments']) ?></strong>
                        </div>
                    </div>
                    
                </div>
                
                <!-- الرسم البياني + التنبيهات -->
                <div class="grid-2">
                    
                    <!-- الرسم البياني -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= e('income_vs_expenses') ?></h3>
                            <span class="card-subtitle"><?= e('last_12_months') ?></span>
                        </div>
                        <div class="card-body">
                            <canvas id="revenueChart" height="280"></canvas>
                        </div>
                    </div>
                    
                    <!-- التنبيهات -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= e('notifications') ?></h3>
                            <a href="<?= url('/notifications') ?>" class="card-link"><?= e('view_all') ?></a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($notifications)): ?>
                                <p class="empty-state"><?= e('no_notifications') ?></p>
                            <?php else: ?>
                                <ul class="notifications-list">
                                    <?php foreach ($notifications as $notif): ?>
                                        <li class="notification-item">
                                            <span class="notification-dot"></span>
                                            <div>
                                                <strong><?= e($notif['type']) ?></strong>
                                                <small><?= date('Y-m-d', strtotime($notif['due_date'])) ?></small>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
                
                <!-- أحدث العقود + أعلى السيارات -->
                <div class="grid-2">
                    
                    <!-- أحدث العقود -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= e('recent_rentals') ?></h3>
                            <a href="<?= url('/rentals') ?>" class="card-link"><?= e('view_all') ?></a>
                        </div>
                        <div class="card-body no-padding">
                            <?php if (empty($recentRentals)): ?>
                                <p class="empty-state"><?= e('no_rentals') ?></p>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?= e('contract_no') ?></th>
                                            <th><?= e('customer') ?></th>
                                            <th><?= e('car') ?></th>
                                            <th><?= e('status') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentRentals as $rental): ?>
                                            <tr>
                                                <td><code><?= e($rental['contract_no']) ?></code></td>
                                                <td><?= e($rental['customer_name']) ?></td>
                                                <td><?= e($rental['plate_number']) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= getStatusColor($rental['status']) ?>">
                                                        <?= e($rental['status']) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- أعلى السيارات ربحاً -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= e('top_cars') ?></h3>
                            <a href="<?= url('/reports') ?>" class="card-link"><?= e('reports') ?></a>
                        </div>
                        <div class="card-body no-padding">
                            <?php if (empty($topCars)): ?>
                                <p class="empty-state"><?= e('no_data') ?></p>
                            <?php else: ?>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?= e('car') ?></th>
                                            <th><?= e('income') ?></th>
                                            <th><?= e('profit') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topCars as $car): ?>
                                            <?php $profit = $car['income'] - $car['expenses']; ?>
                                            <tr>
                                                <td>
                                                    <strong><?= e($car['plate_number']) ?></strong><br>
                                                    <small><?= e($car['brand'] . ' ' . $car['model']) ?></small>
                                                </td>
                                                <td><?= number_format($car['income'], 2) ?></td>
                                                <td class="<?= $profit >= 0 ? 'text-success' : 'text-danger' ?>">
                                                    <strong><?= number_format($profit, 2) ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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
            locale: '<?= Lang::current() ?>',
            direction: '<?= Lang::direction() ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
            chartData: <?= json_encode($chartData, JSON_UNESCAPED_UNICODE) ?>,
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>

<?php
/**
 * دالة مساعدة للحصول على لون الحالة
 */
function getStatusColor(string $status): string
{
    return match ($status) {
        'active'    => 'success',
        'reserved'  => 'info',
        'completed' => 'secondary',
        'cancelled' => 'danger',
        'overdue'   => 'danger',
        default     => 'secondary',
    };
}
?>