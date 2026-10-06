<?php
/**
 * قائمة المدفوعات
 */
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('payments') ?> | <?= e('app_name') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💵</text></svg>">
    
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
                        <h2 class="page-title">💵 <?= e('payments') ?></h2>
                        <p class="page-subtitle"><?= e('payments_subtitle') ?></p>
                    </div>
                </div>
                
                <!-- Flash -->
                <?php $successMsg = Auth::getFlash('success'); ?>
                <?php if (!empty($successMsg) && is_string($successMsg)): ?>
                    <div class="alert alert-success">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span><?= e($successMsg) ?></span>
                    </div>
                <?php endif; ?>
                
                <?php $errorMsg = Auth::getFlash('error'); ?>
                <?php if (!empty($errorMsg) && is_string($errorMsg)): ?>
                    <div class="alert alert-error">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?= e($errorMsg) ?></span>
                    </div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="stats-grid">
                    <div class="stat-card stat-success">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('today_payments') ?></span>
                            <strong class="stat-value"><?= number_format($stats['today_payments'], 2) ?> <?= e('sar') ?></strong>
                            <span class="stat-detail">
                                💵 <?= number_format($stats['today_cash'], 2) ?> • 
                                💳 <?= number_format($stats['today_network'], 2) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-info">
                        <div class="stat-icon">📅</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('month_total') ?></span>
                            <strong class="stat-value"><?= number_format($stats['month_total'], 2) ?> <?= e('sar') ?></strong>
                            <span class="stat-detail">
                                <?= number_format($stats['total_payments']) ?> <?= e('payment') ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">⏳</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_remaining') ?></span>
                            <strong class="stat-value"><?= number_format($stats['total_remaining'], 2) ?> <?= e('sar') ?></strong>
                            <span class="stat-detail">
                                <?= e('from_active_contracts') ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-danger">
                        <div class="stat-icon">⚠️</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('overdue_amount') ?></span>
                            <strong class="stat-value"><?= number_format($stats['overdue_amount'], 2) ?> <?= e('sar') ?></strong>
                            <span class="stat-detail">
                                <?= $stats['overdue_count'] ?> <?= e('overdue_payment') ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="tabs">
                    <a href="?tab=payments" class="tab <?= ($filters['tab'] ?? 'payments') === 'payments' ? 'active' : '' ?>">
                        💵 <?= e('payments_log') ?>
                    </a>
                    <a href="?tab=schedules" class="tab <?= ($filters['tab'] ?? '') === 'schedules' ? 'active' : '' ?>">
                        📅 <?= e('payment_schedules') ?>
                    </a>
                    <a href="?tab=upcoming" class="tab <?= ($filters['tab'] ?? '') === 'upcoming' ? 'active' : '' ?>">
                        ⏰ <?= e('upcoming') ?>
                    </a>
                    <a href="?tab=overdue" class="tab <?= ($filters['tab'] ?? '') === 'overdue' ? 'active' : '' ?>">
                        ⚠️ <?= e('overdue') ?>
                        <?php if ($stats['overdue_count'] > 0): ?>
                            <span class="tab-badge"><?= $stats['overdue_count'] ?></span>
                        <?php endif; ?>
                    </a>
                </div>
                
                <!-- Filters -->
                <div class="card filters-card">
                    <form method="GET" action="<?= url('/payments') ?>" class="filters-form">
                        <input type="hidden" name="tab" value="<?= e($filters['tab'] ?? 'payments') ?>">
                        
                        <div class="filter-group">
                            <label><?= e('search') ?></label>
                            <input type="text" name="search" 
                                   value="<?= e($filters['search'] ?? '') ?>" 
                                   placeholder="<?= e('search_payments_placeholder') ?>">
                        </div>
                        
                        <?php if (($filters['tab'] ?? 'payments') === 'payments'): ?>
                            <div class="filter-group">
                                <label><?= e('payment_method') ?></label>
                                <select name="method">
                                    <option value=""><?= e('all') ?></option>
                                    <option value="cash" <?= ($filters['method'] ?? '') === 'cash' ? 'selected' : '' ?>>
                                        💵 <?= e('cash') ?>
                                    </option>
                                    <option value="network" <?= ($filters['method'] ?? '') === 'network' ? 'selected' : '' ?>>
                                        💳 <?= e('network') ?>
                                    </option>
                                    <option value="bank_transfer" <?= ($filters['method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>
                                        🏦 <?= e('bank_transfer') ?>
                                    </option>
                                </select>
                            </div>
                            
                            <div class="filter-group">
                                <label><?= e('payment_type') ?></label>
                                <select name="type">
                                    <option value=""><?= e('all') ?></option>
                                    <option value="rent" <?= ($filters['type'] ?? '') === 'rent' ? 'selected' : '' ?>>
                                        <?= e('rent') ?>
                                    </option>
                                    <option value="penalty" <?= ($filters['type'] ?? '') === 'penalty' ? 'selected' : '' ?>>
                                        <?= e('penalty') ?>
                                    </option>
                                    <option value="deposit_refund" <?= ($filters['type'] ?? '') === 'deposit_refund' ? 'selected' : '' ?>>
                                        <?= e('deposit_refund') ?>
                                    </option>
                                </select>
                            </div>
                        <?php endif; ?>
                        
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
                                🔍 <?= e('search') ?>
                            </button>
                            <a href="<?= url('/payments') ?>?tab=<?= e($filters['tab'] ?? 'payments') ?>" class="btn btn-secondary">
                                <?= e('reset') ?>
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- ============================================ -->
                <!-- Tab 1: Payments Log -->
                <!-- ============================================ -->
                <?php if (($filters['tab'] ?? 'payments') === 'payments'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>💵 <?= e('payments_log') ?></h3>
                            <span class="card-subtitle"><?= number_format($total) ?> <?= e('records') ?></span>
                        </div>
                        <div class="card-body no-padding">
                            <?php if (empty($payments)): ?>
                                <div class="empty-state-large">
                                    <div class="empty-icon">💵</div>
                                    <h3><?= e('no_payments') ?></h3>
                                    <p><?= e('no_payments_subtitle') ?></p>
                                </div>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><?= e('receipt_no') ?></th>
                                                <th><?= e('contract_no') ?></th>
                                                <th><?= e('customer') ?></th>
                                                <th><?= e('car') ?></th>
                                                <th><?= e('payment_type') ?></th>
                                                <th><?= e('method') ?></th>
                                                <th><?= e('amount') ?></th>
                                                <th><?= e('paid_at') ?></th>
                                                <th><?= e('actions') ?></th> <!-- تمت إضافة عمود الإجراءات للطباعة -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($payments as $p): ?>
                                                <tr>
                                                    <td><code><?= e($p['receipt_no'] ?? '') ?></code></td>
                                                    <td>
                                                        <a href="<?= url('/rentals/' . (int)$p['rental_id']) ?>" class="table-link">
                                                            <?= e($p['contract_no'] ?? '') ?>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <strong><?= e($p['customer_name'] ?? '') ?></strong>
                                                        <small class="text-muted"><?= e($p['customer_phone'] ?? '') ?></small>
                                                    </td>
                                                    <td><?= e($p['plate_number'] ?? '') ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= e(status_badge($p['payment_type'] ?? '')) ?>">
                                                            <?= e($p['payment_type'] ?? '') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $methodIcons = ['cash' => '💵', 'network' => '💳', 'bank_transfer' => '🏦'];
                                                        echo $methodIcons[$p['method'] ?? ''] ?? '';
                                                        ?>
                                                        <?= e($p['method'] ?? '') ?>
                                                    </td>
                                                    <td>
                                                        <strong class="text-success">
                                                            <?= number_format((float)($p['amount'] ?? 0), 2) ?>
                                                        </strong> 
                                                        <?= e('sar') ?>
                                                    </td>
                                                    <td><?= e(format_datetime($p['paid_at'] ?? '')) ?></td>
                                                    <td>
                                                        <!-- زر الطباعة لكل عملية دفع -->
                                                        <a href="<?= url('/payments/' . (int)$p['id'] . '/print') ?>" 
                                                           target="_blank" 
                                                           class="btn btn-sm btn-secondary" 
                                                           title="<?= e('print') ?>">
                                                            🖨️ <?= e('print') ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if (($totalPages ?? 1) > 1): ?>
                                    <div class="pagination-wrapper">
                                        <div class="pagination-info">
                                            <?= e('showing') ?> <?= count($payments) ?> <?= e('of') ?> <?= (int)$total ?>
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
                <?php endif; ?>
                
                <!-- ============================================ -->
                <!-- Tab 2: Payment Schedules (المتبقي) -->
                <!-- ============================================ -->
                <?php if (($filters['tab'] ?? '') === 'schedules'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>📅 <?= e('payment_schedules') ?></h3>
                            <span class="card-subtitle">
                                <?= count($schedules) ?> <?= e('records') ?> — 
                                <?= e('total') ?>: <?= number_format($stats['total_remaining'], 2) ?> <?= e('sar') ?>
                            </span>
                        </div>
                        <div class="card-body no-padding">
                            <?php if (empty($schedules)): ?>
                                <div class="empty-state-large">
                                    <div class="empty-icon">✅</div>
                                    <h3><?= e('no_remaining_payments') ?></h3>
                                    <p><?= e('all_payments_clear') ?></p>
                                </div>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th><?= e('contract_no') ?></th>
                                                <th><?= e('customer') ?></th>
                                                <th><?= e('car') ?></th>
                                                <th><?= e('due_date') ?></th>
                                                <th><?= e('amount') ?></th>
                                                <th><?= e('paid') ?></th>
                                                <th><?= e('remaining') ?></th>
                                                <th><?= e('status') ?></th>
                                                <th><?= e('actions') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($schedules as $s): ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?= url('/rentals/' . (int)$s['rental_id']) ?>" class="table-link">
                                                            <code><?= e($s['contract_no'] ?? '') ?></code>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <strong><?= e($s['customer_name'] ?? '') ?></strong>
                                                        <small class="text-muted"><?= e($s['customer_phone'] ?? '') ?></small>
                                                    </td>
                                                    <td>
                                                        <?= e($s['plate_number'] ?? '') ?>
                                                        <small class="text-muted"><?= e(($s['brand'] ?? '') . ' ' . ($s['model'] ?? '')) ?></small>
                                                    </td>
                                                    <td>
                                                        <?= e(format_date($s['due_date'] ?? '')) ?>
                                                        <?php 
                                                        $days = (int)($s['days_remaining'] ?? 0);
                                                        if ($days < 0): 
                                                        ?>
                                                            <small class="text-danger">
                                                                (<?= abs($days) ?> <?= e('days_late') ?>)
                                                            </small>
                                                        <?php elseif ($days === 0): ?>
                                                            <small class="text-warning">(<?= e('today') ?>)</small>
                                                        <?php elseif ($days <= 7): ?>
                                                            <small class="text-warning">(<?= $days ?> <?= e('days') ?>)</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= number_format((float)($s['amount'] ?? 0), 2) ?></td>
                                                    <td class="text-success"><?= number_format((float)($s['paid_amount'] ?? 0), 2) ?></td>
                                                    <td>
                                                        <strong class="text-danger">
                                                            <?= number_format((float)($s['remaining_amount'] ?? 0), 2) ?>
                                                        </strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?= e(status_badge($s['status'] ?? '')) ?>">
                                                            <?= e($s['status'] ?? '') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-primary"
                                                                onclick="openPaymentModal(
                                                                    <?= (int)$s['rental_id'] ?>,
                                                                    <?= (int)$s['id'] ?>,
                                                                    <?= number_format((float)$s['remaining_amount'], 2, '.', '') ?>,
                                                                    '<?= e(addslashes($s['contract_no'] ?? '')) ?>',
                                                                    '<?= e(addslashes($s['customer_name'] ?? '')) ?>'
                                                                )">
                                                            💵 <?= e('pay_now') ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- ============================================ -->
                <!-- Tab 3: Upcoming Payments -->
                <!-- ============================================ -->
                <?php if (($filters['tab'] ?? '') === 'upcoming'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>⏰ <?= e('upcoming_payments') ?></h3>
                            <span class="card-subtitle"><?= e('next_30_days') ?></span>
                        </div>
                        <div class="card-body no-padding">
                            <?php if (empty($upcoming)): ?>
                                <div class="empty-state-large">
                                    <div class="empty-icon">✅</div>
                                    <h3><?= e('no_upcoming_payments') ?></h3>
                                </div>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th><?= e('contract_no') ?></th>
                                                <th><?= e('customer') ?></th>
                                                <th><?= e('due_date') ?></th>
                                                <th><?= e('days_remaining') ?></th>
                                                <th><?= e('amount') ?></th>
                                                <th><?= e('actions') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($upcoming as $u): ?>
                                                <tr>
                                                    <td>
                                                        <a href="<?= url('/rentals/' . (int)$u['rental_id']) ?>" class="table-link">
                                                            <code><?= e($u['contract_no'] ?? '') ?></code>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <strong><?= e($u['customer_name'] ?? '') ?></strong>
                                                        <small class="text-muted"><?= e($u['customer_phone'] ?? '') ?></small>
                                                    </td>
                                                    <td><?= e(format_date($u['due_date'] ?? '')) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= ((int)($u['days_remaining'] ?? 0)) <= 3 ? 'warning' : 'info' ?>">
                                                            <?= (int)($u['days_remaining'] ?? 0) ?> <?= e('days') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong><?= number_format((float)($u['amount'] ?? 0), 2) ?></strong> 
                                                        <?= e('sar') ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-primary"
                                                                onclick="openPaymentModal(
                                                                    <?= (int)$u['rental_id'] ?>,
                                                                    <?= (int)$u['id'] ?>,
                                                                    <?= number_format((float)($u['amount'] - $u['paid_amount']), 2, '.', '') ?>,
                                                                    '<?= e(addslashes($u['contract_no'] ?? '')) ?>',
                                                                    '<?= e(addslashes($u['customer_name'] ?? '')) ?>'
                                                                )">
                                                            💵 <?= e('pay_now') ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- ============================================ -->
                <!-- Tab 4: Overdue Payments -->
                <!-- ============================================ -->
                <?php if (($filters['tab'] ?? '') === 'overdue'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>⚠️ <?= e('overdue_payments') ?></h3>
                            <span class="card-subtitle text-danger">
                                <?= count($overdue) ?> <?= e('records') ?> — 
                                <?= number_format($stats['overdue_amount'], 2) ?> <?= e('sar') ?>
                            </span>
                        </div>
                        <div class="card-body no-padding">
                            <?php if (empty($overdue)): ?>
                                <div class="empty-state-large">
                                    <div class="empty-icon">✅</div>
                                    <h3><?= e('no_overdue_payments') ?></h3>
                                    <p><?= e('all_payments_clear') ?></p>
                                </div>
                            <?php else: ?>
                                <div class="table-wrapper">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th><?= e('contract_no') ?></th>
                                                <th><?= e('customer') ?></th>
                                                <th><?= e('car') ?></th>
                                                <th><?= e('due_date') ?></th>
                                                <th><?= e('days_overdue') ?></th>
                                                <th><?= e('remaining') ?></th>
                                                <th><?= e('actions') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($overdue as $o): ?>
                                                <tr class="row-danger">
                                                    <td>
                                                        <a href="<?= url('/rentals/' . (int)$o['rental_id']) ?>" class="table-link">
                                                            <code><?= e($o['contract_no'] ?? '') ?></code>
                                                        </a>
                                                    </td>
                                                    <td>
                                                        <strong><?= e($o['customer_name'] ?? '') ?></strong>
                                                        <small class="text-muted"><?= e($o['customer_phone'] ?? '') ?></small>
                                                    </td>
                                                    <td><?= e($o['plate_number'] ?? '') ?></td>
                                                    <td><?= e(format_date($o['due_date'] ?? '')) ?></td>
                                                    <td>
                                                        <span class="badge badge-danger">
                                                            <?= (int)($o['days_overdue'] ?? 0) ?> <?= e('days') ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <strong class="text-danger">
                                                            <?= number_format((float)($o['remaining_amount'] ?? 0), 2) ?>
                                                        </strong> 
                                                        <?= e('sar') ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-primary"
                                                                onclick="openPaymentModal(
                                                                    <?= (int)$o['rental_id'] ?>,
                                                                    <?= (int)$o['id'] ?>,
                                                                    <?= number_format((float)$o['remaining_amount'], 2, '.', '') ?>,
                                                                    '<?= e(addslashes($o['contract_no'] ?? '')) ?>',
                                                                    '<?= e(addslashes($o['customer_name'] ?? '')) ?>'
                                                                )">
                                                            💵 <?= e('pay_now') ?>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
            </main>
            
            <?php require BASE_PATH . '/app/views/layout/footer.php'; ?>
            
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- Modal إضافة دفعة -->
    <!-- ============================================ -->
    <div class="modal-overlay" id="paymentModal" style="display:none;">
        <div class="modal">
            <form method="POST" action="<?= url('/payments') ?>">
                <?= Csrf::field() ?>
                <input type="hidden" name="rental_id" id="modalRentalId" value="">
                <input type="hidden" name="schedule_id" id="modalScheduleId" value="">
                <input type="hidden" name="return_to" value="payments">
                
                <div class="modal-header">
                    <h3>💵 <?= e('add_payment') ?></h3>
                    <button type="button" class="modal-close" onclick="closePaymentModal()">×</button>
                </div>
                
                <div class="modal-body">
                    <div class="payment-info-box">
                        <div class="info-row">
                            <span><?= e('contract_no') ?>:</span>
                            <strong id="modalContractNo">—</strong>
                        </div>
                        <div class="info-row">
                            <span><?= e('customer') ?>:</span>
                            <strong id="modalCustomer">—</strong>
                        </div>
                        <div class="info-row">
                            <span><?= e('remaining') ?>:</span>
                            <strong class="text-danger" id="modalRemaining">0.00 <?= e('sar') ?></strong>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><?= e('payment_type') ?> <span class="required">*</span></label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="payment_type" value="rent" checked>
                                <span class="radio-custom"></span>
                                <span>💰 <?= e('rent') ?></span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="payment_type" value="penalty">
                                <span class="radio-custom"></span>
                                <span>⚠️ <?= e('penalty') ?></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalAmount">
                            <?= e('payment_amount') ?> 
                            <small class="hint">(<?= e('sar') ?>)</small>
                            <span class="required">*</span>
                        </label>
                        <input type="number" id="modalAmount" name="amount" 
                               step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label><?= e('payment_method') ?> <span class="required">*</span></label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="method" value="cash" checked>
                                <span class="radio-custom"></span>
                                <span>💵 <?= e('cash') ?></span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="method" value="network">
                                <span class="radio-custom"></span>
                                <span>💳 <?= e('network') ?></span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="method" value="bank_transfer">
                                <span class="radio-custom"></span>
                                <span>🏦 <?= e('bank_transfer') ?></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modalReference"><?= e('reference_no') ?></label>
                        <input type="text" id="modalReference" name="reference_no" 
                               maxlength="50"
                               placeholder="<?= e('reference_no_placeholder') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="modalNotes"><?= e('payment_notes') ?></label>
                        <textarea id="modalNotes" name="notes" rows="2" maxlength="255"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closePaymentModal()">
                        <?= e('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💵 <?= e('save_payment') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= url('') ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
        };
        
        function openPaymentModal(rentalId, scheduleId, amount, contractNo, customerName) {
            const modal = document.getElementById('paymentModal');
            
            document.getElementById('modalRentalId').value = rentalId;
            document.getElementById('modalScheduleId').value = scheduleId || '';
            document.getElementById('modalAmount').value = amount.toFixed(2);
            document.getElementById('modalContractNo').textContent = contractNo;
            document.getElementById('modalCustomer').textContent = customerName;
            document.getElementById('modalRemaining').textContent = amount.toFixed(2) + ' <?= e('sar') ?>';
            
            modal.style.display = 'flex';
            
            setTimeout(() => document.getElementById('modalAmount').focus(), 100);
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('paymentModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closePaymentModal();
                });
            }
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closePaymentModal();
            });
        });
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>