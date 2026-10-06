<?php
/**
 * تفاصيل المصروف
 */
if (empty($expense) || !is_array($expense)) {
    header('Location: ' . url('/expenses'));
    exit;
}

$expenseId = (int) ($expense['id'] ?? 0);
$carId = (int) ($expense['car_id'] ?? 0);
$carExpenses = is_array($carExpenses ?? null) ? $carExpenses : [];
$carTotalExpenses = (float) ($carTotalExpenses ?? 0);
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($expense['description'] ?? '') ?> | <?= e('app_name') ?></title>
    
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
                
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="<?= url('/expenses') ?>"><?= e('expenses') ?></a>
                    <span>›</span>
                    <span><?= e($expense['description'] ?? '') ?></span>
                </div>
                
                <!-- Alerts -->
                <?php $successMsg = Auth::getFlash('success'); ?>
                <?php if (!empty($successMsg) && is_string($successMsg)): ?>
                    <div class="alert alert-success"><span><?= e($successMsg) ?></span></div>
                <?php endif; ?>
                
                <?php $errorMsg = Auth::getFlash('error'); ?>
                <?php if (!empty($errorMsg) && is_string($errorMsg)): ?>
                    <div class="alert alert-error"><span><?= e($errorMsg) ?></span></div>
                <?php endif; ?>
                
                <!-- Expense Header -->
                <div class="rental-detail-header">
                    <div class="rental-icon-large" style="background: linear-gradient(135deg, var(--danger-color), #b91c1c);">
                        💸
                    </div>
                    
                    <div class="rental-detail-info">
                        <div class="rental-title-row">
                            <h1><?= e($expense['description'] ?? '') ?></h1>
                            <span class="badge badge-info">
                                <?= e(Lang::isArabic() 
                                    ? ($expense['category_name_ar'] ?? '') 
                                    : ($expense['category_name_en'] ?? '')) ?>
                            </span>
                        </div>
                        
                        <p class="rental-subtitle">
                            <strong><?= e($expense['plate_number'] ?? '') ?></strong>
                            <span>•</span>
                            <span><?= e($expense['brand'] ?? '') ?> <?= e($expense['model'] ?? '') ?></span>
                            <span>•</span>
                            <strong class="text-danger"><?= number_format((float)($expense['amount'] ?? 0), 2) ?> <?= e('sar') ?></strong>
                        </p>
                        
                        <div class="rental-quick-info">
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('expense_date') ?></span>
                                <strong><?= e(format_date($expense['expense_date'] ?? '')) ?></strong>
                            </div>
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('vendor') ?></span>
                                <strong><?= e($expense['vendor'] ?? '—') ?></strong>
                            </div>
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('invoice_no') ?></span>
                                <strong><?= e($expense['invoice_no'] ?? '—') ?></strong>
                            </div>
                        </div>
                        
                        <div class="car-actions">
                            <a href="<?= url('/expenses/' . $expenseId . '/edit') ?>" class="btn btn-primary">
                                ✏️ <?= e('edit') ?>
                            </a>
                            
                            <?php if (Auth::isAdmin()): ?>
                                <button type="button" 
                                        class="btn btn-danger"
                                        onclick="showDeleteModal()">
                                    🗑️ <?= e('delete') ?>
                                </button>
                            <?php endif; ?>
                            
                            <a href="<?= url('/cars/' . $carId) ?>" class="btn btn-secondary">
                                🚗 <?= e('view_car') ?>
                            </a>
                            
                            <a href="<?= url('/expenses') ?>" class="btn btn-secondary">
                                ← <?= e('back') ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Financial Stats -->
                <div class="stats-grid">
                    <div class="stat-card stat-danger">
                        <div class="stat-icon">💸</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('amount') ?></span>
                            <strong class="stat-value"><?= number_format((float)($expense['amount'] ?? 0), 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">🚗</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_car_expenses') ?></span>
                            <strong class="stat-value"><?= number_format($carTotalExpenses, 2) ?> <?= e('sar') ?></strong>
                            <span class="stat-detail"><?= e('for_this_car') ?></span>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-info">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('records') ?></span>
                            <strong class="stat-value"><?= count($carExpenses) ?></strong>
                            <span class="stat-detail"><?= e('for_this_car') ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Details Grid -->
                <div class="grid-2">
                    
                    <!-- معلومات المصروف -->
                    <div class="card">
                        <div class="card-header">
                            <h3>📋 <?= e('expense_details') ?></h3>
                        </div>
                        <div class="card-body">
                            <table class="info-table">
                                <tr>
                                    <th><?= e('car') ?></th>
                                    <td>
                                        <a href="<?= url('/cars/' . $carId) ?>" class="table-link">
                                            <?= e($expense['plate_number'] ?? '') ?>
                                        </a>
                                        — <?= e($expense['brand'] ?? '') ?> <?= e($expense['model'] ?? '') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?= e('category') ?></th>
                                    <td>
                                        <?= e(Lang::isArabic() 
                                            ? ($expense['category_name_ar'] ?? '') 
                                            : ($expense['category_name_en'] ?? '')) ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?= e('description') ?></th>
                                    <td><?= e($expense['description'] ?? '') ?></td>
                                </tr>
                                <tr>
                                    <th><?= e('amount') ?></th>
                                    <td>
                                        <strong class="text-danger">
                                            <?= number_format((float)($expense['amount'] ?? 0), 2) ?> <?= e('sar') ?>
                                        </strong>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?= e('expense_date') ?></th>
                                    <td><?= e(format_date($expense['expense_date'] ?? '')) ?></td>
                                </tr>
                                <tr>
                                    <th><?= e('vendor') ?></th>
                                    <td><?= e($expense['vendor'] ?? '—') ?></td>
                                </tr>
                                <tr>
                                    <th><?= e('invoice_no') ?></th>
                                    <td><?= e($expense['invoice_no'] ?? '—') ?></td>
                                </tr>
                                <tr>
                                    <th><?= e('odometer_at') ?></th>
                                    <td>
                                        <?php if (!empty($expense['odometer_at'])): ?>
                                            <?= number_format((int)$expense['odometer_at']) ?> كم
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?= e('added_by') ?></th>
                                    <td><?= e($expense['created_by_name'] ?? '—') ?></td>
                                </tr>
                                <tr>
                                    <th><?= e('created_at') ?></th>
                                    <td><?= e(format_datetime($expense['created_at'] ?? '')) ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- الصيانة القادمة -->
                    <div class="card">
                        <div class="card-header">
                            <h3>🔧 <?= e('next_maintenance') ?></h3>
                        </div>
                        <div class="card-body">
                            <?php if (empty($expense['next_service_date']) && empty($expense['next_service_km'])): ?>
                                <p class="empty-state"><?= e('no_next_maintenance') ?></p>
                            <?php else: ?>
                                <table class="info-table">
                                    <?php if (!empty($expense['next_service_date'])): ?>
                                        <tr>
                                            <th><?= e('next_service_date') ?></th>
                                            <td>
                                                <strong class="text-warning">
                                                    <?= e(format_date($expense['next_service_date'])) ?>
                                                </strong>
                                                <?php 
                                                $daysToService = (int) floor((strtotime($expense['next_service_date']) - time()) / 86400);
                                                if ($daysToService < 0): 
                                                ?>
                                                    <br><small class="text-danger">
                                                        (<?= abs($daysToService) ?> <?= e('days_late') ?>)
                                                    </small>
                                                <?php elseif ($daysToService <= 7): ?>
                                                    <br><small class="text-warning">
                                                        (<?= $daysToService ?> <?= e('days_remaining') ?>)
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($expense['next_service_km'])): ?>
                                        <tr>
                                            <th><?= e('next_service_km') ?></th>
                                            <td><?= number_format((int)$expense['next_service_km']) ?> كم</td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            <?php endif; ?>
                            
                            <?php if (!empty($expense['invoice_image'])): ?>
                                <div style="margin-top:20px;">
                                    <h4 style="font-size:0.9rem; margin-bottom:10px;"><?= e('invoice_image') ?></h4>
                                    <img src="<?= url('/storage/uploads/expenses/' . $expense['invoice_image']) ?>" 
                                         alt="Invoice" 
                                         style="max-width:100%; border-radius:var(--radius-md); border:1px solid var(--border-color);">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Car Expenses History -->
                <?php if (!empty($carExpenses)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>🚗 <?= e('car_expenses_history') ?></h3>
                            <a href="<?= url('/cars/' . $carId) ?>" class="card-link">
                                <?= e('view_car') ?> →
                            </a>
                        </div>
                        <div class="card-body no-padding">
                            <div class="table-wrapper">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?= e('date') ?></th>
                                            <th><?= e('category') ?></th>
                                            <th><?= e('description') ?></th>
                                            <th><?= e('amount') ?></th>
                                            <th><?= e('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($carExpenses as $ce): ?>
                                            <tr class="<?= (int)$ce['id'] === $expenseId ? 'row-active' : '' ?>">
                                                <td><?= e(format_date($ce['expense_date'] ?? '')) ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?= e(Lang::isArabic() 
                                                            ? ($ce['category_name_ar'] ?? '') 
                                                            : ($ce['category_name_en'] ?? '')) ?>
                                                    </span>
                                                </td>
                                                <td><?= e($ce['description'] ?? '') ?></td>
                                                <td>
                                                    <strong><?= number_format((float)($ce['amount'] ?? 0), 2) ?></strong> 
                                                    <?= e('sar') ?>
                                                </td>
                                                <td>
                                                    <a href="<?= url('/expenses/' . (int)$ce['id']) ?>" 
                                                       class="btn-icon" title="<?= e('view') ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                            <circle cx="12" cy="12" r="3"></circle>
                                                        </svg>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
            </main>
            
            <?php require BASE_PATH . '/app/views/layout/footer.php'; ?>
            
        </div>
    </div>
    
    <!-- Modal حذف -->
    <?php if (Auth::isAdmin()): ?>
        <div class="modal-overlay" id="deleteModal" style="display:none;">
            <div class="modal modal-sm">
                <form method="POST" action="<?= url('/expenses/' . $expenseId . '/delete') ?>">
                    <?= Csrf::field() ?>
                    
                    <div class="modal-header">
                        <h3 class="text-danger">🗑️ <?= e('confirm_delete') ?></h3>
                        <button type="button" class="modal-close" onclick="hideDeleteModal()">×</button>
                    </div>
                    
                    <div class="modal-body">
                        <p><?= e('confirm_delete_expense') ?></p>
                        <p class="delete-customer-name">
                            <strong><?= e($expense['description'] ?? '') ?></strong>
                        </p>
                        <p class="text-danger"><small>⚠️ <?= e('delete_warning') ?></small></p>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="hideDeleteModal()">
                            <?= e('cancel') ?>
                        </button>
                        <button type="submit" class="btn btn-danger">
                            🗑️ <?= e('delete') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= url('') ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
        };
        
        function showDeleteModal() {
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function hideDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) hideDeleteModal();
                });
            }
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') hideDeleteModal();
            });
        });
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>