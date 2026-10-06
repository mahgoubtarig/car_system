<?php
/**
 * إضافة مصروف جديد
 */
$formData = is_array($old ?? null) ? $old : [];
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('add_expense') ?> | <?= e('app_name') ?></title>
    
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
                
                <div class="page-header">
                    <div>
                        <a href="<?= url('/expenses') ?>" class="back-link">← <?= e('back_to_expenses') ?></a>
                        <h2 class="page-title">💸 <?= e('add_expense') ?></h2>
                        <p class="page-subtitle"><?= e('add_expense_subtitle') ?></p>
                    </div>
                </div>
                
                <?php if (!empty($errors['general']) && is_string($errors['general'])): ?>
                    <div class="alert alert-error"><span><?= e($errors['general']) ?></span></div>
                <?php endif; ?>
                
                <form method="POST" action="<?= url('/expenses') ?>" class="expense-form" novalidate>
                    <?= Csrf::field() ?>
                    
                    <div class="form-sections">
                        
                        <!-- 1. السيارة والتصنيف -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">1</span>
                                <?= e('car_and_category') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="car_id">
                                        <?= e('car') ?> 
                                        <span class="required">*</span>
                                    </label>
                                    <select id="car_id" name="car_id" required
                                            class="<?= !empty($errors['car_id']) ? 'error' : '' ?>">
                                        <option value=""><?= e('select_car') ?></option>
                                        <?php foreach ($cars as $car): ?>
                                            <option value="<?= (int)$car['id'] ?>"
                                                <?= ($formData['car_id'] ?? '') == $car['id'] ? 'selected' : '' ?>>
                                                <?= e($car['plate_number']) ?> — 
                                                <?= e($car['brand']) ?> <?= e($car['model']) ?>
                                                (<?= e((string)$car['year']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($errors['car_id'])): ?>
                                        <span class="form-error"><?= e($errors['car_id']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="category_id">
                                        <?= e('category') ?> 
                                        <span class="required">*</span>
                                    </label>
                                    <select id="category_id" name="category_id" required
                                            class="<?= !empty($errors['category_id']) ? 'error' : '' ?>">
                                        <option value=""><?= e('select_category') ?></option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?= (int)$cat['id'] ?>"
                                                <?= ($formData['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                                <?= e(Lang::isArabic() ? $cat['name_ar'] : $cat['name_en']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (!empty($errors['category_id'])): ?>
                                        <span class="form-error"><?= e($errors['category_id']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 2. تفاصيل المصروف -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">2</span>
                                <?= e('expense_details') ?>
                            </h3>
                            
                            <div class="form-group">
                                <label for="description">
                                    <?= e('description') ?> 
                                    <span class="required">*</span>
                                </label>
                                <input type="text" 
                                       id="description" 
                                       name="description" 
                                       value="<?= e($formData['description'] ?? '') ?>"
                                       maxlength="255"
                                       required
                                       placeholder="<?= e('description_placeholder') ?>"
                                       class="<?= !empty($errors['description']) ? 'error' : '' ?>">
                                <?php if (!empty($errors['description'])): ?>
                                    <span class="form-error"><?= e($errors['description']) ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="amount">
                                        <?= e('amount') ?> 
                                        <small class="hint">(<?= e('sar') ?>)</small>
                                        <span class="required">*</span>
                                    </label>
                                    <input type="number" 
                                           id="amount" 
                                           name="amount" 
                                           value="<?= e($formData['amount'] ?? '') ?>"
                                           step="0.01" 
                                           min="0.01"
                                           required
                                           class="<?= !empty($errors['amount']) ? 'error' : '' ?>">
                                    <?php if (!empty($errors['amount'])): ?>
                                        <span class="form-error"><?= e($errors['amount']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="expense_date">
                                        <?= e('expense_date') ?> 
                                        <span class="required">*</span>
                                    </label>
                                    <input type="date" 
                                           id="expense_date" 
                                           name="expense_date" 
                                           value="<?= e($formData['expense_date'] ?? date('Y-m-d')) ?>"
                                           required
                                           class="<?= !empty($errors['expense_date']) ? 'error' : '' ?>">
                                    <?php if (!empty($errors['expense_date'])): ?>
                                        <span class="form-error"><?= e($errors['expense_date']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 3. معلومات الفاتورة -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">3</span>
                                <?= e('invoice_info') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="vendor"><?= e('vendor') ?></label>
                                    <input type="text" 
                                           id="vendor" 
                                           name="vendor" 
                                           value="<?= e($formData['vendor'] ?? '') ?>"
                                           maxlength="100"
                                           placeholder="<?= e('vendor_placeholder') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="invoice_no"><?= e('invoice_no') ?></label>
                                    <input type="text" 
                                           id="invoice_no" 
                                           name="invoice_no" 
                                           value="<?= e($formData['invoice_no'] ?? '') ?>"
                                           maxlength="40">
                                </div>
                                
                                <div class="form-group">
                                    <label for="odometer_at">
                                        <?= e('odometer_at') ?> 
                                        <small class="hint">(كم)</small>
                                    </label>
                                    <input type="number" 
                                           id="odometer_at" 
                                           name="odometer_at" 
                                           value="<?= e($formData['odometer_at'] ?? '') ?>"
                                           min="0">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 4. الصيانة القادمة -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">4</span>
                                <?= e('next_maintenance') ?>
                            </h3>
                            
                            <div class="info-box">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span><?= e('next_maintenance_hint') ?></span>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="next_service_date"><?= e('next_service_date') ?></label>
                                    <input type="date" 
                                           id="next_service_date" 
                                           name="next_service_date" 
                                           value="<?= e($formData['next_service_date'] ?? '') ?>"
                                           min="<?= date('Y-m-d') ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="next_service_km">
                                        <?= e('next_service_km') ?> 
                                        <small class="hint">(كم)</small>
                                    </label>
                                    <input type="number" 
                                           id="next_service_km" 
                                           name="next_service_km" 
                                           value="<?= e($formData['next_service_km'] ?? '') ?>"
                                           min="0">
                                </div>
                            </div>
                        </div>
                        
                    </div>
                    
                    <!-- Actions -->
                    <div class="form-actions">
                        <a href="<?= url('/expenses') ?>" class="btn btn-secondary">
                            <?= e('cancel') ?>
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <?= e('save_expense') ?>
                        </button>
                    </div>
                </form>
                
            </main>
            
            <?php require BASE_PATH . '/app/views/layout/footer.php'; ?>
            
        </div>
    </div>
    
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= url('') ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
        };
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>