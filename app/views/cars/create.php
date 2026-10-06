<?php
/**
 * إضافة سيارة جديدة
 */
$formData = is_array($old ?? null) ? $old : [];
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('add_car') ?> | <?= e('app_name') ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚗</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>">
    <link rel="stylesheet" href="<?= url('/assets/css/cars.css') ?>">
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
                        <a href="<?= url('/cars') ?>" class="back-link">← <?= e('back_to_cars') ?></a>
                        <h2 class="page-title">🚗 <?= e('add_car') ?></h2>
                        <p class="page-subtitle"><?= e('add_car_subtitle') ?></p>
                    </div>
                </div>
                
                <?php if (!empty($errors['general']) && is_string($errors['general'])): ?>
                    <div class="alert alert-error"><span><?= e($errors['general']) ?></span></div>
                <?php endif; ?>
                
                <form method="POST" action="<?= url('/cars') ?>" class="car-form" enctype="multipart/form-data" novalidate>
                    <?= Csrf::field() ?>
                    
                    <div class="form-sections">
                        
                        <!-- 1. المعلومات الأساسية -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">1</span>
                                <?= e('basic_info') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="plate_number"><?= e('plate_number') ?> <span class="required">*</span></label>
                                    <input type="text" id="plate_number" name="plate_number" 
                                           value="<?= e($formData['plate_number'] ?? '') ?>"
                                           maxlength="20" required autofocus
                                           class="<?= !empty($errors['plate_number']) ? 'error' : '' ?>">
                                    <?php if (!empty($errors['plate_number'])): ?>
                                        <span class="form-error"><?= e($errors['plate_number']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="chassis_number"><?= e('chassis_number') ?> <span class="required">*</span></label>
                                    <input type="text" id="chassis_number" name="chassis_number" 
                                           value="<?= e($formData['chassis_number'] ?? '') ?>"
                                           maxlength="40" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="brand"><?= e('brand') ?> <span class="required">*</span></label>
                                    <input type="text" id="brand" name="brand" 
                                           value="<?= e($formData['brand'] ?? '') ?>"
                                           maxlength="50" required>
                                </div>
                                <div class="form-group">
                                    <label for="model"><?= e('model') ?> <span class="required">*</span></label>
                                    <input type="text" id="model" name="model" 
                                           value="<?= e($formData['model'] ?? '') ?>"
                                           maxlength="50" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="year"><?= e('year') ?> <span class="required">*</span></label>
                                    <input type="number" id="year" name="year" 
                                           value="<?= e($formData['year'] ?? date('Y')) ?>"
                                           min="1900" max="<?= date('Y') + 1 ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="car_type_id"><?= e('car_type') ?> <span class="required">*</span></label>
                                    <select id="car_type_id" name="car_type_id" required>
                                        <option value=""><?= e('select_type') ?></option>
                                        <?php foreach ($types as $type): ?>
                                            <option value="<?= (int)$type['id'] ?>"
                                                <?= ($formData['car_type_id'] ?? '') == $type['id'] ? 'selected' : '' ?>>
                                                <?= e(Lang::isArabic() ? $type['name_ar'] : $type['name_en']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="color"><?= e('color') ?></label>
                                    <input type="text" id="color" name="color" 
                                           value="<?= e($formData['color'] ?? '') ?>"
                                           maxlength="30">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 2. المواصفات -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">2</span>
                                <?= e('specifications') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="fuel_type"><?= e('fuel_type') ?></label>
                                    <select id="fuel_type" name="fuel_type">
                                        <option value="petrol" <?= ($formData['fuel_type'] ?? 'petrol') === 'petrol' ? 'selected' : '' ?>><?= e('petrol') ?></option>
                                        <option value="diesel" <?= ($formData['fuel_type'] ?? '') === 'diesel' ? 'selected' : '' ?>><?= e('diesel') ?></option>
                                        <option value="hybrid" <?= ($formData['fuel_type'] ?? '') === 'hybrid' ? 'selected' : '' ?>><?= e('hybrid') ?></option>
                                        <option value="electric" <?= ($formData['fuel_type'] ?? '') === 'electric' ? 'selected' : '' ?>><?= e('electric') ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="transmission"><?= e('transmission') ?></label>
                                    <select id="transmission" name="transmission">
                                        <option value="automatic" <?= ($formData['transmission'] ?? 'automatic') === 'automatic' ? 'selected' : '' ?>><?= e('automatic') ?></option>
                                        <option value="manual" <?= ($formData['transmission'] ?? '') === 'manual' ? 'selected' : '' ?>><?= e('manual') ?></option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="seats"><?= e('seats') ?></label>
                                    <input type="number" id="seats" name="seats" 
                                           value="<?= e($formData['seats'] ?? '5') ?>"
                                           min="1" max="50">
                                </div>
                                <div class="form-group">
                                    <label for="odometer"><?= e('odometer') ?> <small class="hint">(كم)</small></label>
                                    <input type="number" id="odometer" name="odometer" 
                                           value="<?= e($formData['odometer'] ?? '0') ?>"
                                           min="0">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 3. الأسعار -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">3</span>
                                <?= e('pricing') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="daily_price"><?= e('daily_price') ?> (<?= e('sar') ?>) <span class="required">*</span></label>
                                    <input type="number" id="daily_price" name="daily_price" 
                                           value="<?= e($formData['daily_price'] ?? '') ?>"
                                           step="0.01" min="0" required>
                                </div>
                                <div class="form-group">
                                    <label for="weekly_price"><?= e('weekly_price') ?> (<?= e('sar') ?>)</label>
                                    <input type="number" id="weekly_price" name="weekly_price" 
                                           value="<?= e($formData['weekly_price'] ?? '') ?>"
                                           step="0.01" min="0">
                                </div>
                                <div class="form-group">
                                    <label for="monthly_price"><?= e('monthly_price') ?> (<?= e('sar') ?>)</label>
                                    <input type="number" id="monthly_price" name="monthly_price" 
                                           value="<?= e($formData['monthly_price'] ?? '') ?>"
                                           step="0.01" min="0">
                                </div>
                                <div class="form-group">
                                    <label for="purchase_price"><?= e('purchase_price') ?> (<?= e('sar') ?>)</label>
                                    <input type="number" id="purchase_price" name="purchase_price" 
                                           value="<?= e($formData['purchase_price'] ?? '') ?>"
                                           step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        
                        <!-- 4. الوثائق -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">4</span>
                                <?= e('documents') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="registration_expiry"><?= e('registration_expiry') ?></label>
                                    <input type="date" id="registration_expiry" name="registration_expiry" 
                                           value="<?= e($formData['registration_expiry'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="insurance_expiry"><?= e('insurance_expiry') ?></label>
                                    <input type="date" id="insurance_expiry" name="insurance_expiry" 
                                           value="<?= e($formData['insurance_expiry'] ?? '') ?>">
                                </div>
                                <div class="form-group">
                                    <label for="inspection_expiry"><?= e('inspection_expiry') ?></label>
                                    <input type="date" id="inspection_expiry" name="inspection_expiry" 
                                           value="<?= e($formData['inspection_expiry'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- ✅ 5. صور السيارة -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">5</span>
                                🖼️ <?= e('car_images') ?>
                            </h3>
                            
                            <div class="info-box">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span><?= e('car_images_hint') ?></span>
                            </div>
                            
                            <!-- الصورة الرئيسية -->
                            <div class="form-group">
                                <label><?= e('main_image') ?> <small class="hint"><?= e('main_image_hint') ?></small></label>
                                <div class="image-upload-wrapper">
                                    <input type="file" 
                                           id="main_image" 
                                           name="main_image" 
                                           accept="image/jpeg,image/png,image/webp,image/gif"
                                           class="image-input"
                                           onchange="previewMainImage(event)">
                                    <label for="main_image" class="image-upload-label" id="mainImageLabel">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                            <polyline points="21 15 16 10 5 21"></polyline>
                                        </svg>
                                        <span><?= e('click_to_upload') ?></span>
                                        <small><?= e('image_formats') ?></small>
                                    </label>
                                </div>
                                <div id="mainImagePreview" class="image-preview-large" style="display:none;">
                                    <img id="mainImagePreviewImg" src="" alt="">
                                    <div class="preview-actions">
                                        <button type="button" class="btn-preview-action btn-change" onclick="document.getElementById('main_image').click()">
                                            🔄 <?= e('change') ?>
                                        </button>
                                        <button type="button" class="btn-preview-action btn-remove" onclick="removeMainImage()">
                                            🗑️ <?= e('remove') ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- الصور الإضافية -->
                            <div class="form-group">
                                <label><?= e('additional_images') ?> <small class="hint"><?= e('multiple_images_hint') ?></small></label>
                                <div class="image-upload-wrapper">
                                    <input type="file" 
                                           id="additional_images" 
                                           name="additional_images[]" 
                                           accept="image/jpeg,image/png,image/webp,image/gif"
                                           class="image-input"
                                           multiple
                                           onchange="previewAdditionalImages(event)">
                                    <label for="additional_images" class="image-upload-label">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <line x1="12" y1="5" x2="12" y2="19"></line>
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                        </svg>
                                        <span><?= e('click_to_upload_multiple') ?></span>
                                        <small><?= e('multiple_images_hint') ?></small>
                                    </label>
                                </div>
                                <div id="additionalImagesPreview" class="images-grid"></div>
                            </div>
                        </div>
                        
                        <!-- 6. الحالة والملاحظات -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">6</span>
                                <?= e('status_and_notes') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="status"><?= e('status') ?></label>
                                    <select id="status" name="status">
                                        <option value="available" <?= ($formData['status'] ?? 'available') === 'available' ? 'selected' : '' ?>><?= e('available') ?></option>
                                        <option value="maintenance" <?= ($formData['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>><?= e('maintenance') ?></option>
                                        <option value="out_of_service" <?= ($formData['status'] ?? '') === 'out_of_service' ? 'selected' : '' ?>><?= e('out_of_service') ?></option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes"><?= e('notes') ?></label>
                                <textarea id="notes" name="notes" rows="4" maxlength="1000"><?= e($formData['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="form-actions">
                        <a href="<?= url('/cars') ?>" class="btn btn-secondary"><?= e('cancel') ?></a>
                        <button type="submit" class="btn btn-primary btn-lg">💾 <?= e('save_car') ?></button>
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
        
        function previewMainImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            
            if (file.size > 5 * 1024 * 1024) {
                alert('<?= e('image_too_large') ?>');
                event.target.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('mainImagePreviewImg').src = e.target.result;
                document.getElementById('mainImagePreview').style.display = 'block';
                document.getElementById('mainImageLabel').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
        
        function removeMainImage() {
            document.getElementById('main_image').value = '';
            document.getElementById('mainImagePreview').style.display = 'none';
            document.getElementById('mainImageLabel').style.display = 'flex';
        }
        
        function previewAdditionalImages(event) {
            const files = event.target.files;
            const preview = document.getElementById('additionalImagesPreview');
            preview.innerHTML = '';
            
            Array.from(files).forEach((file, index) => {
                if (file.size > 5 * 1024 * 1024) return;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="">
                        <span class="preview-badge">${index + 1}</span>
                    `;
                    preview.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>