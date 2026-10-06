<?php
/**
 * قائمة السيارات
 */
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('cars') ?> | <?= e('app_name') ?></title>
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
                        <h2 class="page-title">🚗 <?= e('cars') ?></h2>
                        <p class="page-subtitle"><?= e('cars_subtitle') ?></p>
                    </div>
                    <div class="page-actions">
                        <a href="<?= url('/cars/create') ?>" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <?= e('add_car') ?>
                        </a>
                    </div>
                </div>
                
                <?php $successMsg = Auth::getFlash('success'); ?>
                <?php if (!empty($successMsg) && is_string($successMsg)): ?>
                    <div class="alert alert-success"><span><?= e($successMsg) ?></span></div>
                <?php endif; ?>
                
                <?php $errorMsg = Auth::getFlash('error'); ?>
                <?php if (!empty($errorMsg) && is_string($errorMsg)): ?>
                    <div class="alert alert-error"><span><?= e($errorMsg) ?></span></div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="stats-grid stats-grid-compact">
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm">🚗</div>
                        <div>
                            <span class="stat-label-sm"><?= e('total_cars') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['total'] ?? 0)) ?></strong>
                        </div>
                    </div>
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--success-light);">✅</div>
                        <div>
                            <span class="stat-label-sm"><?= e('available') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['available'] ?? 0)) ?></strong>
                        </div>
                    </div>
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--info-color);color:white;">🔑</div>
                        <div>
                            <span class="stat-label-sm"><?= e('rented') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['rented'] ?? 0)) ?></strong>
                        </div>
                    </div>
                    <div class="stat-card-sm">
                        <div class="stat-icon-sm" style="background:var(--warning-light);">🔧</div>
                        <div>
                            <span class="stat-label-sm"><?= e('maintenance') ?></span>
                            <strong class="stat-value-sm"><?= number_format((int)($stats['maintenance'] ?? 0)) ?></strong>
                        </div>
                    </div>
                </div>
                
                <!-- Filters -->
                <div class="card filters-card">
                    <form method="GET" action="<?= url('/cars') ?>" class="filters-form">
                        <div class="filter-group">
                            <label><?= e('search') ?></label>
                            <input type="text" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="<?= e('search_cars_placeholder') ?>">
                        </div>
                        <div class="filter-group">
                            <label><?= e('status') ?></label>
                            <select name="status">
                                <option value=""><?= e('all') ?></option>
                                <option value="available" <?= ($filters['status'] ?? '') === 'available' ? 'selected' : '' ?>><?= e('available') ?></option>
                                <option value="rented" <?= ($filters['status'] ?? '') === 'rented' ? 'selected' : '' ?>><?= e('rented') ?></option>
                                <option value="maintenance" <?= ($filters['status'] ?? '') === 'maintenance' ? 'selected' : '' ?>><?= e('maintenance') ?></option>
                                <option value="out_of_service" <?= ($filters['status'] ?? '') === 'out_of_service' ? 'selected' : '' ?>><?= e('out_of_service') ?></option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label><?= e('car_type') ?></label>
                            <select name="car_type_id">
                                <option value=""><?= e('all') ?></option>
                                <?php if (!empty($types)): ?>
                                    <?php foreach ($types as $type): ?>
                                        <option value="<?= (int)$type['id'] ?>" <?= ($filters['car_type_id'] ?? 0) == $type['id'] ? 'selected' : '' ?>>
                                            <?= e(Lang::isArabic() ? $type['name_ar'] : $type['name_en']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">🔍 <?= e('search') ?></button>
                            <a href="<?= url('/cars') ?>" class="btn btn-secondary"><?= e('reset') ?></a>
                        </div>
                    </form>
                </div>
                
                <!-- Table -->
                <div class="card">
                    <div class="card-body no-padding">
                        <?php if (empty($cars)): ?>
                            <div class="empty-state-large">
                                <div class="empty-icon">🚗</div>
                                <h3><?= e('no_cars') ?></h3>
                                <p><?= e('no_cars_subtitle') ?></p>
                                <a href="<?= url('/cars/create') ?>" class="btn btn-primary"><?= e('add_first_car') ?></a>
                            </div>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?= e('car') ?></th>
                                            <th><?= e('plate_number') ?></th>
                                            <th><?= e('car_type') ?></th>
                                            <th><?= e('year') ?></th>
                                            <th><?= e('daily_price') ?></th>
                                            <th><?= e('status') ?></th>
                                            <th><?= e('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cars as $car): ?>
                                            <?php 
                                            $hasImage = !empty($car['main_image']);
                                            $imageSrc = $hasImage ? url('/storage/uploads/cars/' . $car['main_image']) : '';
                                            $imagesCount = (int)($car['images_count'] ?? 0) + ($hasImage ? 1 : 0);
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="car-cell">
                                                        <div class="car-image-thumb" 
                                                             onclick="openImageLightbox('<?= $imageSrc ?>', '<?= e(addslashes($car['brand'] . ' ' . $car['model'])) ?>')">
                                                            <?php if ($hasImage): ?>
                                                                <img src="<?= $imageSrc ?>" 
                                                                     alt="<?= e($car['brand'] . ' ' . $car['model']) ?>"
                                                                     loading="lazy">
                                                                <div class="image-overlay">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                        <circle cx="11" cy="11" r="8"></circle>
                                                                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                                                                        <line x1="11" y1="8" x2="11" y2="14"></line>
                                                                        <line x1="8" y1="11" x2="14" y2="11"></line>
                                                                    </svg>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="no-image">🚗</span>
                                                            <?php endif; ?>
                                                            <?php if ($imagesCount > 1): ?>
                                                                <span class="images-count">📷 <?= $imagesCount ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <strong><?= e($car['brand'] ?? '') ?> <?= e($car['model'] ?? '') ?></strong>
                                                            <small><?= e($car['color'] ?? '') ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><code><?= e($car['plate_number'] ?? '') ?></code></td>
                                                <td><?= e(Lang::isArabic() ? ($car['type_name_ar'] ?? '') : ($car['type_name_en'] ?? '')) ?></td>
                                                <td><?= e((string)($car['year'] ?? '')) ?></td>
                                                <td><strong><?= number_format((float)($car['daily_price'] ?? 0), 2) ?></strong> <?= e('sar') ?></td>
                                                <td>
                                                    <span class="badge badge-<?= e(status_badge($car['status'] ?? '')) ?>">
                                                        <?= e($car['status'] ?? '') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="actions">
                                                        <a href="<?= url('/cars/' . (int)$car['id']) ?>" class="btn-icon" title="<?= e('view') ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                                <circle cx="12" cy="12" r="3"></circle>
                                                            </svg>
                                                        </a>
                                                        <a href="<?= url('/cars/' . (int)$car['id'] . '/edit') ?>" class="btn-icon" title="<?= e('edit') ?>">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                            </svg>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (($totalPages ?? 1) > 1): ?>
                                <div class="pagination-wrapper">
                                    <div class="pagination-info"><?= e('showing') ?> <?= count($cars) ?> <?= e('of') ?> <?= (int)$total ?></div>
                                    <div class="pagination">
                                        <?php if ($currentPage > 1): ?>
                                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $currentPage - 1])) ?>" class="page-link">←</a>
                                        <?php endif; ?>
                                        <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="page-link <?= $i === $currentPage ? 'active' : '' ?>"><?= $i ?></a>
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
    
    <!-- ✅ Lightbox لعرض الصور -->
    <div class="image-lightbox" id="imageLightbox" onclick="closeImageLightbox()" style="display:none;">
        <button class="lightbox-close-btn" onclick="event.stopPropagation();closeImageLightbox()">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
        <div class="lightbox-content" onclick="event.stopPropagation()">
            <img id="lightboxImage" src="" alt="">
            <p id="lightboxCaption"></p>
        </div>
    </div>
    
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= url('') ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
        };
        
        function openImageLightbox(src, caption) {
            if (!src) return;
            document.getElementById('lightboxImage').src = src;
            document.getElementById('lightboxCaption').textContent = caption || '';
            document.getElementById('imageLightbox').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeImageLightbox() {
            document.getElementById('imageLightbox').style.display = 'none';
            document.body.style.overflow = '';
        }
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeImageLightbox();
        });
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>