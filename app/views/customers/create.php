<?php
/**
 * إضافة مستأجر جديد
 */
$formData = is_array($old ?? null) ? $old : [];
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('add_customer') ?> | <?= e('app_name') ?></title>
    
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
                
                <div class="page-header">
                    <div>
                        <a href="<?= url('/customers') ?>" class="back-link">← <?= e('back_to_customers') ?></a>
                        <h2 class="page-title"><?= e('add_customer') ?></h2>
                        <p class="page-subtitle"><?= e('add_customer_subtitle') ?></p>
                    </div>
                </div>
                
                <?php if (!empty($errors['general']) && is_string($errors['general'])): ?>
                    <div class="alert alert-error">
                        <span><?= e($errors['general']) ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?= url('/customers') ?>" class="customer-form" novalidate>
                    <?= Csrf::field() ?>
                    
                    <div class="form-sections">
                        
                        <!-- 1. البيانات الأساسية -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">1</span>
                                <?= e('basic_info') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full_name"><?= e('full_name') ?> <span class="required">*</span></label>
                                    <input type="text" id="full_name" name="full_name" 
                                           value="<?= e($formData['full_name'] ?? '') ?>"
                                           maxlength="120" required autofocus
                                           class="<?= !empty($errors['full_name']) ? 'error' : '' ?>">
                                    <?php if (!empty($errors['full_name'])): ?>
                                        <span class="form-error"><?= e($errors['full_name']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="nationality"><?= e('nationality') ?></label>
                                    <input type="text" id="nationality" name="nationality" 
                                           value="<?= e($formData['nationality'] ?? '') ?>"
                                           maxlength="50">
                                </div>
                                
                                <div class="form-group">
                                    <label for="birth_date"><?= e('birth_date') ?></label>
                                    <input type="date" id="birth_date" name="birth_date" 
                                           value="<?= e($formData['birth_date'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 2. الهوية -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">2</span>
                                <?= e('identification') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="id_type"><?= e('id_type') ?> <span class="required">*</span></label>
                                    <select id="id_type" name="id_type" required
                                            class="<?= !empty($errors['id_type']) ? 'error' : '' ?>">
                                        <option value="national_id" <?= ($formData['id_type'] ?? 'national_id') === 'national_id' ? 'selected' : '' ?>>
                                            <?= e('national_id') ?>
                                        </option>
                                        <option value="iqama" <?= ($formData['id_type'] ?? '') === 'iqama' ? 'selected' : '' ?>>
                                            <?= e('iqama') ?>
                                        </option>
                                        <option value="passport" <?= ($formData['id_type'] ?? '') === 'passport' ? 'selected' : '' ?>>
                                            <?= e('passport') ?>
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="id_number"><?= e('id_number') ?> <span class="required">*</span></label>
                                    <input type="text" id="id_number" name="id_number" 
                                           value="<?= e($formData['id_number'] ?? '') ?>"
                                           maxlength="30" required
                                           class="<?= !empty($errors['id_number']) ? 'error' : '' ?>">
                                    <?php if (!empty($errors['id_number'])): ?>
                                        <span class="form-error"><?= e($errors['id_number']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="id_expiry"><?= e('id_expiry') ?></label>
                                    <input type="date" id="id_expiry" name="id_expiry" 
                                           value="<?= e($formData['id_expiry'] ?? '') ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="license_number"><?= e('license_number') ?></label>
                                    <input type="text" id="license_number" name="license_number" 
                                           value="<?= e($formData['license_number'] ?? '') ?>"
                                           maxlength="30">
                                </div>
                                
                                <div class="form-group">
                                    <label for="license_expiry"><?= e('license_expiry') ?></label>
                                    <input type="date" id="license_expiry" name="license_expiry" 
                                           value="<?= e($formData['license_expiry'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 3. التواصل -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">3</span>
                                <?= e('contact_info') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="phone"><?= e('phone') ?> <span class="required">*</span></label>
                                    <input type="tel" id="phone" name="phone" 
                                           value="<?= e($formData['phone'] ?? '') ?>"
                                           maxlength="20" required
                                           placeholder="05xxxxxxxx"
                                           class="<?= !empty($errors['phone']) ? 'error' : '' ?>">
                                    <?php if (!empty($errors['phone'])): ?>
                                        <span class="form-error"><?= e($errors['phone']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="phone2"><?= e('phone2') ?></label>
                                    <input type="tel" id="phone2" name="phone2" 
                                           value="<?= e($formData['phone2'] ?? '') ?>"
                                           maxlength="20">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email"><?= e('email') ?></label>
                                    <input type="email" id="email" name="email" 
                                           value="<?= e($formData['email'] ?? '') ?>"
                                           maxlength="100">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="city"><?= e('city') ?></label>
                                    <input type="text" id="city" name="city" 
                                           value="<?= e($formData['city'] ?? '') ?>"
                                           maxlength="60">
                                </div>
                                
                                <div class="form-group" style="grid-column: span 2;">
                                    <label for="address"><?= e('address') ?></label>
                                    <input type="text" id="address" name="address" 
                                           value="<?= e($formData['address'] ?? '') ?>"
                                           maxlength="255">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 4. إضافي -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">4</span>
                                <?= e('additional_info') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="sponsor_or_employer"><?= e('sponsor_or_employer') ?></label>
                                    <input type="text" id="sponsor_or_employer" name="sponsor_or_employer" 
                                           value="<?= e($formData['sponsor_or_employer'] ?? '') ?>"
                                           maxlength="120">
                                </div>
                                
                                <div class="form-group">
                                    <label for="emergency_contact"><?= e('emergency_contact') ?></label>
                                    <input type="text" id="emergency_contact" name="emergency_contact" 
                                           value="<?= e($formData['emergency_contact'] ?? '') ?>"
                                           maxlength="150">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes"><?= e('notes') ?></label>
                                <textarea id="notes" name="notes" rows="3" maxlength="1000"><?= e($formData['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="form-actions">
                        <a href="<?= url('/customers') ?>" class="btn btn-secondary">
                            <?= e('cancel') ?>
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <?= e('save_customer') ?>
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