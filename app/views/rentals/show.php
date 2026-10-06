<?php
/**
 * تفاصيل العقد
 */
if (empty($rental) || !is_array($rental)) {
    header('Location: ' . url('/rentals'));
    exit;
}

$rentalId = (int) ($rental['id'] ?? 0);
$payments = is_array($payments ?? null) ? $payments : [];
$schedules = is_array($schedules ?? null) ? $schedules : [];
$inspections = is_array($inspections ?? null) ? $inspections : [];
$paidAmount = (float) ($paidAmount ?? 0);
$remaining = (float) ($remaining ?? 0);
$nextPayment = $nextPayment ?? null;

// جلب السيارة للحصول على قراءة العداد
$carModel = new Car();
$car = $carModel->find((int) ($rental['car_id'] ?? 0));

// جلب رقم واتساب المستأجر (من تفاصيل العقد مباشرة إذا كان موجوداً)
$whatsappNumber = $rental['customer_phone'] ?? $rental['phone'] ?? null;

// فحص وجود مبلغ تأمين
$hasDeposit = (float)($rental['deposit_amount'] ?? 0) > 0;
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($rental['contract_no'] ?? '') ?> | <?= e('app_name') ?></title>
    
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📝</text></svg>">
    
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
                    <a href="<?= url('/rentals') ?>"><?= e('rentals') ?></a>
                    <span>›</span>
                    <span><?= e($rental['contract_no'] ?? '') ?></span>
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
                
                <!-- Rental Header -->
                <div class="rental-detail-header">
                    <div class="rental-icon-large">📝</div>
                    
                    <div class="rental-detail-info">
                        <div class="rental-title-row">
                            <h1><?= e($rental['contract_no'] ?? '') ?></h1>
                            <span class="badge badge-<?= e(status_badge($rental['status'] ?? '')) ?>">
                                <?= e($rental['status'] ?? '') ?>
                            </span>
                        </div>
                        
                        <p class="rental-subtitle">
                            <strong><?= e($rental['customer_name'] ?? '') ?></strong>
                            <span>•</span>
                            <span><?= e($rental['plate_number'] ?? '') ?> — <?= e($rental['brand'] ?? '') ?> <?= e($rental['model'] ?? '') ?></span>
                        </p>
                        
                        <div class="rental-quick-info">
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('pickup_datetime') ?></span>
                                <strong><?= e(format_datetime($rental['pickup_datetime'] ?? '')) ?></strong>
                            </div>
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('return_due_datetime') ?></span>
                                <strong><?= e(format_datetime($rental['return_due_datetime'] ?? '')) ?></strong>
                            </div>
                            <div class="quick-info-item">
                                <span class="quick-label"><?= e('rental_days') ?></span>
                                <strong><?= e((string)($rental['rental_days'] ?? '')) ?> <?= e('days') ?></strong>
                            </div>
                        </div>
                        
                        <!-- ✅ الأزرار -->
                        <div class="car-actions">
                            <!-- طباعة العقد -->
                            <a href="<?= url('/rentals/' . $rentalId . '/print') ?>" 
                               target="_blank" 
                               class="btn btn-primary">
                                🖨️ <?= e('print_contract') ?>
                            </a>
                            
                            <!-- زر واتساب -->
                            <?php if ($whatsappNumber): ?>
                                <?php
                                $waMessage = urlencode(
                                    "مرحباً {$rental['customer_name']}،\n\n" .
                                    "نتواصل معك بخصوص عقد الإيجار: {$rental['contract_no']}\n" .
                                    "السيارة: {$rental['plate_number']}\n" .
                                    "المتبقي: " . number_format($remaining, 2) . " ر.س\n\n" .
                                    "شكراً لتعاملكم معنا 🙏"
                                );
                                ?>
                                <a href="https://wa.me/<?= $whatsappNumber ?>?text=<?= $waMessage ?>" 
                                   target="_blank"
                                   class="btn btn-success"
                                   title="<?= e('whatsapp_customer') ?>">
                                    📱 <?= e('whatsapp') ?>
                                </a>
                            <?php endif; ?>
                            
                            <!-- إغلاق العقد -->
                            <?php if (($rental['status'] ?? '') === 'active'): ?>
                                <button type="button" class="btn btn-success" onclick="showCloseModal()">
                                    ✅ <?= e('close_rental') ?>
                                </button>
                            <?php endif; ?>
                            
                            <!-- إضافة دفعة -->
                            <button type="button" class="btn btn-primary" onclick="showPaymentModal()">
                                💵 <?= e('add_payment') ?>
                            </button>
                            
                            <!-- إلغاء العقد (للمدير) -->
                            <?php if (Auth::isAdmin() && !in_array($rental['status'] ?? '', ['completed', 'cancelled'])): ?>
                                <button type="button" class="btn btn-danger" onclick="showCancelModal()">
                                    ❌ <?= e('cancel_rental') ?>
                                </button>
                            <?php endif; ?>
                            
                            <!-- رجوع -->
                            <a href="<?= url('/rentals') ?>" class="btn btn-secondary">
                                ← <?= e('back') ?>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Financial Stats -->
                <div class="stats-grid">
                    <div class="stat-card stat-info">
                        <div class="stat-icon">💰</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('total_amount') ?></span>
                            <strong class="stat-value"><?= number_format((float)($rental['total_amount'] ?? 0), 2) ?> <?= e('sar') ?></strong>
                        </div>
                    </div>
                    
                    <div class="stat-card stat-success">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <span class="stat-label"><?= e('paid_amount') ?></span>
                            <strong class="stat-value"><?= number_format($paidAmount, 2) ?> <?= e('sar') ?></strong>
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
                
                <!-- Next Payment Alert -->
                <?php if (!empty($nextPayment)): ?>
                    <?php 
                    $daysRemaining = (int) floor((strtotime($nextPayment['due_date']) - time()) / 86400);
                    $isOverdue = $daysRemaining < 0;
                    $alertClass = $isOverdue ? 'alert-error' : ($daysRemaining <= 3 ? 'alert-warning' : 'alert-info');
                    ?>
                    <div class="alert <?= $alertClass ?>" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                        <div>
                            <strong>
                                <?php if ($isOverdue): ?>
                                    ⚠️ <?= e('payment_overdue') ?>
                                <?php else: ?>
                                    ⏰ <?= e('next_payment') ?>
                                <?php endif; ?>
                            </strong>
                            <span style="margin-inline-start:12px;">
                                <?= e(format_date($nextPayment['due_date'])) ?>
                                — 
                                <strong><?= number_format((float)$nextPayment['amount'], 2) ?> <?= e('sar') ?></strong>
                            </span>
                            <?php if ($isOverdue): ?>
                                <span class="text-danger" style="margin-inline-start:12px;">
                                    (<?= abs($daysRemaining) ?> <?= e('days_overdue') ?>)
                                </span>
                            <?php else: ?>
                                <span style="margin-inline-start:12px;">
                                    (<?= $daysRemaining ?> <?= e('days_remaining') ?>)
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div style="display:flex; gap:8px;">
                            <?php if ($whatsappNumber): ?>
                                <?php
                                $reminderMsg = urlencode(
                                    "🔔 تذكير بموعد الدفعة\n\n" .
                                    "المستأجر: {$rental['customer_name']}\n" .
                                    "السيارة: {$rental['plate_number']}\n" .
                                    "المبلغ: " . number_format((float)$nextPayment['amount'], 2) . " ر.س\n" .
                                    "تاريخ الاستحقاق: {$nextPayment['due_date']}"
                                );
                                ?>
                                <a href="https://wa.me/<?= $whatsappNumber ?>?text=<?= $reminderMsg ?>" 
                                   target="_blank"
                                   class="btn btn-sm btn-success">
                                    📱 <?= e('whatsapp_reminder') ?>
                                </a>
                            <?php endif; ?>
                            
                            <button type="button" 
                                    class="btn btn-sm btn-primary" 
                                    onclick="showPaymentModal(<?= (int)$nextPayment['id'] ?>, <?= (float)$nextPayment['amount'] ?>)">
                                💵 <?= e('pay_now') ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Details Grid -->
                <div class="grid-2">
                    
                    <!-- معلومات العقد -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= e('rental_info') ?></h3>
                        </div>
                        <div class="card-body">
                            <table class="info-table">
                                <tr><th><?= e('contract_no') ?></th><td><code><?= e($rental['contract_no'] ?? '') ?></code></td></tr>
                                <tr><th><?= e('customer') ?></th><td><?= e($rental['customer_name'] ?? '') ?></td></tr>
                                <tr><th><?= e('car') ?></th><td><?= e($rental['plate_number'] ?? '') ?> — <?= e($rental['brand'] ?? '') ?> <?= e($rental['model'] ?? '') ?></td></tr>
                                <tr><th><?= e('pickup_datetime') ?></th><td><?= e(format_datetime($rental['pickup_datetime'] ?? '')) ?></td></tr>
                                <tr><th><?= e('pickup_location') ?></th><td><?= e($rental['pickup_location'] ?? '—') ?></td></tr>
                                <tr><th><?= e('return_due_datetime') ?></th><td><?= e(format_datetime($rental['return_due_datetime'] ?? '')) ?></td></tr>
                                <tr><th><?= e('return_location') ?></th><td><?= e($rental['return_location'] ?? '—') ?></td></tr>
                                <?php if (!empty($rental['actual_return_datetime'])): ?>
                                    <tr><th><?= e('actual_return') ?></th><td><?= e(format_datetime($rental['actual_return_datetime'])) ?></td></tr>
                                <?php endif; ?>
                                <tr><th><?= e('rental_days') ?></th><td><?= e((string)($rental['rental_days'] ?? '')) ?> <?= e('days') ?></td></tr>
                                
                                <?php if (!empty($car)): ?>
                                    <tr>
                                        <th><?= e('current_odometer') ?></th>
                                        <td><strong><?= number_format((int)($car['odometer'] ?? 0)) ?> كم</strong></td>
                                    </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    
                    <!-- المعلومات المالية -->
                    <div class="card">
                        <div class="card-header">
                            <h3><?= e('financial_info') ?></h3>
                        </div>
                        <div class="card-body">
                            <table class="info-table">
                                <tr><th><?= e('rate_type') ?></th><td><?= e($rental['rate_type'] ?? '') ?></td></tr>
                                <tr><th><?= e('rate_amount') ?></th><td><?= number_format((float)($rental['rate_amount'] ?? 0), 2) ?> <?= e('sar') ?></td></tr>
                                <tr><th><?= e('subtotal') ?></th><td><?= number_format((float)($rental['subtotal'] ?? 0), 2) ?> <?= e('sar') ?></td></tr>
                                <tr><th><?= e('discount') ?></th><td>-<?= number_format((float)($rental['discount'] ?? 0), 2) ?> <?= e('sar') ?></td></tr>
                                <tr><th><?= e('tax_amount') ?></th><td><?= number_format((float)($rental['tax_amount'] ?? 0), 2) ?> <?= e('sar') ?></td></tr>
                                <tr><th><?= e('extra_charges') ?></th><td><?= number_format((float)($rental['extra_charges'] ?? 0), 2) ?> <?= e('sar') ?></td></tr>
                                <tr class="total-row"><th><?= e('total_amount') ?></th><td><strong><?= number_format((float)($rental['total_amount'] ?? 0), 2) ?> <?= e('sar') ?></strong></td></tr>
                                <tr><th><?= e('deposit_amount') ?></th><td><?= number_format((float)($rental['deposit_amount'] ?? 0), 2) ?> <?= e('sar') ?></td></tr>
                            </table>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Payments -->
                <div class="card">
                    <div class="card-header">
                        <h3>💵 <?= e('payments') ?></h3>
                        <button type="button" class="card-link" onclick="showPaymentModal()">
                            + <?= e('add_payment') ?>
                        </button>
                    </div>
                    <div class="card-body no-padding">
                        <?php if (empty($payments)): ?>
                            <p class="empty-state"><?= e('no_payments') ?></p>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?= e('receipt_no') ?></th>
                                            <th><?= e('payment_type') ?></th>
                                            <th><?= e('method') ?></th>
                                            <th><?= e('amount') ?></th>
                                            <th><?= e('paid_at') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($payments as $p): ?>
                                            <tr>
                                                <td><code><?= e($p['receipt_no'] ?? '') ?></code></td>
                                                <td>
                                                    <?= e(payment_type_icon($p['payment_type'] ?? '')) ?>
                                                    <?= e($p['payment_type'] ?? '') ?>
                                                </td>
                                                <td>
                                                    <?= e(method_icon($p['method'] ?? '')) ?>
                                                    <?= e($p['method'] ?? '') ?>
                                                </td>
                                                <td><strong><?= number_format((float)($p['amount'] ?? 0), 2) ?></strong> <?= e('sar') ?></td>
                                                <td><?= e(format_datetime($p['paid_at'] ?? '')) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Payment Schedules -->
                <?php if (!empty($schedules)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3>📅 <?= e('payment_schedules') ?></h3>
                        </div>
                        <div class="card-body no-padding">
                            <div class="table-wrapper">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
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
                                            <?php 
                                            $scheduleRemaining = (float)($s['amount'] ?? 0) - (float)($s['paid_amount'] ?? 0);
                                            ?>
                                            <tr>
                                                <td><?= (int)($s['installment_no'] ?? 0) ?></td>
                                                <td><?= e(format_date($s['due_date'] ?? '')) ?></td>
                                                <td><?= number_format((float)($s['amount'] ?? 0), 2) ?> <?= e('sar') ?></td>
                                                <td><?= number_format((float)($s['paid_amount'] ?? 0), 2) ?> <?= e('sar') ?></td>
                                                <td>
                                                    <?php if ($scheduleRemaining > 0): ?>
                                                        <strong class="text-danger"><?= number_format($scheduleRemaining, 2) ?></strong>
                                                    <?php else: ?>
                                                        <span class="text-success">✅ <?= e('paid') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= e(status_badge($s['status'] ?? '')) ?>">
                                                        <?= e(status_icon($s['status'] ?? '')) ?>
                                                        <?= e($s['status'] ?? '') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($scheduleRemaining > 0 && ($rental['status'] ?? '') === 'active'): ?>
                                                        <button type="button" 
                                                                class="btn-icon btn-icon-success" 
                                                                title="<?= e('pay_now') ?>"
                                                                onclick="showPaymentModal(<?= (int)$s['id'] ?>, <?= number_format($scheduleRemaining, 2, '.', '') ?>)">
                                                            💵
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Inspections -->
                <div class="card">
                    <div class="card-header">
                        <h3>🔍 <?= e('inspections') ?></h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($inspections)): ?>
                            <p class="empty-state"><?= e('no_inspections') ?></p>
                        <?php else: ?>
                            <div class="inspections-list">
                                <?php foreach ($inspections as $insp): ?>
                                    <div class="inspection-item">
                                        <div class="inspection-header">
                                            <strong>
                                                <?= ($insp['type'] ?? '') === 'handover' ? '🚗' : '🔄' ?>
                                                <?= e($insp['type'] ?? '') ?>
                                            </strong>
                                            <span><?= e(format_datetime($insp['inspected_at'] ?? '')) ?></span>
                                        </div>
                                        <div class="inspection-details">
                                            <span>📊 <?= e('odometer') ?>: <strong><?= number_format((int)($insp['odometer'] ?? 0)) ?> كم</strong></span>
                                            <span>⛽ <?= e('fuel') ?>: <?= e($insp['fuel_level'] ?? '') ?></span>
                                            <span>✨ <?= e('condition') ?>: <?= e($insp['general_condition'] ?? '') ?></span>
                                        </div>
                                        
                                        <!-- صور الفحص -->
                                        <?php if (!empty($insp['photos'])): ?>
                                            <div class="inspection-photos">
                                                <?php foreach ($insp['photos'] as $photo): ?>
                                                    <div class="inspection-photo">
                                                        <img src="<?= url('/storage/uploads/inspections/' . $photo['image_path']) ?>" 
                                                             alt="<?= e($photo['caption'] ?? '') ?>"
                                                             onclick="openImageModal('<?= url('/storage/uploads/inspections/' . $photo['image_path']) ?>')">
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
            </main>
            
            <?php require BASE_PATH . '/app/views/layout/footer.php'; ?>
            
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- Modal إضافة دفعة -->
    <!-- ============================================ -->
    <div class="modal-overlay" id="paymentModal" style="display:none;">
        <div class="modal">
            <form method="POST" action="<?= url('/payments') ?>" id="paymentForm">
                <?= Csrf::field() ?>
                <input type="hidden" name="rental_id" value="<?= $rentalId ?>">
                <input type="hidden" name="schedule_id" id="paymentScheduleId" value="">
                
                <div class="modal-header">
                    <h3>💵 <?= e('add_payment') ?></h3>
                    <button type="button" class="modal-close" onclick="hidePaymentModal()">×</button>
                </div>
                
                <div class="modal-body">
                    <div class="payment-info-box">
                        <div class="info-row">
                            <span><?= e('contract_no') ?>:</span>
                            <strong><?= e($rental['contract_no'] ?? '') ?></strong>
                        </div>
                        <div class="info-row">
                            <span><?= e('customer') ?>:</span>
                            <strong><?= e($rental['customer_name'] ?? '') ?></strong>
                        </div>
                        <div class="info-row">
                            <span><?= e('remaining') ?>:</span>
                            <strong class="text-danger" id="modalRemaining">
                                <?= number_format($remaining, 2) ?> <?= e('sar') ?>
                            </strong>
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
                            <label class="radio-option">
                                <input type="radio" name="payment_type" value="deposit_refund">
                                <span class="radio-custom"></span>
                                <span>↩️ <?= e('deposit_refund') ?></span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_amount_modal">
                            <?= e('payment_amount') ?> 
                            <small class="hint">(<?= e('sar') ?>)</small>
                            <span class="required">*</span>
                        </label>
                        <input type="number" 
                               id="payment_amount_modal" 
                               name="amount" 
                               step="0.01" 
                               min="0.01" 
                               required
                               value="<?= number_format($remaining, 2, '.', '') ?>">
                        <small class="hint">
                            <?= e('max_amount') ?>: <?= number_format($remaining, 2) ?> <?= e('sar') ?>
                        </small>
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
                        <label for="payment_reference"><?= e('reference_no') ?></label>
                        <input type="text" 
                               id="payment_reference" 
                               name="reference_no" 
                               maxlength="50"
                               placeholder="<?= e('reference_no_placeholder') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="payment_notes_modal"><?= e('payment_notes') ?></label>
                        <textarea id="payment_notes_modal" 
                                  name="notes" 
                                  rows="2" 
                                  maxlength="255"></textarea>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hidePaymentModal()">
                        <?= e('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-primary">
                        💵 <?= e('save_payment') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- ✅ Modal إغلاق العقد -->
    <!-- ============================================ -->
    <div class="modal-overlay" id="closeModal" style="display:none;">
        <div class="modal">
            <form method="POST" action="<?= url('/rentals/' . $rentalId . '/close') ?>" id="closeForm">
                <?= Csrf::field() ?>
                
                <div class="modal-header">
                    <h3 class="text-success">✅ <?= e('close_rental') ?></h3>
                    <button type="button" class="modal-close" onclick="hideCloseModal()">×</button>
                </div>
                
                <div class="modal-body">
                    
                    <!-- 🚨 التنبيه إذا كان هناك مبلغ غير مدفوع -->
                    <?php if ($remaining > 0): ?>
                        <div class="alert alert-error" style="margin-bottom: 20px;">
                            <strong>⚠️ <?= e('unpaid_warning_title') ?? 'تنبيه هام:' ?></strong> <?= e('unpaid_warning_part1') ?? 'لا يمكن إغلاق العقد لوجود مبلغ مستحق الدفع قدره' ?> (<?= number_format($remaining, 2) ?> <?= e('sar') ?>). <?= e('unpaid_warning_part2') ?? 'الرجاء تسوية المبلغ المتبقي أولاً.' ?>
                        </div>
                    <?php else: ?>
                        <p><?= e('close_rental_confirmation') ?? 'هل أنت متأكد من إغلاق هذا العقد وإرجاع السيارة؟' ?></p>
                        
                        <!-- إدخال قراءة العداد (إلزامي) -->
                        <div class="form-group">
                            <label for="odometer_reading">
                                📊 <?= e('current_odometer') ?? 'قراءة العداد الحالية' ?> 
                                <span class="required">*</span>
                            </label>
                            <input type="number" 
                                   id="odometer_reading" 
                                   name="odometer_reading" 
                                   value="<?= (int)($car['odometer'] ?? 0) ?>"
                                   min="<?= (int)($car['odometer'] ?? 0) ?>" 
                                   required>
                            <small class="hint"><?= e('odometer_min_hint') ?? 'يجب أن تكون القراءة أكبر من أو تساوي' ?> (<?= (int)($car['odometer'] ?? 0) ?> <?= e('km') ?? 'كم' ?>)</small>
                        </div>
                        
                        <!-- استرجاع مبلغ التأمين (إلزامي إذا وجد مبلغ) -->
                        <?php if ($hasDeposit): ?>
                            <div class="form-group">
                                <label class="checkbox-wrapper">
                                    <input type="checkbox" name="refund_deposit" id="refund_deposit" value="1" required>
                                    <span class="checkbox-custom"></span>
                                    <span style="font-weight: bold; color: #d9534f;">
                                        💰 <?= e('confirm_deposit_refund') ?? 'أؤكد إنشاء سند لصرف وإرجاع مبلغ التأمين للعميل' ?> (<?= number_format((float)$rental['deposit_amount'], 2) ?> <?= e('sar') ?>)
                                        <span class="required">*</span>
                                    </span>
                                </label>
                            </div>
                        <?php endif; ?>

                        <!-- غرامة التأخير (اختياري) -->
                        <div class="form-group" style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
                            <label class="checkbox-wrapper">
                                <input type="checkbox" name="apply_overdue_penalty" id="apply_overdue_penalty" value="1" onchange="togglePenaltyInput()">
                                <span class="checkbox-custom"></span>
                                <span>⚠️ <?= e('apply_overdue_penalty') ?? 'تطبيق غرامة تأخير تسليم' ?></span>
                            </label>
                        </div>
                        
                        <div class="form-group" id="penaltyInputGroup" style="display: none;">
                            <label for="overdue_penalty_amount"><?= e('penalty_amount_sar') ?? 'مبلغ الغرامة (ر.س)' ?></label>
                            <input type="number" name="overdue_penalty_amount" id="overdue_penalty_amount" step="0.01" min="0" placeholder="0.00">
                        </div>
                        
                        <div class="alert alert-info" style="margin-top:16px;">
                            <span>🚗 <?= e('car_status_available_msg') ?? 'سيتم تغيير حالة السيارة إلى (متاحة).' ?></span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideCloseModal()">
                        <?= e('cancel') ?>
                    </button>
                    <!-- إخفاء الزر إذا لم يسدد العميل المبالغ -->
                    <?php if ($remaining <= 0): ?>
                        <button type="submit" class="btn btn-success">
                            ✅ <?= e('confirm_and_close') ?? 'تأكيد وإغلاق العقد' ?>
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- Modal إلغاء العقد -->
    <!-- ============================================ -->
    <div class="modal-overlay" id="cancelModal" style="display:none;">
        <div class="modal">
            <form method="POST" action="<?= url('/rentals/' . $rentalId . '/cancel') ?>" id="cancelForm">
                <?= Csrf::field() ?>
                
                <div class="modal-header">
                    <h3 class="text-danger">❌ <?= e('cancel_rental') ?></h3>
                    <button type="button" class="modal-close" onclick="hideCancelModal()">×</button>
                </div>
                
                <div class="modal-body">
                    <p><?= e('confirm_cancel_rental') ?? 'هل أنت متأكد من إلغاء هذا العقد؟' ?></p>
                    
                    <div class="form-group">
                        <label for="cancellation_reason"><?= e('cancellation_reason') ?? 'سبب الإلغاء' ?> <span class="required">*</span></label>
                        <textarea id="cancellation_reason" 
                                  name="cancellation_reason" 
                                  rows="3" 
                                  maxlength="255" required></textarea>
                    </div>
                    
                    <?php if ($hasDeposit): ?>
                        <div class="form-group">
                            <label class="checkbox-wrapper">
                                <input type="checkbox" name="refund_deposit" id="cancel_refund_deposit" value="1" checked>
                                <span class="checkbox-custom"></span>
                                <span>💰 <?= e('refund_deposit_label') ?? 'إنشاء سند إرجاع تأمين للعميل' ?> (<?= number_format((float)$rental['deposit_amount'], 2) ?> <?= e('sar') ?>)</span>
                            </label>
                        </div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning" style="margin-top:16px;">
                        <span>🚗 <?= e('cancel_rental_warning') ?? 'سيتم إلغاء العقد وتغيير حالة السيارة إلى (متاحة).' ?></span>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="hideCancelModal()">
                        <?= e('cancel') ?>
                    </button>
                    <button type="submit" class="btn btn-danger">
                        ❌ <?= e('confirm_cancel') ?? 'تأكيد الإلغاء' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- ============================================ -->
    <!-- Modal عرض الصورة -->
    <!-- ============================================ -->
    <div class="modal-overlay" id="imageModal" style="display:none;" onclick="hideImageModal()">
        <div style="max-width:90vw; max-height:90vh;" onclick="event.stopPropagation()">
            <img id="modalImage" src="" alt="" 
                 style="max-width:100%; max-height:85vh; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,0.5);">
        </div>
    </div>
    
    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= url('') ?>',
            csrfToken: '<?= Csrf::getToken() ?>',
            remaining: <?= (float)$remaining ?>,
        };
        
        function showPaymentModal(scheduleId = null, scheduleAmount = null) {
            const modal = document.getElementById('paymentModal');
            const scheduleInput = document.getElementById('paymentScheduleId');
            const amountInput = document.getElementById('payment_amount_modal');
            
            if (scheduleId !== null) {
                scheduleInput.value = scheduleId;
                if (scheduleAmount !== null) {
                    amountInput.value = parseFloat(scheduleAmount).toFixed(2);
                }
            } else {
                scheduleInput.value = '';
                amountInput.value = window.APP_CONFIG.remaining.toFixed(2);
            }
            
            modal.style.display = 'flex';
            setTimeout(() => amountInput.focus(), 100);
        }
        
        function hidePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        
        function showCloseModal() {
            document.getElementById('closeModal').style.display = 'flex';
        }
        
        function hideCloseModal() {
            document.getElementById('closeModal').style.display = 'none';
        }
        
        function showCancelModal() {
            document.getElementById('cancelModal').style.display = 'flex';
        }
        
        function hideCancelModal() {
            document.getElementById('cancelModal').style.display = 'none';
        }
        
        function openImageModal(src) {
            document.getElementById('modalImage').src = src;
            document.getElementById('imageModal').style.display = 'flex';
        }
        
        function hideImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        // إظهار/إخفاء حقل الغرامة
        function togglePenaltyInput() {
            const chk = document.getElementById('apply_overdue_penalty');
            const div = document.getElementById('penaltyInputGroup');
            const input = document.getElementById('overdue_penalty_amount');
            if (chk.checked) {
                div.style.display = 'block';
                input.required = true;
            } else {
                div.style.display = 'none';
                input.required = false;
                input.value = '';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            ['paymentModal', 'closeModal', 'cancelModal', 'imageModal'].forEach(id => {
                const modal = document.getElementById(id);
                if (modal && id !== 'imageModal') {
                    modal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            if (id === 'paymentModal') hidePaymentModal();
                            else if (id === 'closeModal') hideCloseModal();
                            else if (id === 'cancelModal') hideCancelModal();
                        }
                    });
                }
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    hidePaymentModal();
                    hideCloseModal();
                    hideCancelModal();
                    hideImageModal();
                }
            });
        });
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>