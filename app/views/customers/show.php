<?php
/**
 * تفاصيل المستأجر
 */
if (empty($customer) || !is_array($customer)) {
    header('Location: ' . url('/customers'));
    exit;
}

$customerId = (int) ($customer['id'] ?? 0);
$rentals = is_array($rentals ?? null) ? $rentals : [];
$totalPaid = (float) ($totalPaid ?? 0);
$totalContracted = (float) ($totalContracted ?? 0);
$remaining = (float) ($remaining ?? 0);
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($customer['full_name'] ?? '') ?> | <?= e('app_name') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👤</text></svg>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/cars.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/customers.css') ?>">
    
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
                    <a href="<?= url('/customers') ?>"><?= e('customers') ?></a>
                    <span>›</span>
                    <span><?= e($customer['full_name'] ?? '') ?></span>
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
                
                <!-- Customer Header -->
                <div class="customer-detail-header">
                    <div class="customer-avatar-large">
                        <?= e(initials($customer['full_name'] ?? '?', 2)) ?>
                    </div>
                    
                    <div class="customer-detail-info">
                        <div class="customer-title-row">
                            <h1><?= e($customer['full_name'] ?? '') ?></h1>
                            <?php if (!empty($customer['is_blacklisted'])): ?>
                                <span class="badge badge-danger">🚫 <?= e('blacklisted') ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <p class="customer-id">
                            <code><?= e($customer['id_number'] ?? '') ?></code>
                            <span>•</span>
                            <span><?= e($customer['nationality'] ?? '') ?></span>
                        </p>
                        
                        <div class="customer-quick-info">
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('phone') ?></span>
                                <strong><?= e($customer['phone'] ?? '—') ?></strong>
                            </div>
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('city') ?></span>
                                <strong><?= e($customer['city'] ?? '—') ?></strong>
                            </div>
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('id_expiry') ?></span>
                                <strong><?= e(format_date($customer['id_expiry'] ?? '')) ?></strong>
                            </div>
                        </div>
                        
                        <div class="car-actions">
                            <!-- تعديل -->
                            <a href="<?= url('/customers/' . $customerId . '/edit') ?>" class="btn btn-primary">
                                ✏️ <?= e('edit') ?>
                            </a>
                            
                            <!-- القائمة السوداء (للمدير فقط) -->
                            <?php if (Auth::isAdmin()): ?>
                                <form method="POST" 
                                      action="<?= url('/customers/' . $customerId . '/blacklist') ?>" 
                                      style="display:inline-block;"
                                      onsubmit="return confirm('<?= e('confirm_blacklist_toggle') ?>');">
                                    <?= Csrf::field() ?>
                                    <?php if (empty($customer['is_blacklisted'])): ?>
                                        <input type="hidden" name="reason" value="إضافة إدارية">
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-<?= !empty($customer['is_blacklisted']) ? 'success' : 'warning' ?>">
                                        <?= !empty($customer['is_blacklisted']) ? '✅ ' . e('remove_from_blacklist') : '🚫 ' . e('add_to_blacklist') ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <!-- ✅ حذف (للمدير فقط) -->
                            <?php if (Auth::isAdmin()): ?>
                                <button type="button" 
                                        class="btn btn-danger"
                                        onclick="confirmDeleteCustomer(<?= $customerId ?>, '<?= e(addslashes($customer['full_name'] ?? '')) ?>')">
                                    🗑️ <?= e('delete') ?>
                                </button>
                            <?php endif; ?>
                            
                            <!-- رجوع -->
                            <a href="<?= url('/customers') ?>" class="btn btn-secondary">
                                ← <?= e('back') ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Financial Stats -->
                <div class="stats-grid">
                    <div class="stat-card stat-success">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_contracted') ?></span>
                            <strong class="stat-value"><?= number_format($totalContracted, 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-info">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_paid') ?></span>
                            <strong class="stat-value"><?= number_format($totalPaid, 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-<?= $remaining <= 0 ? 'success' : 'danger' ?>">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('remaining') ?></span>
                            <strong class="stat-value"><?= number_format($remaining, 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Details Grid -->
                <div class="grid-2">
                    
                    <!-- معلومات شخصية -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= e('personal_info') ?></h3>
                        </div>
                        <div class="card-body">
                            <table class="info-table">
                                <tr><th><?= e('full_name') ?></th><td><?= e($customer['full_name'] ?? '') ?></td></tr>
                                <tr><th><?= e('nationality') ?></th><td><?= e($customer['nationality'] ?? '—') ?></td></tr>
                                <tr><th><?= e('birth_date') ?></th><td><?= e(format_date($customer['birth_date'] ?? '')) ?></td></tr>
                                <tr><th><?= e('id_type') ?></th><td><?= e($customer['id_type'] ?? '') ?></td></tr>
                                <tr><th><?= e('id_number') ?></th><td><code><?= e($customer['id_number'] ?? '') ?></code></td></tr>
                                <tr><th><?= e('id_expiry') ?></th><td><?= e(format_date($customer['id_expiry'] ?? '')) ?></td></tr>
                                <tr><th><?= e('license_number') ?></th><td><?= e($customer['license_number'] ?? '—') ?></td></tr>
                                <tr><th><?= e('license_expiry') ?></th><td><?= e(format_date($customer['license_expiry'] ?? '')) ?></td></tr>
                                <tr><th><?= e('sponsor_or_employer') ?></th><td><?= e($customer['sponsor_or_employer'] ?? '—') ?></td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- معلومات التواصل -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= e('contact_info') ?></h3>
                        </div>
                        <div class="card-body">
                            <table class="info-table">
                                <tr><th><?= e('phone') ?></th><td><a href="tel:<?= e($customer['phone'] ?? '') ?>"><?= e($customer['phone'] ?? '—') ?></a></td></tr>
                                <tr><th><?= e('phone2') ?></th><td><?= e($customer['phone2'] ?? '—') ?></td></tr>
                                <tr><th><?= e('email') ?></th><td><?= e($customer['email'] ?? '—') ?></td></tr>
                                <tr><th><?= e('city') ?></th><td><?= e($customer['city'] ?? '—') ?></td></tr>
                                <tr><th><?= e('address') ?></th><td><?= e($customer['address'] ?? '—') ?></td></tr>
                                <tr><th><?= e('emergency_contact') ?></th><td><?= e($customer['emergency_contact'] ?? '—') ?></td></tr>
                                <?php if (!empty($customer['notes'])): ?>
                                    <tr><th><?= e('notes') ?></th><td><?= e($customer['notes']) ?></td></tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Rental History -->
                <div class="card">
                    <div class="card-header">
                        <h3><?= e('rental_history') ?></h3>
                        <span class="card-subtitle"><?= count($rentals) ?> <?= e('records') ?></span>
                    </div>
                    <div class="card-body no-padding">
                        <?php if (empty($rentals)): ?>
                            <p class="empty-state"><?= e('no_rentals') ?></p>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?= e('contract_no') ?></th>
                                            <th><?= e('car') ?></th>
                                            <th><?= e('pickup_datetime') ?></th>
                                            <th><?= e('return_due_datetime') ?></th>
                                            <th><?= e('total_amount') ?></th>
                                            <th><?= e('status') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rentals as $r): ?>
                                            <tr>
                                                <td><code><?= e($r['contract_no'] ?? '') ?></code></td>
                                                <td><?= e($r['plate_number'] ?? '') ?> - <?= e($r['brand'] ?? '') ?></td>
                                                <td><?= e(format_date($r['pickup_datetime'] ?? '')) ?></td>
                                                <td><?= e(format_date($r['return_due_datetime'] ?? '')) ?></td>
                                                <td><strong><?= number_format((float)($r['total_amount'] ?? 0), 2) ?></strong> <?= e('sar') ?></td>
                                                <td>
                                                    <span class="badge badge-<?= e(status_badge($r['status'] ?? '')) ?>">
                                                        <?= e($r['status'] ?? '') ?>
                                                    </span>
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
    
    <!-- ============================================ -->
    <!-- Modal تأكيد الحذف -->
    <!-- ============================================ -->
    <div class="modal-overlay" id="deleteModal" style="display:none;">
        <div class="modal modal-sm">
            <div class="modal-header">
                <h3 class="text-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:inline-block;vertical-align:middle;margin-inline-end:6px;">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                    <?= e('confirm_delete') ?>
                </h3>
                <button type="button" class="modal-close" onclick="closeDeleteModal()">×</button>
            </div>
            <div class="modal-body">
                <p><?= e('confirm_delete_customer_msg') ?></p>
                <p class="delete-customer-name"><strong><?= e($customer['full_name'] ?? '') ?></strong></p>
                <p class="text-danger"><small>⚠️ <?= e('delete_warning') ?></small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <?= e('cancel') ?>
                </button>
                <form method="POST" action="<?= url('/customers/' . $customerId . '/delete') ?>" style="display:inline-block;">
                    <?= Csrf::field() ?>
                    <button type="submit" class="btn btn-danger">
                        🗑️ <?= e('delete') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= url('') ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
        };
        
        function confirmDeleteCustomer(customerId, customerName) {
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) closeDeleteModal();
                });
            }
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') closeDeleteModal();
            });
        });
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>