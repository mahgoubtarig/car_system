<?php
/**
 * إنشاء عقد جديد
 */
$formData = is_array($old ?? null) ? $old : [];
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e('create_rental') ?> | <?= e('app_name') ?></title>
    
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
                
                <div class="page-header">
                    <div>
                        <a href="<?= url('/rentals') ?>" class="back-link">← <?= e('back_to_rentals') ?></a>
                        <h2 class="page-title"><?= e('create_rental') ?></h2>
                        <p class="page-subtitle">
                            <?= e('contract_no') ?>: <strong><?= e($nextContractNo ?? '') ?></strong>
                        </p>
                    </div>
                </div>
                
                <?php if (!empty($errors['general']) && is_string($errors['general'])): ?>
                    <div class="alert alert-error">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12.01" y2="16"></line>
                        </svg>
                        <span><?= e($errors['general']) ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?= url('/rentals') ?>" class="rental-form" novalidate id="rentalForm">
                    <?= Csrf::field() ?>
                    
                    <div class="form-sections">
                        
                        <!-- 1. المستأجر والسيارة -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">1</span>
                                <?= e('customer_and_car') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="customer_id">
                                        <?= e('customer') ?> 
                                        <span class="required">*</span>
                                    </label>
                                    <select id="customer_id" name="customer_id" required
                                            class="<?= !empty($errors['customer_id']) ? 'error' : '' ?>">
                                        <option value=""><?= e('select_customer') ?></option>
                                        <?php if (!empty($customers)): ?>
                                            <?php foreach ($customers as $c): ?>
                                                <option value="<?= (int)$c['id'] ?>" 
                                                    <?= ($formData['customer_id'] ?? '') == $c['id'] ? 'selected' : '' ?>>
                                                    <?= e($c['full_name']) ?> — <?= e($c['id_number']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <?php if (!empty($errors['customer_id'])): ?>
                                        <span class="form-error"><?= e($errors['customer_id']) ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="car_id">
                                        <?= e('car') ?> 
                                        <span class="required">*</span>
                                    </label>
                                    <select id="car_id" name="car_id" required
                                            class="<?= !empty($errors['car_id']) ? 'error' : '' ?>">
                                        <option value=""><?= e('select_car') ?></option>
                                        <?php if (!empty($cars)): ?>
                                            <?php foreach ($cars as $car): ?>
                                                <option value="<?= (int)$car['id'] ?>"
                                                    data-daily-price="<?= (float)$car['daily_price'] ?>"
                                                    data-weekly-price="<?= (float)$car['weekly_price'] ?>"
                                                    data-monthly-price="<?= (float)$car['monthly_price'] ?>"
                                                    <?= ($formData['car_id'] ?? '') == $car['id'] ? 'selected' : '' ?>>
                                                    <?= e($car['plate_number']) ?> — 
                                                    <?= e($car['brand']) ?> <?= e($car['model']) ?>
                                                    (<?= number_format((float)$car['daily_price'], 2) ?> <?= e('sar') ?>/<?= e('day') ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <?php if (!empty($errors['car_id'])): ?>
                                        <span class="form-error"><?= e($errors['car_id']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 2. المدة والمواعيد -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">2</span>
                                <?= e('duration_and_dates') ?>
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="pickup_datetime">
                                        <?= e('pickup_datetime') ?> 
                                        <span class="required">*</span>
                                    </label>
                                    <input type="datetime-local" 
                                           id="pickup_datetime" 
                                           name="pickup_datetime" 
                                           value="<?= e($formData['pickup_datetime'] ?? date('Y-m-d\TH:i')) ?>" 
                                           required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="return_due_datetime">
                                        <?= e('return_due_datetime') ?> 
                                        <span class="required">*</span>
                                    </label>
                                    <input type="datetime-local" 
                                           id="return_due_datetime" 
                                           name="return_due_datetime" 
                                           value="<?= e($formData['return_due_datetime'] ?? date('Y-m-d\TH:i', strtotime('+1 day'))) ?>" 
                                           required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="pickup_location"><?= e('pickup_location') ?></label>
                                    <input type="text" 
                                           id="pickup_location" 
                                           name="pickup_location" 
                                           value="<?= e($formData['pickup_location'] ?? '') ?>"
                                           maxlength="150">
                                </div>
                                
                                <div class="form-group">
                                    <label for="return_location"><?= e('return_location') ?></label>
                                    <input type="text" 
                                           id="return_location" 
                                           name="return_location" 
                                           value="<?= e($formData['return_location'] ?? '') ?>"
                                           maxlength="150">
                                </div>
                            </div>
                            
                            <div class="duration-info" id="durationInfo" style="display:none;">
                                <div class="duration-badge">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                        <line x1="16" y1="2" x2="16" y2="6"></line>
                                        <line x1="8" y1="2" x2="8" y2="6"></line>
                                        <line x1="3" y1="10" x2="21" y2="10"></line>
                                    </svg>
                                    <span><?= e('duration') ?>:</span>
                                    <strong id="durationDays">0</strong>
                                    <span><?= e('days') ?></span>
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
                                    <label for="rate_type"><?= e('rate_type') ?></label>
                                    <select id="rate_type" name="rate_type">
                                        <option value="daily" <?= ($formData['rate_type'] ?? 'daily') === 'daily' ? 'selected' : '' ?>>
                                            <?= e('daily') ?>
                                        </option>
                                        <option value="weekly" <?= ($formData['rate_type'] ?? '') === 'weekly' ? 'selected' : '' ?>>
                                            <?= e('weekly') ?>
                                        </option>
                                        <option value="monthly" <?= ($formData['rate_type'] ?? '') === 'monthly' ? 'selected' : '' ?>>
                                            <?= e('monthly') ?>
                                        </option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="rate_amount">
                                        <?= e('rate_amount') ?> 
                                        <small class="hint">(<?= e('sar') ?>)</small>
                                        <span class="required">*</span>
                                    </label>
                                    <input type="number" 
                                           id="rate_amount" 
                                           name="rate_amount" 
                                           value="<?= e($formData['rate_amount'] ?? '') ?>"
                                           step="0.01" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="discount">
                                        <?= e('discount') ?> 
                                        <small class="hint">(<?= e('sar') ?>)</small>
                                    </label>
                                    <input type="number" 
                                           id="discount" 
                                           name="discount" 
                                           value="<?= e($formData['discount'] ?? '0') ?>"
                                           step="0.01" min="0">
                                </div>
                                
                                <div class="form-group">
                                    <label for="tax_amount">
                                        <?= e('tax_amount') ?> 
                                        <small class="hint">(<?= e('sar') ?>)</small>
                                    </label>
                                    <input type="number" 
                                           id="tax_amount" 
                                           name="tax_amount" 
                                           value="<?= e($formData['tax_amount'] ?? '0') ?>"
                                           step="0.01" min="0">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="extra_charges">
                                        <?= e('extra_charges') ?> 
                                        <small class="hint">(<?= e('sar') ?>)</small>
                                    </label>
                                    <input type="number" 
                                           id="extra_charges" 
                                           name="extra_charges" 
                                           value="<?= e($formData['extra_charges'] ?? '0') ?>"
                                           step="0.01" min="0">
                                </div>
                                
                                <div class="form-group">
                                    <label for="deposit_amount">
                                        <?= e('deposit_amount') ?> 
                                        <small class="hint">(<?= e('sar') ?>)</small>
                                    </label>
                                    <input type="number" 
                                           id="deposit_amount" 
                                           name="deposit_amount" 
                                           value="<?= e($formData['deposit_amount'] ?? '0') ?>"
                                           step="0.01" min="0">
                                </div>
                                
                                <div class="form-group">
                                    <label for="km_limit_per_day"><?= e('km_limit_per_day') ?></label>
                                    <input type="number" 
                                           id="km_limit_per_day" 
                                           name="km_limit_per_day" 
                                           value="<?= e($formData['km_limit_per_day'] ?? '') ?>"
                                           min="0"
                                           placeholder="<?= e('optional') ?>">
                                </div>
                            </div>
                            
                            <div class="total-summary" id="totalSummary">
                                <div class="summary-row">
                                    <span><?= e('subtotal') ?>:</span>
                                    <strong id="subtotalDisplay">0.00 <?= e('sar') ?></strong>
                                </div>
                                <div class="summary-row">
                                    <span><?= e('discount') ?>:</span>
                                    <strong class="text-danger" id="discountDisplay">-0.00 <?= e('sar') ?></strong>
                                </div>
                                <div class="summary-row">
                                    <span><?= e('tax_amount') ?>:</span>
                                    <strong id="taxDisplay">0.00 <?= e('sar') ?></strong>
                                </div>
                                <div class="summary-row">
                                    <span><?= e('extra_charges') ?>:</span>
                                    <strong id="extraDisplay">0.00 <?= e('sar') ?></strong>
                                </div>
                                <div class="summary-row">
                                    <span><?= e('deposit_amount') ?>:</span>
                                    <strong id="depositDisplay">0.00 <?= e('sar') ?></strong>
                                </div>
                                <div class="summary-row summary-row-total">
                                    <span><?= e('total_amount') ?>:</span>
                                    <strong id="totalDisplay">0.00 <?= e('sar') ?></strong>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 4. الدفعة الأولى -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">4</span>
                                <?= e('first_payment') ?>
                            </h3>
                            
                            <div class="info-box">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="16" x2="12" y2="12"></line>
                                    <line x1="12" y1="8" x2="12.01" y2="8"></line>
                                </svg>
                                <span><?= e('first_payment_hint') ?></span>
                            </div>
                            
                            <div class="form-group checkbox-group">
                                <label class="checkbox-wrapper">
                                    <input type="checkbox" 
                                           id="add_first_payment" 
                                           name="add_first_payment" 
                                           value="1"
                                           <?= !empty($formData['add_first_payment']) ? 'checked' : '' ?>>
                                    <span class="checkbox-custom"></span>
                                    <span><?= e('add_first_payment') ?></span>
                                </label>
                            </div>
                            
                            <div id="firstPaymentFields" style="<?= !empty($formData['add_first_payment']) ? '' : 'display:none;' ?>">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_payment_amount">
                                            <?= e('payment_amount') ?> 
                                            <small class="hint">(<?= e('sar') ?>)</small>
                                        </label>
                                        <input type="number" 
                                               id="first_payment_amount" 
                                               name="first_payment_amount" 
                                               value="<?= e($formData['first_payment_amount'] ?? '') ?>"
                                               step="0.01" min="0">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="first_payment_method"><?= e('payment_method') ?></label>
                                        <select id="first_payment_method" name="first_payment_method">
                                            <option value="cash" <?= ($formData['first_payment_method'] ?? 'cash') === 'cash' ? 'selected' : '' ?>>
                                                💵 <?= e('cash') ?>
                                            </option>
                                            <option value="network" <?= ($formData['first_payment_method'] ?? '') === 'network' ? 'selected' : '' ?>>
                                                💳 <?= e('network') ?>
                                            </option>
                                            <option value="bank_transfer" <?= ($formData['first_payment_method'] ?? '') === 'bank_transfer' ? 'selected' : '' ?>>
                                                🏦 <?= e('bank_transfer') ?>
                                            </option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="first_payment_type"><?= e('payment_type') ?></label>
                                        <select id="first_payment_type" name="first_payment_type">
                                            <option value="rent" <?= ($formData['first_payment_type'] ?? 'rent') === 'rent' ? 'selected' : '' ?>>
                                                <?= e('rent') ?>
                                            </option>
                                            <option value="deposit" <?= ($formData['first_payment_type'] ?? '') === 'deposit' ? 'selected' : '' ?>>
                                                <?= e('deposit') ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="first_payment_reference"><?= e('reference_no') ?></label>
                                        <input type="text" 
                                               id="first_payment_reference" 
                                               name="first_payment_reference" 
                                               value="<?= e($formData['first_payment_reference'] ?? '') ?>"
                                               maxlength="50">
                                    </div>
                                    
                                    <div class="form-group" style="grid-column: span 2;">
                                        <label for="first_payment_notes"><?= e('payment_notes') ?></label>
                                        <input type="text" 
                                               id="first_payment_notes" 
                                               name="first_payment_notes" 
                                               value="<?= e($formData['first_payment_notes'] ?? '') ?>"
                                               maxlength="255">
                                    </div>
                                </div>
                                
                                <div class="payment-summary" id="paymentSummary">
                                    <div class="summary-row">
                                        <span><?= e('total_amount') ?>:</span>
                                        <strong id="paymentTotalDisplay">0.00 <?= e('sar') ?></strong>
                                    </div>
                                    <div class="summary-row">
                                        <span><?= e('payment_amount') ?>:</span>
                                        <strong class="text-success" id="paymentAmountDisplay">0.00 <?= e('sar') ?></strong>
                                    </div>
                                    <div class="summary-row summary-row-total">
                                        <span><?= e('remaining_after_payment') ?>:</span>
                                        <strong id="remainingAfterPayment">0.00 <?= e('sar') ?></strong>
                                    </div>
                                </div>
                                
                                <!-- ✅ الدفعة القادمة -->
                                <div id="nextPaymentSection" style="display:none; margin-top:20px;">
                                    <div class="info-box" style="background:var(--warning-light);border-color:var(--warning-color);color:var(--warning-color);">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12 6 12 12 16 14"></polyline>
                                        </svg>
                                        <span><?= e('next_payment_hint') ?></span>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="next_payment_date">
                                                <?= e('next_payment_date') ?> 
                                                <span class="required">*</span>
                                            </label>
                                            <input type="date" 
                                                   id="next_payment_date" 
                                                   name="next_payment_date" 
                                                   value="<?= e($formData['next_payment_date'] ?? date('Y-m-d', strtotime('+30 days'))) ?>"
                                                   min="<?= date('Y-m-d') ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="next_payment_amount">
                                                <?= e('next_payment_amount') ?> 
                                                <small class="hint">(<?= e('sar') ?>)</small>
                                            </label>
                                            <input type="number" 
                                                   id="next_payment_amount" 
                                                   name="next_payment_amount" 
                                                   value="<?= e($formData['next_payment_amount'] ?? '') ?>"
                                                   step="0.01" min="0">
                                            <small class="hint"><?= e('next_payment_amount_hint') ?></small>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="payment_reminder_days"><?= e('payment_reminder_days') ?></label>
                                            <select id="payment_reminder_days" name="payment_reminder_days">
                                                <option value="1"><?= e('1_day_before') ?></option>
                                                <option value="3" selected><?= e('3_days_before') ?></option>
                                                <option value="5"><?= e('5_days_before') ?></option>
                                                <option value="7"><?= e('7_days_before') ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 5. ملاحظات -->
                        <div class="form-section">
                            <h3 class="form-section-title">
                                <span class="section-number">5</span>
                                <?= e('notes') ?>
                            </h3>
                            
                            <div class="form-group">
                                <label for="notes"><?= e('additional_notes') ?></label>
                                <textarea id="notes" 
                                          name="notes" 
                                          rows="4" 
                                          maxlength="1000"><?= e($formData['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                    </div>
                    
                    <div class="form-actions">
                        <a href="<?= url('/rentals') ?>" class="btn btn-secondary">
                            <?= e('cancel') ?>
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            <?= e('create_rental_btn') ?>
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
            translations: {
                sar: '<?= e('sar') ?>',
            }
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            
            // العناصر
            const pickupInput = document.getElementById('pickup_datetime');
            const returnInput = document.getElementById('return_due_datetime');
            const rateTypeSelect = document.getElementById('rate_type');
            const rateAmountInput = document.getElementById('rate_amount');
            const discountInput = document.getElementById('discount');
            const taxInput = document.getElementById('tax_amount');
            const extraInput = document.getElementById('extra_charges');
            const depositInput = document.getElementById('deposit_amount'); // تمت إضافة متغير العربون/التأمين
            const carSelect = document.getElementById('car_id');
            
            const durationInfo = document.getElementById('durationInfo');
            const durationDays = document.getElementById('durationDays');
            
            const subtotalDisplay = document.getElementById('subtotalDisplay');
            const discountDisplay = document.getElementById('discountDisplay');
            const taxDisplay = document.getElementById('taxDisplay');
            const extraDisplay = document.getElementById('extraDisplay');
            const depositDisplay = document.getElementById('depositDisplay'); // لعرض التأمين في الإجمالي
            const totalDisplay = document.getElementById('totalDisplay');
            
            const addFirstPayment = document.getElementById('add_first_payment');
            const firstPaymentFields = document.getElementById('firstPaymentFields');
            const paymentAmountInput = document.getElementById('first_payment_amount');
            const paymentSummary = document.getElementById('paymentSummary');
            const paymentTotalDisplay = document.getElementById('paymentTotalDisplay');
            const paymentAmountDisplay = document.getElementById('paymentAmountDisplay');
            const remainingAfterPayment = document.getElementById('remainingAfterPayment');
            
            const nextPaymentSection = document.getElementById('nextPaymentSection');
            const nextPaymentAmount = document.getElementById('next_payment_amount');
            
            const sar = window.APP_CONFIG.translations.sar;
            
            // ============================================================
            // حساب الإجمالي
            // ============================================================
            function calculateTotals() {
                let days = 0;
                
                if (pickupInput.value && returnInput.value) {
                    const pickup = new Date(pickupInput.value);
                    const returnDue = new Date(returnInput.value);
                    const diffMs = returnDue - pickup;
                    
                    if (diffMs > 0) {
                        days = Math.max(1, Math.ceil(diffMs / (1000 * 60 * 60 * 24)));
                        durationDays.textContent = days;
                        durationInfo.style.display = 'block';
                    }
                }
                
                const rate = parseFloat(rateAmountInput.value) || 0;
                const disc = parseFloat(discountInput.value) || 0;
                const tax = parseFloat(taxInput.value) || 0;
                const extra = parseFloat(extraInput.value) || 0;
                const deposit = parseFloat(depositInput.value) || 0; // سحب قيمة التأمين
                
                const subtotal = rate * Math.max(1, days);
                // تم دمج التأمين مع الإجمالي
                const total = subtotal - disc + tax + extra + deposit; 
                
                subtotalDisplay.textContent = subtotal.toFixed(2) + ' ' + sar;
                discountDisplay.textContent = '-' + disc.toFixed(2) + ' ' + sar;
                taxDisplay.textContent = tax.toFixed(2) + ' ' + sar;
                extraDisplay.textContent = extra.toFixed(2) + ' ' + sar;
                depositDisplay.textContent = deposit.toFixed(2) + ' ' + sar; // عرض التأمين
                totalDisplay.textContent = total.toFixed(2) + ' ' + sar;
                
                return total;
            }
            
            // ============================================================
            // ملخص الدفعة
            // ============================================================
            function updatePaymentSummary() {
                if (!addFirstPayment.checked) {
                    paymentSummary.style.display = 'none';
                    nextPaymentSection.style.display = 'none';
                    return;
                }
                
                const total = calculateTotals();
                const paymentAmount = parseFloat(paymentAmountInput.value) || 0;
                const remaining = total - paymentAmount;
                
                paymentTotalDisplay.textContent = total.toFixed(2) + ' ' + sar;
                paymentAmountDisplay.textContent = paymentAmount.toFixed(2) + ' ' + sar;
                remainingAfterPayment.textContent = remaining.toFixed(2) + ' ' + sar;
                
                if (remaining <= 0) {
                    remainingAfterPayment.classList.remove('text-danger');
                    remainingAfterPayment.classList.add('text-success');
                    nextPaymentSection.style.display = 'none';
                } else {
                    remainingAfterPayment.classList.remove('text-success');
                    remainingAfterPayment.classList.add('text-danger');
                    nextPaymentSection.style.display = 'block';
                    
                    if (!nextPaymentAmount.value) {
                        nextPaymentAmount.value = remaining.toFixed(2);
                    }
                }
                
                paymentSummary.style.display = 'block';
            }
            
            // ============================================================
            // تحديث السعر من السيارة
            // ============================================================
            function updatePriceFromCar() {
                const selected = carSelect.options[carSelect.selectedIndex];
                if (!selected || !selected.value) return;
                
                const rateType = rateTypeSelect.value;
                let price = 0;
                
                if (rateType === 'daily') price = parseFloat(selected.dataset.dailyPrice) || 0;
                else if (rateType === 'weekly') price = parseFloat(selected.dataset.weeklyPrice) || 0;
                else if (rateType === 'monthly') price = parseFloat(selected.dataset.monthlyPrice) || 0;
                
                if (price > 0 && !rateAmountInput.value) {
                    rateAmountInput.value = price.toFixed(2);
                }
                
                calculateTotals();
                updatePaymentSummary();
            }
            
            // ============================================================
            // ربط الأحداث
            // ============================================================
            // تم إضافة حقل التأمين لمستمعي الأحداث
            [pickupInput, returnInput, rateAmountInput, discountInput, taxInput, extraInput, depositInput].forEach(el => {
                if (el) el.addEventListener('input', function() {
                    calculateTotals();
                    updatePaymentSummary();
                });
            });
            
            if (rateTypeSelect) rateTypeSelect.addEventListener('change', updatePriceFromCar);
            if (carSelect) carSelect.addEventListener('change', updatePriceFromCar);
            
            if (addFirstPayment) {
                addFirstPayment.addEventListener('change', function() {
                    if (this.checked) {
                        firstPaymentFields.style.display = 'block';
                        
                        if (!paymentAmountInput.value) {
                            const total = calculateTotals();
                            if (total > 0) paymentAmountInput.value = total.toFixed(2);
                        }
                    } else {
                        firstPaymentFields.style.display = 'none';
                    }
                    updatePaymentSummary();
                });
            }
            
            if (paymentAmountInput) paymentAmountInput.addEventListener('input', updatePaymentSummary);
            
            // الحساب الأولي
            calculateTotals();
            updatePaymentSummary();
        });
    </script>
    <script src="<?= url('/assets/js/theme.js') ?>"></script>
    <script src="<?= url('/assets/js/dashboard.js') ?>"></script>
</body>
</html>