<?php
/**
 * تفاصيل السيارة
 */
if (empty($car) || !is_array($car)) {
    header('Location: ' . url('/cars'));
    exit;
}

$carId = (int) ($car['id'] ?? 0);
$carImages = is_array($carImages ?? null) ? $carImages : [];
$rentals = is_array($rentals ?? null) ? $rentals : [];
$expenses = is_array($expenses ?? null) ? $expenses : [];
$income = (float) ($income ?? 0);
$expensesTotal = (float) ($expensesTotal ?? 0);
$profit = (float) ($profit ?? 0);
$carStatus = $car['status'] ?? 'available';

// تجميع كل الصور
$allImages = [];
if (!empty($car['main_image'])) {
    $allImages[] = [
        'src' => url('/storage/uploads/cars/' . $car['main_image']),
        'is_main' => true,
    ];
}
foreach ($carImages as $img) {
    $allImages[] = [
        'src' => url('/storage/uploads/cars/' . $img['image_name']),
        'is_main' => false,
    ];
}
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($car['brand'] ?? '') ?> <?= e($car['model'] ?? '') ?> | <?= e('app_name') ?></title>
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
                
                <!-- Breadcrumb -->
                <div class="breadcrumb">
                    <a href="<?= url('/cars') ?>"><?= e('cars') ?></a>
                    <span>›</span>
                    <span><?= e($car['plate_number'] ?? '') ?></span>
                </div>
                
                <!-- Flash -->
                <?php $successMsg = Auth::getFlash('success'); ?>
                <?php if (!empty($successMsg) && is_string($successMsg)): ?>
                    <div class="alert alert-success"><span><?= e($successMsg) ?></span></div>
                <?php endif; ?>
                
                <?php $errorMsg = Auth::getFlash('error'); ?>
                <?php if (!empty($errorMsg) && is_string($errorMsg)): ?>
                    <div class="alert alert-error"><span><?= e($errorMsg) ?></span></div>
                <?php endif; ?>
                
                <!-- ✅ Car Header with Image -->
                <div class="car-detail-header">
                    
                    <!-- صورة السيارة -->
                    <div class="car-detail-image-container">
                        <div class="car-detail-image" onclick="openLightbox(0)">
                            <?php if (!empty($car['main_image'])): ?>
                                <img src="<?= url('/storage/uploads/cars/' . $car['main_image']) ?>" alt="">
                                <div class="gallery-zoom-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="11" cy="11" r="8"></circle>
                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                        <line x1="11" y1="8" x2="11" y2="14"></line>
                                        <line x1="8" y1="11" x2="14" y2="11"></line>
                                    </svg>
                                </div>
                            <?php else: ?>
                                <span>🚗</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($carImages)): ?>
                            <div class="car-images-mini-grid">
                                <?php foreach (array_slice($carImages, 0, 4) as $i => $img): ?>
                                    <?php $actualIndex = $i + (!empty($car['main_image']) ? 1 : 0); ?>
                                    <div class="mini-thumb" onclick="openLightbox(<?= $actualIndex ?>)">
                                        <img src="<?= url('/storage/uploads/cars/' . $img['image_name']) ?>" alt="" loading="lazy">
                                    </div>
                                <?php endforeach; ?>
                                <?php if (count($carImages) > 4): ?>
                                    <div class="mini-thumb mini-thumb-more" onclick="openLightbox(0)">
                                        +<?= count($carImages) - 4 ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- معلومات السيارة -->
                    <div class="car-detail-info">
                        <div class="car-title-row">
                            <h1><?= e($car['brand'] ?? '') ?> <?= e($car['model'] ?? '') ?></h1>
                            <span class="badge badge-<?= e(status_badge($carStatus)) ?>">
                                <?= e($carStatus) ?>
                            </span>
                        </div>
                        
                        <p class="car-plate">
                            <code><?= e($car['plate_number'] ?? '') ?></code>
                            <span>•</span>
                            <span><?= e((string)($car['year'] ?? '')) ?></span>
                            <span>•</span>
                            <span><?= e($car['color'] ?? '') ?></span>
                        </p>
                        
                        <div class="car-quick-info">
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('car_type') ?></span>
                                <strong><?= e(Lang::isArabic() ? ($car['type_name_ar'] ?? '') : ($car['type_name_en'] ?? '')) ?></strong>
                            </div>
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('daily_price') ?></span>
                                <strong><?= number_format((float)($car['daily_price'] ?? 0), 2) ?> <?= e('sar') ?></strong>
                            </div>
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('odometer') ?></span>
                                <strong><?= number_format((int)($car['odometer'] ?? 0)) ?> كم</strong>
                            </div>
                        </div>
                        
                        <div class="car-actions">
                            <a href="<?= url('/cars/' . $carId . '/edit') ?>" class="btn btn-primary">
                                ✏️ <?= e('edit') ?>
                            </a>
                            
                            <?php if ($carStatus !== 'rented'): ?>
                                <form method="POST" action="<?= url('/cars/' . $carId . '/status') ?>" class="status-form" onsubmit="return confirm('<?= e('confirm_status_change') ?>');">
                                    <?= Csrf::field() ?>
                                    <select name="status" onchange="this.form.submit()" class="btn btn-secondary status-select">
                                        <option value=""><?= e('change_status') ?> ▾</option>
                                        <option value="available" <?= $carStatus === 'available' ? 'disabled' : '' ?>>✅ <?= e('available') ?></option>
                                        <option value="maintenance" <?= $carStatus === 'maintenance' ? 'disabled' : '' ?>>🔧 <?= e('maintenance') ?></option>
                                        <option value="out_of_service" <?= $carStatus === 'out_of_service' ? 'disabled' : '' ?>>🚫 <?= e('out_of_service') ?></option>
                                    </select>
                                </form>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary" disabled>🔒 <?= e('change_status') ?></button>
                            <?php endif; ?>
                            
                            <a href="<?= url('/cars') ?>" class="btn btn-secondary">← <?= e('back') ?></a>
                        </div>
                    </div>
                </div>
                
                <!-- Financial Stats -->
                <div class="stats-grid">
                    <div class="stat-card stat-success">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_income') ?></span>
                            <strong class="stat-value"><?= number_format($income, 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                    <div class="stat-card stat-warning">
                        <div class="stat-icon">📉</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_expenses') ?></span>
                            <strong class="stat-value"><?= number_format($expensesTotal, 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                    <div class="stat-card stat-<?= $profit >= 0 ? 'success' : 'danger' ?>">
                        <div class="stat-icon">📊</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('net_profit') ?></span>
                            <strong class="stat-value"><?= number_format($profit, 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- ✅ معرض الصور -->
                <?php if (!empty($allImages)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>🖼️ <?= e('car_gallery') ?></h3>
                            <span class="card-subtitle">
                                <?= count($allImages) ?> <?= e('images') ?>
                                <?php if (count($allImages) > 1): ?> — <?= e('click_to_zoom') ?><?php endif; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <?php 
                            $mainImg = $allImages[0];
                            $otherImages = array_slice($allImages, 1);
                            ?>
                            
                            <!-- الصورة الرئيسية الكبيرة -->
                            <div class="gallery-main">
                                <div class="gallery-main-image" onclick="openLightbox(0)">
                                    <img src="<?= $mainImg['src'] ?>" alt="">
                                    <?php if ($mainImg['is_main']): ?>
                                        <span class="gallery-badge-main">⭐ <?= e('main_image') ?></span>
                                    <?php endif; ?>
                                    <div class="gallery-zoom-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="11" cy="11" r="8"></circle>
                                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                            <line x1="11" y1="8" x2="11" y2="14"></line>
                                            <line x1="8" y1="11" x2="14" y2="11"></line>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- الصور الإضافية -->
                            <?php if (!empty($otherImages)): ?>
                                <div class="gallery-thumbs">
                                    <?php foreach ($otherImages as $i => $img): ?>
                                        <?php $actualIndex = $i + 1; ?>
                                        <div class="gallery-thumb" onclick="openLightbox(<?= $actualIndex ?>)">
                                            <img src="<?= $img['src'] ?>" alt="" loading="lazy">
                                            <div class="thumb-overlay">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="11" cy="11" r="8"></circle>
                                                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                                </svg>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Lightbox -->
                    <div class="lightbox" id="lightbox" style="display:none;" onclick="closeLightbox()">
                        <button class="lightbox-close" onclick="event.stopPropagation();closeLightbox()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                        
                        <?php if (count($allImages) > 1): ?>
                            <button class="lightbox-prev" onclick="event.stopPropagation();lightboxPrev()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15 18 9 12 15 6"></polyline>
                                </svg>
                            </button>
                        <?php endif; ?>
                        
                        <div class="lightbox-content" onclick="event.stopPropagation()">
                            <img id="lightboxImg" src="" alt="">
                            <div class="lightbox-info">
                                <span class="lightbox-counter" id="lightboxCounter"></span>
                                <span class="lightbox-caption" id="lightboxCaption"></span>
                            </div>
                        </div>
                        
                        <?php if (count($allImages) > 1): ?>
                            <button class="lightbox-next" onclick="event.stopPropagation();lightboxNext()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Details Grid -->
                <div class="grid-2">
                    <div class="card">
                        <div class="card-header"><h3><?= e('specifications') ?></h3></div>
                        <div class="card-body">
                            <table class="info-table">
                                <tr><th><?= e('plate_number') ?></th><td><code><?= e($car['plate_number'] ?? '') ?></code></td></tr>
                                <tr><th><?= e('chassis_number') ?></th><td><code><?= e($car['chassis_number'] ?? '') ?></code></td></tr>
                                <tr><th><?= e('brand') ?></th><td><?= e($car['brand'] ?? '') ?></td></tr>
                                <tr><th><?= e('model') ?></th><td><?= e($car['model'] ?? '') ?></td></tr>
                                <tr><th><?= e('year') ?></th><td><?= e((string)($car['year'] ?? '')) ?></td></tr>
                                <tr><th><?= e('color') ?></th><td><?= e($car['color'] ?? '') ?></td></tr>
                                <tr><th><?= e('fuel_type') ?></th><td><?= e($car['fuel_type'] ?? '') ?></td></tr>
                                <tr><th><?= e('transmission') ?></th><td><?= e($car['transmission'] ?? '') ?></td></tr>
                                <tr><th><?= e('seats') ?></th><td><?= e((string)($car['seats'] ?? '')) ?></td></tr>
                                <tr><th><?= e('odometer') ?></th><td><?= number_format((int)($car['odometer'] ?? 0)) ?> كم</td></tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header"><h3><?= e('pricing') ?> & <?= e('documents') ?></h3></div>
                        <div class="card-body">
                            <table class="info-table">
                                <tr><th><?= e('daily_price') ?></th><td><strong><?= number_format((float)($car['daily_price'] ?? 0), 2) ?> <?= e('sar') ?></strong></td></tr>
                                <tr><th><?= e('weekly_price') ?></th><td><?= number_format((float)($car['weekly_price'] ?? 0), 2) ?> <?= e('sar') ?></td></tr>
                                <tr><th><?= e('monthly_price') ?></th><td><?= number_format((float)($car['monthly_price'] ?? 0), 2) ?> <?= e('sar') ?></td></tr>
                                <tr><th><?= e('purchase_price') ?></th><td><?= !empty($car['purchase_price']) ? number_format((float)$car['purchase_price'], 2) . ' ' . e('sar') : '—' ?></td></tr>
                                <tr><th><?= e('registration_expiry') ?></th><td><?= !empty($car['registration_expiry']) ? e(format_date($car['registration_expiry'])) : '—' ?></td></tr>
                                <tr><th><?= e('insurance_expiry') ?></th><td><?= !empty($car['insurance_expiry']) ? e(format_date($car['insurance_expiry'])) : '—' ?></td></tr>
                                <tr><th><?= e('inspection_expiry') ?></th><td><?= !empty($car['inspection_expiry']) ? e(format_date($car['inspection_expiry'])) : '—' ?></td></tr>
                                <?php if (!empty($car['notes'])): ?>
                                    <tr><th><?= e('notes') ?></th><td><?= e($car['notes']) ?></td></tr>
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
                                            <th><?= e('customer') ?></th>
                                            <th><?= e('pickup_datetime') ?></th>
                                            <th><?= e('return_due_datetime') ?></th>
                                            <th><?= e('total_amount') ?></th>
                                            <th><?= e('status') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rentals as $r): ?>
                                            <tr>
                                                <td><a href="<?= url('/rentals/' . (int)($r['id'] ?? 0)) ?>" class="table-link"><code><?= e($r['contract_no'] ?? '') ?></code></a></td>
                                                <td><?= e($r['customer_name'] ?? '') ?></td>
                                                <td><?= e(format_date($r['pickup_datetime'] ?? '')) ?></td>
                                                <td><?= e(format_date($r['return_due_datetime'] ?? '')) ?></td>
                                                <td><strong><?= number_format((float)($r['total_amount'] ?? 0), 2) ?></strong> <?= e('sar') ?></td>
                                                <td><span class="badge badge-<?= e(status_badge($r['status'] ?? '')) ?>"><?= e($r['status'] ?? '') ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Expense History -->
                <div class="card">
                    <div class="card-header">
                        <h3><?= e('expense_history') ?></h3>
                        <a href="<?= url('/expenses/create?car_id=' . $carId) ?>" class="card-link">+ <?= e('add_expense') ?></a>
                    </div>
                    <div class="card-body no-padding">
                        <?php if (empty($expenses)): ?>
                            <p class="empty-state"><?= e('no_expenses') ?></p>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?= e('date') ?></th>
                                            <th><?= e('category') ?></th>
                                            <th><?= e('description') ?></th>
                                            <th><?= e('amount') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($expenses as $exp): ?>
                                            <tr>
                                                <td><?= e(format_date($exp['expense_date'] ?? '')) ?></td>
                                                <td><?= e($exp['category_name'] ?? '') ?></td>
                                                <td><?= e($exp['description'] ?? '') ?></td>
                                                <td><strong><?= number_format((float)($exp['amount'] ?? 0), 2) ?></strong> <?= e('sar') ?></td>
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
    
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= url('') ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
        };
        
        let currentImageIndex = 0;
        const galleryImages = <?= json_encode(array_map(fn($i) => $i['src'], $allImages)) ?>;
        const imageLabels = <?= json_encode(array_map(fn($i) => $i['is_main'] ? '⭐ الصورة الرئيسية' : '', $allImages)) ?>;
        
        function openLightbox(index) {
            currentImageIndex = index;
            updateLightbox();
            document.getElementById('lightbox').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeLightbox() {
            document.getElementById('lightbox').style.display = 'none';
            document.body.style.overflow = '';
        }
        
        function lightboxPrev() {
            currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
            updateLightbox();
        }
        
        function lightboxNext() {
            currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
            updateLightbox();
        }
        
        function updateLightbox() {
            document.getElementById('lightboxImg').src = galleryImages[currentImageIndex];
            
            const counter = document.getElementById('lightboxCounter');
            if (counter) counter.textContent = (currentImageIndex + 1) + ' / ' + galleryImages.length;
            
            const caption = document.getElementById('lightboxCaption');
            if (caption) caption.textContent = imageLabels[currentImageIndex] || '';
        }
        
        document.addEventListener('keydown', function(e) {
            const lightbox = document.getElementById('lightbox');
            if (lightbox && lightbox.style.display === 'flex') {
                if (e.key === 'Escape') closeLightbox();
                if (e.key === 'ArrowLeft') lightboxPrev();
                if (e.key === 'ArrowRight') lightboxNext();
            }
        });
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>