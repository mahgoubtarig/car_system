<?php
/**
 * قائمة المستأجرين
 */
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('customers') ?> | <?= e('app_name') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>👥</text></svg>">
    
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
                
                <!-- Header -->
                <div class="page-header">
                    <div>
                        <h2 class="page-title"><?= e('customers') ?></h2>
                        <p class="page-subtitle"><?= e('customers_subtitle') ?></p>
                    </div>
                    <div class="page-actions">
                        <a href="<?= url('/customers/create') ?>" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <?= e('add_customer') ?>
                        </a>
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
                <div class="stats-grid stats-grid-compact">
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm">👥</div>
                        <div>
                            <span class="stat-label-sm"><?= e('total_customers') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['total'] ?? 0)) ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--info-color);color:white;">🔑</div>
                        <div>
                            <span class="stat-label-sm"><?= e('with_active_rentals') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['with_active'] ?? 0)) ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--warning-light);">⚠️</div>
                        <div>
                            <span class="stat-label-sm"><?= e('expired_ids') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['expired_ids'] ?? 0)) ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--danger-light);">🚫</div>
                        <div>
                            <span class="stat-label-sm"><?= e('blacklisted') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['blacklisted'] ?? 0)) ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card filters-card">
                    <form method="GET" action="<?= url('/customers') ?>" class="filters-form">
                        <div class="filter-group">
                            <label><?= e('search') ?></label>
                            <input type="text" name="search" 
                                   value="<?= e($filters['search'] ?? '') ?>" 
                                   placeholder="<?= e('search_customers_placeholder') ?>">
                        </div>
                        
                        <div class="filter-group">
                            <label><?= e('id_type') ?></label>
                            <select name="id_type">
                                <option value=""><?= e('all') ?></option>
                                <option value="national_id" <?= ($filters['id_type'] ?? '') === 'national_id' ? 'selected' : '' ?>>
                                    <?= e('national_id') ?>
                                </option>
                                <option value="iqama" <?= ($filters['id_type'] ?? '') === 'iqama' ? 'selected' : '' ?>>
                                    <?= e('iqama') ?>
                                </option>
                                <option value="passport" <?= ($filters['id_type'] ?? '') === 'passport' ? 'selected' : '' ?>>
                                    <?= e('passport') ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label><?= e('city') ?></label>
                            <select name="city">
                                <option value=""><?= e('all') ?></option>
                                <?php if (!empty($cities)): ?>
                                    <?php foreach ($cities as $c): ?>
                                        <option value="<?= e($c['city']) ?>" <?= ($filters['city'] ?? '') === $c['city'] ? 'selected' : '' ?>>
                                            <?= e($c['city']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label><?= e('blacklist') ?></label>
                            <select name="is_blacklisted">
                                <option value=""><?= e('all') ?></option>
                                <option value="1" <?= ($filters['is_blacklisted'] ?? '') === '1' ? 'selected' : '' ?>>
                                    <?= e('blacklisted_only') ?>
                                </option>
                                <option value="0" <?= ($filters['is_blacklisted'] ?? '') === '0' ? 'selected' : '' ?>>
                                    <?= e('not_blacklisted') ?>
                                </option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                </svg>
                                <?= e('search') ?>
                            </button>
                            <a href="<?= url('/customers') ?>" class="btn btn-secondary">
                                <?= e('reset') ?>
                            </a>
                        </div>
                    </form>
                </div>
                
                <!-- Table -->
                <div class="card">
                    <div class="card-body no-padding">
                        <?php if (empty($customers)): ?>
                            <div class="empty-state-large">
                                <div class="empty-icon">👥</div>
                                <h3><?= e('no_customers') ?></h3>
                                <p><?= e('no_customers_subtitle') ?></p>
                                <a href="<?= url('/customers/create') ?>" class="btn btn-primary">
                                    <?= e('add_first_customer') ?>
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?= e('customer') ?></th>
                                            <th><?= e('id_number') ?></th>
                                            <th><?= e('phone') ?></th>
                                            <th><?= e('city') ?></th>
                                            <th><?= e('rentals') ?></th>
                                            <th><?= e('status') ?></th>
                                            <th><?= e('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($customers as $cu): ?>
                                            <tr>
                                                <td>
                                                    <div class="customer-cell">
                                                        <div class="customer-avatar">
                                                            <?= e(initials($cu['full_name'] ?? '?')) ?>
                                                        </div>
                                                        <div>
                                                            <strong><?= e($cu['full_name'] ?? '') ?></strong>
                                                            <small><?= e($cu['nationality'] ?? '') ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <code><?= e($cu['id_number'] ?? '') ?></code>
                                                </td>
                                                <td>
                                                    <?php if (!empty($cu['phone'])): ?>
                                                        <a href="tel:<?= e($cu['phone']) ?>" class="table-link">
                                                            <?= e($cu['phone']) ?>
                                                        </a>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= e($cu['city'] ?? '—') ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?= (int)($cu['total_rentals'] ?? 0) ?>
                                                    </span>
                                                    <?php if ((int)($cu['active_rentals'] ?? 0) > 0): ?>
                                                        <span class="badge badge-success">
                                                            <?= (int)$cu['active_rentals'] ?> <?= e('active') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($cu['is_blacklisted'])): ?>
                                                        <span class="badge badge-danger">🚫 <?= e('blacklisted') ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">✅ <?= e('active') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="actions">
                                                        <!-- عرض -->
                                                        <a href="<?= url('/customers/' . (int)$cu['id']) ?>" 
                                                           class="btn-icon" 
                                                           title="<?= e('view') ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                                <circle cx="12" cy="12" r="3"></circle>
                                                            </svg>
                                                        </a>
                                                        
                                                        <!-- تعديل -->
                                                        <a href="<?= url('/customers/' . (int)$cu['id'] . '/edit') ?>" 
                                                           class="btn-icon" 
                                                           title="<?= e('edit') ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                            </svg>
                                                        </a>
                                                        
                                                        <!-- ✅ حذف (للمدير فقط) -->
                                                        <?php if (Auth::isAdmin()): ?>
                                                            <button type="button" 
                                                                    class="btn-icon btn-icon-danger" 
                                                                    title="<?= e('delete') ?>"
                                                                    onclick="confirmDeleteCustomer(<?= (int)$cu['id'] ?>, '<?= e(addslashes($cu['full_name'] ?? '')) ?>')">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                    <polyline points="3 6 5 6 21 6"></polyline>
                                                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                                                    <line x1="10" y1="11" x2="10" y2="17"></line>
                                                                    <line x1="14" y1="11" x2="14" y2="17"></line>
                                                                </svg>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
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
                                        <?= e('showing') ?> <?= count($customers) ?> <?= e('of') ?> <?= (int)$total ?>
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
                <p class="delete-customer-name" id="deleteCustomerName"></p>
                <p class="text-danger"><small>⚠️ <?= e('delete_warning') ?></small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <?= e('cancel') ?>
                </button>
                <form method="POST" id="deleteForm" style="display:inline-block;">
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
            locale: '<?= e(Lang::current()) ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
        };
        
        function confirmDeleteCustomer(customerId, customerName) {
            const modal = document.getElementById('deleteModal');
            const form = document.getElementById('deleteForm');
            const nameEl = document.getElementById('deleteCustomerName');
            
            form.action = window.APP_CONFIG.baseUrl + '/customers/' + customerId + '/delete';
            nameEl.textContent = customerName;
            modal.style.display = 'flex';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // إغلاق عند النقر خارج المودال
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('deleteModal');
            if (modal) {
                modal.addEventListener('click', function(e) {
                    if (e.target === this) {
                        closeDeleteModal();
                    }
                });
            }
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeDeleteModal();
                }
            });
        });
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>