<?php
/**
 * قالب طباعة العقد
 * - PDF يملأ صفحة A4 بالكامل
 * - توقيعان: رسم + كتابة
 * - جميع النصوص مترجمة
 */
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>">
<head>
    <meta charset="UTF-8">
    <title><?= e('contract_title') ?> <?= e($rental['contract_no']) ?> | <?= e('print') ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&family=Amiri:wght@400;700&family=Reem+Kufi:wght@400;600&family=Dancing+Script:wght@500;700&family=Great+Vibes&display=swap" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Cairo', sans-serif; 
            background: #e2e8f0; 
            padding: 20px; 
            color: #1e293b;
        }
        .print-container { 
            max-width: 900px; 
            margin: 0 auto; 
            background: white; 
            padding: 40px; 
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
        }
        
        /* Header */
        .print-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding-bottom: 25px; 
            border-bottom: 2px solid #e2e8f0; 
            margin-bottom: 30px; 
        }
        .office-info h1 { 
            font-size: 1.6rem; 
            color: #0f172a; 
            font-weight: 800;
            margin-bottom: 8px; 
        }
        .office-info p { 
            font-size: 0.9rem; 
            color: #64748b; 
            margin-bottom: 4px; 
        }
        .contract-title { 
            text-align: left; 
            background: #f8fafc;
            padding: 15px 25px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .contract-title h2 { 
            font-size: 1.4rem; 
            color: #2563eb; 
            margin-bottom: 6px; 
            font-weight: 800;
        }
        .contract-title p { 
            font-size: 0.95rem; 
            color: #475569; 
            margin-bottom: 3px;
        }
        .logo { font-size: 3.5rem; text-align: center; }
        
        /* Sections */
        .section { margin-bottom: 30px; }
        .section-title { 
            background: #f1f5f9; 
            color: #0f172a; 
            padding: 10px 16px; 
            border-radius: 6px; 
            font-size: 1.1rem; 
            font-weight: 700; 
            margin-bottom: 15px; 
            border-right: 4px solid #2563eb;
        }
        .info-grid { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 15px; 
        }
        .info-row { 
            display: flex; 
            justify-content: space-between; 
            padding: 10px 15px; 
            background: #f8fafc; 
            border-radius: 6px; 
            font-size: 0.95rem;
            border: 1px solid #f1f5f9;
        }
        .info-row span { color: #64748b; }
        .info-row strong { color: #0f172a; font-weight: 700; }
        
        /* Table */
        table { 
            width: 100%; 
            border-collapse: separate; 
            border-spacing: 0;
            margin-top: 10px; 
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        table th, table td { 
            padding: 12px 15px; 
            text-align: right; 
            font-size: 0.95rem; 
            border-bottom: 1px solid #e2e8f0;
        }
        table tr:last-child td { border-bottom: none; }
        table th { 
            background: #f8fafc; 
            font-weight: 700; 
            color: #475569; 
            text-transform: uppercase;
            font-size: 0.85rem;
        }
        table .total-row { background: #eff6ff; }
        table .total-row td {
            font-weight: 800;
            color: #1e40af;
            font-size: 1.05rem;
        }
        
        /* Terms */
        .terms { 
            background: #fefce8; 
            border: 1px solid #fef08a; 
            padding: 20px; 
            border-radius: 8px; 
            font-size: 0.85rem; 
            line-height: 1.8; 
            color: #854d0e; 
        }
        
        /* Signatures */
        .signatures { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 40px; 
            margin-top: 50px; 
            padding-top: 40px; 
            border-top: 1px solid #e2e8f0; 
        }
        .signature-box { 
            text-align: center; 
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .signature-box > p { 
            font-size: 1rem; 
            color: #475569; 
            font-weight: 600; 
            margin-bottom: 10px;
        }
        
        /* Tabs */
        .sig-tabs {
            display: flex;
            gap: 4px;
            width: 100%;
            margin-bottom: 8px;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 8px;
        }
        .sig-tab {
            flex: 1;
            padding: 8px 12px;
            background: transparent;
            border: none;
            border-radius: 6px;
            font-family: inherit;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
        }
        .sig-tab.active {
            background: white;
            color: #2563eb;
            box-shadow: 0 2px 6px rgba(0,0,0,0.08);
        }
        .sig-tab:hover:not(.active) { color: #334155; }
        
        .sig-panel { width: 100%; display: none; }
        .sig-panel.active { display: block; }
        
        .canvas-container {
            border: 1.5px dashed #94a3b8;
            border-radius: 8px;
            background: #f8fafc;
            width: 100%;
            height: 150px;
            position: relative;
            overflow: hidden;
        }
        canvas {
            display: block;
            width: 100%;
            height: 100%;
            cursor: crosshair;
            touch-action: none;
        }
        
        .type-container {
            border: 1.5px dashed #94a3b8;
            border-radius: 8px;
            background: #f8fafc;
            padding: 12px;
            height: 150px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            transition: border-color 0.3s, background 0.3s;
        }
        .type-container.signed {
            border: 1.5px solid #10b981;
            background: white;
            padding: 6px;
        }
        .type-container.signed .type-input,
        .type-container.signed .type-preview,
        .type-container.signed .font-selector {
            display: none !important;
        }
        .type-container.signed .signature-image {
            display: block !important;
        }
        
        .type-input {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-family: 'Cairo', sans-serif;
            font-size: 1rem;
            text-align: center;
            background: white;
            outline: none;
            transition: border-color 0.2s;
        }
        .type-input:focus { border-color: #2563eb; }
        
        .type-preview {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #0f172a;
            background: white;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            padding: 6px;
            overflow: hidden;
            white-space: nowrap;
        }
        
        .font-selector {
            display: flex;
            gap: 4px;
            margin-top: 6px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .font-option {
            padding: 5px 10px;
            border: 1px solid #e2e8f0;
            background: white;
            border-radius: 5px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.2s;
            color: #475569;
        }
        .font-option:hover { border-color: #94a3b8; }
        .font-option.active {
            border-color: #2563eb;
            background: #eff6ff;
            color: #1e40af;
        }
        
        .signature-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: none;
        }
        
        .sig-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            justify-content: center;
        }
        .btn-sig {
            padding: 7px 16px;
            font-size: 0.82rem;
            border-radius: 6px;
            cursor: pointer;
            border: none;
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-clear { background: #e2e8f0; color: #475569; }
        .btn-clear:hover { background: #cbd5e1; }
        
        .btn-save { background: #10b981; color: white; }
        .btn-save:hover { background: #059669; }
        
        .print-footer { 
            margin-top: 40px; 
            padding-top: 20px; 
            border-top: 1px solid #e2e8f0; 
            text-align: center; 
            font-size: 0.8rem; 
            color: #94a3b8; 
        }
        
        /* Control buttons */
        .print-actions { 
            position: fixed; 
            top: 20px; 
            left: 20px; 
            display: flex; 
            gap: 10px; 
            z-index: 100; 
        }
        
        .btn-action {
            padding: 12px 24px;
            color: white;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            white-space: nowrap;
        }
        .btn-action:hover { transform: translateY(-1px); }
        .btn-action:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
        
        .btn-print { background: #2563eb; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
        .btn-print:hover { background: #1d4ed8; }
        
        .btn-pdf { background: #16a34a; box-shadow: 0 4px 12px rgba(22, 163, 74, 0.2); }
        .btn-pdf:hover { background: #15803d; }
        
        .btn-close-print { background: #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); }
        .btn-close-print:hover { background: #dc2626; }
        
        .spinner-inline {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        /* Print mode */
        @media print {
            body { background: white; padding: 0; }
            .print-container { box-shadow: none; padding: 0; max-width: 100%; border-radius: 0; }
            .print-actions, .sig-actions, .sig-tabs, .font-selector { display: none !important; }
            .section { page-break-inside: avoid; }
            .terms { border-color: #ccc; color: #333; background: transparent; }
            table { border-color: #ccc; }
            table th { background: #f5f5f5; color: #000; }
            .total-row { background: #f5f5f5 !important; }
            .total-row td { color: #000; }
            .info-row { border-color: #eee; }
            .canvas-container, .type-container { border-color: transparent !important; background: transparent !important; }
            canvas { display: none !important; }
            .signature-image { display: block !important; }
            .type-input { display: none !important; }
            .type-preview { border: none !important; background: transparent !important; }
        }
        
        /* ============================================================
           ✅ وضع PDF — ضغط كل شيء ليملأ A4 بالكامل
           ============================================================ */
        .pdf-mode { 
            background: white !important; 
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .pdf-mode .print-actions,
        .pdf-mode .sig-actions,
        .pdf-mode .sig-tabs,
        .pdf-mode .font-selector,
        .pdf-mode .type-input { 
            display: none !important; 
        }
        
        .pdf-mode .print-container {
            box-shadow: none !important;
            border-radius: 0 !important;
            padding: 8mm !important;
            margin: 0 auto !important;
            max-width: 210mm !important;
            width: 210mm !important;
            min-height: 297mm !important;
            font-size: 0.72rem !important;
            background: white !important;
        }
        
        .pdf-mode .print-header {
            padding-bottom: 8px !important;
            margin-bottom: 10px !important;
        }
        
        .pdf-mode .office-info h1 {
            font-size: 1rem !important;
            margin-bottom: 3px !important;
        }
        
        .pdf-mode .office-info p {
            font-size: 0.65rem !important;
            margin-bottom: 1px !important;
        }
        
        .pdf-mode .contract-title {
            padding: 6px 12px !important;
        }
        
        .pdf-mode .contract-title h2 {
            font-size: 0.9rem !important;
            margin-bottom: 2px !important;
        }
        
        .pdf-mode .contract-title p {
            font-size: 0.65rem !important;
            margin-bottom: 1px !important;
        }
        
        .pdf-mode .logo { font-size: 1.8rem !important; }
        
        .pdf-mode .section { margin-bottom: 10px !important; }
        
        .pdf-mode .section-title {
            padding: 5px 10px !important;
            font-size: 0.8rem !important;
            margin-bottom: 6px !important;
        }
        
        .pdf-mode .info-grid {
            gap: 5px !important;
        }
        
        .pdf-mode .info-row {
            padding: 4px 8px !important;
            font-size: 0.7rem !important;
        }
        
        .pdf-mode table th,
        .pdf-mode table td {
            padding: 4px 8px !important;
            font-size: 0.7rem !important;
        }
        
        .pdf-mode table .total-row td {
            font-size: 0.75rem !important;
        }
        
        .pdf-mode .terms {
            padding: 8px !important;
            font-size: 0.65rem !important;
            line-height: 1.4 !important;
        }
        
        .pdf-mode .signatures {
            gap: 15px !important;
            margin-top: 15px !important;
            padding-top: 10px !important;
        }
        
        .pdf-mode .signature-box > p {
            font-size: 0.75rem !important;
            margin-bottom: 4px !important;
        }
        
        .pdf-mode .canvas-container,
        .pdf-mode .type-container {
            height: 70px !important;
        }
        
        .pdf-mode canvas { display: none !important; }
        .pdf-mode .signature-image { display: block !important; }
        .pdf-mode .canvas-container,
        .pdf-mode .type-container { 
            border-color: transparent !important; 
            background: transparent !important; 
        }
        .pdf-mode .type-preview { 
            border: none !important; 
            background: transparent !important; 
        }
        
        .pdf-mode .print-footer {
            margin-top: 15px !important;
            padding-top: 8px !important;
            font-size: 0.6rem !important;
        }
    </style>
</head>
<body>

<!-- أزرار التحكم -->
<div class="print-actions">
    <button class="btn-action btn-print" onclick="window.print()">
        🖨️ <?= e('print') ?>
    </button>
    
    <button class="btn-action btn-pdf" id="btnPdf" onclick="downloadPDF()">
        <span class="btn-pdf-icon">📄</span>
        <span class="btn-pdf-text"><?= e('download_pdf') ?></span>
    </button>
    
    <button class="btn-action btn-close-print" onclick="window.close()">
        ✕ <?= e('close') ?>
    </button>
</div>

<div class="print-container" id="printContainer">
    
    <!-- Header -->
    <div class="print-header">
        <div class="office-info">
            <h1><?= e($office['office_name'] ?? 'مكتب تأجير السيارات') ?></h1>
            <?php if (!empty($office['office_phone'])): ?>
                <p>📞 <?= e($office['office_phone']) ?></p>
            <?php endif; ?>
            <?php if (!empty($office['office_address'])): ?>
                <p>📍 <?= e($office['office_address']) ?></p>
            <?php endif; ?>
            <?php if (!empty($office['tax_number'])): ?>
                <p>🏢 <?= e('tax_number') ?>: <?= e($office['tax_number']) ?></p>
            <?php endif; ?>
        </div>
        <div class="logo">🚗</div>
        <div class="contract-title">
            <h2><?= e('contract_title') ?></h2>
            <p><?= e('contract_no') ?>: <strong><?= e($rental['contract_no']) ?></strong></p>
            <p><?= e('print_date') ?>: <strong><?= e(date('Y-m-d h:i A')) ?></strong></p>
        </div>
    </div>
    
    <!-- بيانات المنتفع -->
    <div class="section">
        <div class="section-title">👤 <?= e('customer_info') ?></div>
        <div class="info-grid">
            <div class="info-row"><span><?= e('full_name') ?>:</span><strong><?= e($customer['full_name'] ?? '') ?></strong></div>
            <div class="info-row"><span><?= e('phone') ?>:</span><strong><bdi><?= e($customer['phone'] ?? '') ?></bdi></strong></div>
            <div class="info-row"><span><?= e('id_type') ?>:</span><strong><?= e($customer['id_type'] ?? '') ?></strong></div>
            <div class="info-row"><span><?= e('id_number') ?>:</span><strong><?= e($customer['id_number'] ?? '') ?></strong></div>
            <?php if (!empty($customer['nationality'])): ?>
                <div class="info-row"><span><?= e('nationality') ?>:</span><strong><?= e($customer['nationality']) ?></strong></div>
            <?php endif; ?>
            <?php if (!empty($customer['license_number'])): ?>
                <div class="info-row"><span><?= e('license_number') ?>:</span><strong><?= e($customer['license_number']) ?></strong></div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- بيانات السيارة -->
    <div class="section">
        <div class="section-title">🚗 <?= e('car_info') ?></div>
        <div class="info-grid">
            <div class="info-row"><span><?= e('plate_number') ?>:</span><strong><?= e($car['plate_number'] ?? '') ?></strong></div>
            <div class="info-row"><span><?= e('brand_model') ?>:</span><strong><?= e(($car['brand'] ?? '') . ' ' . ($car['model'] ?? '')) ?></strong></div>
            <div class="info-row"><span><?= e('year') ?>:</span><strong><?= e((string)($car['year'] ?? '')) ?></strong></div>
            <div class="info-row"><span><?= e('color') ?>:</span><strong><?= e($car['color'] ?? '') ?></strong></div>
            <div class="info-row"><span><?= e('odometer') ?>:</span><strong><?= number_format((int)($car['odometer'] ?? 0)) ?> <?= e('km') ?? 'كم' ?></strong></div>
            <div class="info-row"><span><?= e('chassis_number') ?>:</span><strong><?= e($car['chassis_number'] ?? '—') ?></strong></div>
        </div>
    </div>
    
    <!-- تفاصيل الانتفاع -->
    <div class="section">
        <div class="section-title">📄 <?= e('rental_details') ?></div>
        <div class="info-grid">
            <div class="info-row"><span><?= e('pickup_datetime') ?>:</span><strong><?= e(format_datetime($rental['pickup_datetime'])) ?></strong></div>
            <div class="info-row"><span><?= e('return_due_datetime') ?>:</span><strong><?= e(format_datetime($rental['return_due_datetime'])) ?></strong></div>
            <div class="info-row"><span><?= e('pickup_location') ?>:</span><strong><?= e($rental['pickup_location'] ?? '—') ?></strong></div>
            <div class="info-row"><span><?= e('return_location') ?>:</span><strong><?= e($rental['return_location'] ?? '—') ?></strong></div>
            <div class="info-row"><span><?= e('rental_days') ?>:</span><strong><?= e((string)($rental['rental_days'] ?? 0)) ?> <?= e('days') ?></strong></div>
        </div>
    </div>
    
    <!-- المبالغ -->
    <div class="section">
        <div class="section-title">💰 <?= e('financial_details') ?></div>
        <table>
            <thead>
                <tr>
                    <th><?= e('description') ?></th>
                    <th style="width: 150px;"><?= e('amount') ?> (<?= e('sar') ?>)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?= e('rate_amount') ?> (<?= e($rental['rate_type']) ?>) × <?= (int)$rental['rental_days'] ?> <?= e('days') ?></td>
                    <td><?= number_format((float)$rental['subtotal'], 2) ?></td>
                </tr>
                <?php if ((float)$rental['discount'] > 0): ?>
                    <tr><td><?= e('discount') ?></td><td>-<?= number_format((float)$rental['discount'], 2) ?></td></tr>
                <?php endif; ?>
                <?php if ((float)$rental['tax_amount'] > 0): ?>
                    <tr><td><?= e('tax_amount') ?></td><td><?= number_format((float)$rental['tax_amount'], 2) ?></td></tr>
                <?php endif; ?>
                <?php if ((float)$rental['extra_charges'] > 0): ?>
                    <tr><td><?= e('extra_charges') ?></td><td><?= number_format((float)$rental['extra_charges'], 2) ?></td></tr>
                <?php endif; ?>
                <?php if ((float)$rental['deposit_amount'] > 0): ?>
                    <tr><td><?= e('deposit_amount') ?></td><td><?= number_format((float)$rental['deposit_amount'], 2) ?></td></tr>
                <?php endif; ?>
                <tr class="total-row">
                    <td><?= e('total_amount') ?></td>
                    <td><?= number_format((float)$rental['total_amount'], 2) ?></td>
                </tr>
                <tr>
                    <td><strong><?= e('paid_amount') ?></strong></td>
                    <td><strong><?= number_format($paidAmount, 2) ?></strong></td>
                </tr>
                <tr class="total-row" style="background: #fef2f2;">
                    <td style="color: #991b1b;"><?= e('remaining') ?></td>
                    <td style="color: #991b1b;"><?= number_format($remaining, 2) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- المدفوعات -->
    <?php if (!empty($payments)): ?>
        <div class="section">
            <div class="section-title">💵 <?= e('payments_log') ?></div>
            <table>
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
                            <td><?= e($p['receipt_no']) ?></td>
                            <td><?= e($p['payment_type'] === 'rent' ? e('rent') : ($p['payment_type'] === 'penalty' ? e('penalty') : e('deposit_refund'))) ?></td>
                            <td><?= e($p['method'] === 'cash' ? e('cash') : ($p['method'] === 'network' ? e('network') : e('bank_transfer'))) ?></td>
                            <td><?= number_format((float)$p['amount'], 2) ?></td>
                            <td><?= e(date('Y-m-d h:i A', strtotime($p['paid_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <!-- الشروط -->
    <?php 
    if (!isset($termsText)) {
        $settings = new Setting();
        $terms = $settings->getContractTerms();
        $termsText = Lang::isArabic() ? ($terms['contract_terms_ar'] ?? '') : ($terms['contract_terms_en'] ?? '');
    }
    if (!empty($termsText)): 
    ?>
        <div class="section" style="page-break-inside: auto;">
            <div class="section-title">📜 <?= e('contract_terms') ?></div>
            <div class="terms"><?= nl2br(e($termsText)) ?></div>
        </div>
    <?php endif; ?>
    
    <!-- التوقيعات -->
    <div class="signatures">
        
        <!-- توقيع المنتفع -->
        <div class="signature-box">
            <p><?= e('signature_customer_label') ?></p>
            
            <?php if (!empty($rental['customer_signature'])): ?>
                <div class="canvas-container">
                    <img src="<?= url('/storage/uploads/signatures/' . $rental['customer_signature']) ?>" 
                         alt="<?= e('signature_customer_label') ?>"
                         class="signature-image"
                         style="display: block;">
                </div>
            <?php else: ?>
                <div class="sig-tabs">
                    <button type="button" class="sig-tab active" onclick="switchSigTab('customer', 'draw')">
                        ✍️ <?= e('signature_draw') ?>
                    </button>
                    <button type="button" class="sig-tab" onclick="switchSigTab('customer', 'type')">
                        ⌨️ <?= e('signature_type') ?>
                    </button>
                </div>
                
                <div class="sig-panel active" id="customer-draw-panel">
                    <div class="canvas-container">
                        <canvas id="customerSignature"></canvas>
                        <img id="customerSignatureImg" class="signature-image" alt="<?= e('signature_customer_label') ?>">
                    </div>
                </div>
                
                <div class="sig-panel" id="customer-type-panel">
                    <div class="type-container">
                        <input type="text" 
                               class="type-input" 
                               id="customerTypeInput" 
                               placeholder="<?= e('signature_placeholder_customer') ?>"
                               maxlength="50"
                               value="<?= e($customer['full_name'] ?? '') ?>"
                               oninput="updateTypePreview('customer')">
                        
                        <div class="type-preview" id="customerTypePreview" style="font-family: 'Amiri', serif;">
                            <?= e($customer['full_name'] ?? '') ?>
                        </div>
                        
                        <div class="font-selector" id="customerFontSelector">
                            <button type="button" class="font-option active" data-font="Amiri" onclick="selectFont('customer', 'Amiri')" style="font-family: 'Amiri', serif;"><?= e('font_amiri') ?></button>
                            <button type="button" class="font-option" data-font="Reem Kufi" onclick="selectFont('customer', 'Reem Kufi')" style="font-family: 'Reem Kufi', sans-serif;"><?= e('font_reem_kufi') ?></button>
                            <button type="button" class="font-option" data-font="Cairo" onclick="selectFont('customer', 'Cairo')" style="font-family: 'Cairo', sans-serif;"><?= e('font_cairo') ?></button>
                            <button type="button" class="font-option" data-font="Dancing Script" onclick="selectFont('customer', 'Dancing Script')" style="font-family: 'Dancing Script', cursive;"><?= e('font_dancing') ?></button>
                            <button type="button" class="font-option" data-font="Great Vibes" onclick="selectFont('customer', 'Great Vibes')" style="font-family: 'Great Vibes', cursive;"><?= e('font_vibes') ?></button>
                        </div>
                        
                        <img id="customerTypeImg" class="signature-image" alt="<?= e('signature_customer_label') ?>">
                    </div>
                </div>
                
                <div class="sig-actions">
                    <button type="button" class="btn-sig btn-clear" onclick="clearSignature('customer')"><?= e('signature_clear') ?></button>
                    <button type="button" class="btn-sig btn-save" onclick="saveSignature('customer')"><?= e('signature_confirm') ?></button>
                </div>
            <?php endif; ?>
            
            <strong style="margin-top: 8px;"><?= e($customer['full_name'] ?? '') ?></strong>
        </div>

        <!-- توقيع الإدارة -->
        <div class="signature-box">
            <p><?= e('signature_office_label') ?></p>
            
            <?php if (!empty($rental['office_signature'])): ?>
                <div class="canvas-container">
                    <img src="<?= url('/storage/uploads/signatures/' . $rental['office_signature']) ?>" 
                         alt="<?= e('signature_office_label') ?>"
                         class="signature-image"
                         style="display: block;">
                </div>
            <?php else: ?>
                <div class="sig-tabs">
                    <button type="button" class="sig-tab active" onclick="switchSigTab('office', 'draw')">
                        ✍️ <?= e('signature_draw') ?>
                    </button>
                    <button type="button" class="sig-tab" onclick="switchSigTab('office', 'type')">
                        ⌨️ <?= e('signature_type') ?>
                    </button>
                </div>
                
                <div class="sig-panel active" id="office-draw-panel">
                    <div class="canvas-container">
                        <canvas id="officeSignature"></canvas>
                        <img id="officeSignatureImg" class="signature-image" alt="<?= e('signature_office_label') ?>">
                    </div>
                </div>
                
                <div class="sig-panel" id="office-type-panel">
                    <div class="type-container">
                        <input type="text" 
                               class="type-input" 
                               id="officeTypeInput" 
                               placeholder="<?= e('signature_placeholder_office') ?>"
                               maxlength="50"
                               value="<?= e($office['office_name'] ?? '') ?>"
                               oninput="updateTypePreview('office')">
                        
                        <div class="type-preview" id="officeTypePreview" style="font-family: 'Amiri', serif;">
                            <?= e($office['office_name'] ?? '') ?>
                        </div>
                        
                        <div class="font-selector" id="officeFontSelector">
                            <button type="button" class="font-option active" data-font="Amiri" onclick="selectFont('office', 'Amiri')" style="font-family: 'Amiri', serif;"><?= e('font_amiri') ?></button>
                            <button type="button" class="font-option" data-font="Reem Kufi" onclick="selectFont('office', 'Reem Kufi')" style="font-family: 'Reem Kufi', sans-serif;"><?= e('font_reem_kufi') ?></button>
                            <button type="button" class="font-option" data-font="Cairo" onclick="selectFont('office', 'Cairo')" style="font-family: 'Cairo', sans-serif;"><?= e('font_cairo') ?></button>
                            <button type="button" class="font-option" data-font="Dancing Script" onclick="selectFont('office', 'Dancing Script')" style="font-family: 'Dancing Script', cursive;"><?= e('font_dancing') ?></button>
                            <button type="button" class="font-option" data-font="Great Vibes" onclick="selectFont('office', 'Great Vibes')" style="font-family: 'Great Vibes', cursive;"><?= e('font_vibes') ?></button>
                        </div>
                        
                        <img id="officeTypeImg" class="signature-image" alt="<?= e('signature_office_label') ?>">
                    </div>
                </div>
                
                <div class="sig-actions">
                    <button type="button" class="btn-sig btn-clear" onclick="clearSignature('office')"><?= e('signature_clear') ?></button>
                    <button type="button" class="btn-sig btn-save" onclick="saveSignature('office')"><?= e('signature_confirm') ?></button>
                </div>
            <?php endif; ?>
            
            <strong style="margin-top: 8px;"><?= e($office['office_name'] ?? 'الإدارة') ?></strong>
        </div>

    </div>
    
    <!-- Footer -->
    <div class="print-footer">
        <p><?= e($office['office_name'] ?? 'نظام إدارة تأجير السيارات') ?> — <?= e('all_rights_reserved') ?> © <?= date('Y') ?></p>
        <p><?= e('contract_no') ?>: <?= e($rental['contract_no']) ?> — <?= e('print_time') ?>: <?= e(date('Y-m-d h:i A')) ?></p>
    </div>
    
</div>

<script>
// ============================================================
// الترجمات
// ============================================================
window.I18N = {
    signature_type_empty:       '<?= e('signature_type_empty') ?>',
    signature_draw_empty:       '<?= e('signature_draw_empty') ?>',
    signature_draw_success:     '<?= e('signature_draw_success') ?>',
    signature_type_success:     '<?= e('signature_type_success') ?>',
    signature_cleared:          '<?= e('signature_cleared') ?>',
    pdf_generating:             '<?= e('pdf_generating') ?>',
    pdf_success:                '<?= e('pdf_success') ?>',
    pdf_error:                  '<?= e('pdf_error') ?>',
    signature_default_customer: '<?= e('signature_default_customer') ?>',
    signature_default_office:   '<?= e('signature_default_office') ?>'
};

// ============================================================
// إعدادات النظام
// ============================================================
window.CONTRACT_CONFIG = {
    contractNo: '<?= e($rental['contract_no'] ?? '') ?>',
    rentalId: <?= (int)($rental['id'] ?? 0) ?>
};

const sigMode = { customer: 'draw', office: 'draw' };
const sigFont = { customer: 'Amiri', office: 'Amiri' };

// ============================================================
// 📑 تبديل التبويب
// ============================================================
function switchSigTab(type, mode) {
    sigMode[type] = mode;
    
    const panels = {
        draw: document.getElementById(type + '-draw-panel'),
        type: document.getElementById(type + '-type-panel')
    };
    
    Object.values(panels).forEach(p => p && p.classList.remove('active'));
    if (panels[mode]) panels[mode].classList.add('active');
    
    const sigBox = panels.draw?.closest('.signature-box') || panels.type?.closest('.signature-box');
    if (sigBox) {
        sigBox.querySelectorAll('.sig-tab').forEach(tab => {
            const txt = tab.textContent.trim();
            const drawTxt = '<?= e('signature_draw') ?>';
            const typeTxt = '<?= e('signature_type') ?>';
            tab.classList.toggle('active', 
                (mode === 'draw' && txt.includes(drawTxt)) || 
                (mode === 'type' && txt.includes(typeTxt))
            );
        });
    }
    
    if (mode === 'type') updateTypePreview(type);
}

// ============================================================
// ⌨️ تحديث معاينة الكتابة
// ============================================================
function updateTypePreview(type) {
    const input = document.getElementById(type + 'TypeInput');
    const preview = document.getElementById(type + 'TypePreview');
    if (input && preview) {
        const defaultText = type === 'customer' 
            ? window.I18N.signature_default_customer 
            : window.I18N.signature_default_office;
        preview.textContent = input.value || defaultText;
        preview.style.fontFamily = `'${sigFont[type]}', serif`;
    }
}

// ============================================================
// 🎨 اختيار الخط
// ============================================================
function selectFont(type, fontName) {
    sigFont[type] = fontName;
    
    const selector = document.getElementById(type + 'FontSelector');
    if (selector) {
        selector.querySelectorAll('.font-option').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.font === fontName);
        });
    }
    
    const preview = document.getElementById(type + 'TypePreview');
    if (preview) {
        preview.style.fontFamily = `'${fontName}', serif`;
    }
}

// ============================================================
// ✍️ إعداد Canvas للرسم
// ============================================================
function setupCanvas(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    
    const ctx = canvas.getContext('2d');
    let isDrawing = false;
    
    function resizeCanvas() {
        const rect = canvas.parentElement.getBoundingClientRect();
        canvas.width = rect.width;
        canvas.height = rect.height;
    }
    resizeCanvas();
    window.addEventListener('resize', resizeCanvas);

    ctx.strokeStyle = '#0f172a';
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';

    function startDrawing(e) {
        isDrawing = true;
        draw(e);
    }

    function stopDrawing() {
        isDrawing = false;
        ctx.beginPath();
    }

    function draw(e) {
        if (!isDrawing) return;
        e.preventDefault();

        const rect = canvas.getBoundingClientRect();
        const clientX = e.touches ? e.touches[0].clientX : e.clientX;
        const clientY = e.touches ? e.touches[0].clientY : e.clientY;
        
        const x = clientX - rect.left;
        const y = clientY - rect.top;

        ctx.lineTo(x, y);
        ctx.stroke();
        ctx.beginPath();
        ctx.moveTo(x, y);
    }

    canvas.addEventListener('mousedown', startDrawing);
    canvas.addEventListener('mousemove', draw);
    canvas.addEventListener('mouseup', stopDrawing);
    canvas.addEventListener('mouseout', stopDrawing);
    canvas.addEventListener('touchstart', startDrawing, { passive: false });
    canvas.addEventListener('touchmove', draw, { passive: false });
    canvas.addEventListener('touchend', stopDrawing);
}

// ============================================================
// 🗑️ مسح التوقيع
// ============================================================
function clearSignature(type) {
    const canvas = document.getElementById(type + 'Signature');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        canvas.style.display = 'block';
    }
    
    const img = document.getElementById(type + 'SignatureImg');
    if (img) {
        img.style.display = 'none';
        img.src = '';
    }
    
    const typeImg = document.getElementById(type + 'TypeImg');
    if (typeImg) {
        typeImg.style.display = 'none';
        typeImg.src = '';
    }
    
    const input = document.getElementById(type + 'TypeInput');
    const preview = document.getElementById(type + 'TypePreview');
    const fontSelector = document.getElementById(type + 'FontSelector');
    
    if (input) {
        input.style.display = 'block';
        if (type === 'office') input.value = '';
    }
    if (preview) {
        preview.style.display = 'flex';
        preview.style.visibility = 'visible';
    }
    if (fontSelector) fontSelector.style.display = 'flex';
    
    const container = document.querySelector(`#${type}-type-panel .type-container`);
    if (container) container.classList.remove('signed');
    
    updateTypePreview(type);
    
    showToast('🗑️ ' + window.I18N.signature_cleared, 'info');
}

// ============================================================
// 💾 اعتماد وحفظ التوقيع
// ============================================================
function saveSignature(type) {
    const mode = sigMode[type];
    
    if (mode === 'draw') {
        const canvas = document.getElementById(type + 'Signature');
        const img = document.getElementById(type + 'SignatureImg');
        if (!canvas) return;
        
        const blank = document.createElement('canvas');
        blank.width = canvas.width;
        blank.height = canvas.height;
        if (canvas.toDataURL() === blank.toDataURL()) {
            showToast('⚠️ ' + window.I18N.signature_draw_empty, 'warning');
            return;
        }
        
        img.src = canvas.toDataURL('image/png');
        canvas.style.display = 'none';
        img.style.display = 'block';
        
        showToast('✅ ' + window.I18N.signature_draw_success, 'success');
        
    } else {
        const input = document.getElementById(type + 'TypeInput');
        const typeImg = document.getElementById(type + 'TypeImg');
        const fontSelector = document.getElementById(type + 'FontSelector');
        const preview = document.getElementById(type + 'TypePreview');
        
        if (!input || !input.value.trim()) {
            showToast('⚠️ ' + window.I18N.signature_type_empty, 'warning');
            return;
        }
        
        const text = input.value.trim();
        const font = sigFont[type];
        
        const canvas = document.createElement('canvas');
        canvas.width = 800;
        canvas.height = 300;
        
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        ctx.fillStyle = '#0f172a';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.direction = 'rtl';
        
        const fontSpec = `600 90px '${font}', 'Cairo', serif`;
        
        const drawAndFinish = () => {
            ctx.font = fontSpec;
            ctx.fillText(text, canvas.width / 2, canvas.height / 2);
            
            const dataUrl = canvas.toDataURL('image/png');
            
            typeImg.src = dataUrl;
            typeImg.style.display = 'block';
            
            input.style.display = 'none';
            preview.style.display = 'none';
            if (fontSelector) fontSelector.style.display = 'none';
            
            const container = input.closest('.type-container');
            if (container) container.classList.add('signed');
            
            showToast('✅ ' + window.I18N.signature_type_success, 'success');
        };
        
        if (document.fonts && document.fonts.load) {
            document.fonts.load(fontSpec, text)
                .then(drawAndFinish)
                .catch(drawAndFinish);
        } else {
            drawAndFinish();
        }
    }
}

// ============================================================
// 📄 توليد PDF يملأ A4 بالكامل
// ============================================================
async function downloadPDF() {
    const btn = document.getElementById('btnPdf');
    const originalHTML = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-inline"></span> ' + window.I18N.pdf_generating;
    
    document.body.classList.add('pdf-mode');
    
    const element = document.getElementById('printContainer');
    const originalCSS = element.style.cssText;
    
    try {
        // ✅ 1. انتظر تحميل الخطوط
        if (document.fonts && document.fonts.ready) {
            await document.fonts.ready;
        }
        
        // ✅ 2. انتظر قصير للسماح للـ CSS بالتطبيق
        await new Promise(resolve => setTimeout(resolve, 300));
        
        // ✅ 3. قياس المحتوى
        const contentWidth = element.scrollWidth;
        const contentHeight = element.scrollHeight;
        
        // ✅ 4. تحويل المحتوى إلى صورة عالية الدقة
        const canvas = await html2canvas(element, {
            scale: 2.5,
            useCORS: true,
            backgroundColor: '#ffffff',
            logging: false,
            windowWidth: contentWidth,
            windowHeight: contentHeight,
            width: contentWidth,
            height: contentHeight
        });
        
        const imgData = canvas.toDataURL('image/jpeg', 0.95);
        
        // ✅ 5. أبعاد A4 بالـ mm
        const A4_WIDTH = 210;
        const A4_HEIGHT = 297;
        
        // ✅ 6. حساب نسبة الأبعاد
        const contentRatio = contentWidth / contentHeight;
        const a4Ratio = A4_WIDTH / A4_HEIGHT;
        
        let imgWidth, imgHeight, xOffset, yOffset;
        
        if (contentRatio > a4Ratio) {
            // المحتوى أوسع من A4 → العرض هو المرجع
            imgWidth = A4_WIDTH;
            imgHeight = A4_WIDTH / contentRatio;
            xOffset = 0;
            yOffset = (A4_HEIGHT - imgHeight) / 2;
        } else {
            // المحتوى أطول من A4 → الارتفاع هو المرجع
            imgHeight = A4_HEIGHT;
            imgWidth = A4_HEIGHT * contentRatio;
            xOffset = (A4_WIDTH - imgWidth) / 2;
            yOffset = 0;
        }
        
        // ✅ 7. إنشاء PDF بصفحة واحدة
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait',
            compress: true
        });
        
        pdf.addImage(
            imgData,
            'JPEG',
            xOffset,
            yOffset,
            imgWidth,
            imgHeight,
            undefined,
            'FAST'
        );
        
        // ✅ 8. حفظ الملف
        const fileName = 'contract-' + window.CONTRACT_CONFIG.contractNo + '.pdf';
        pdf.save(fileName);
        
        // استعادة الحالة
        element.style.cssText = originalCSS;
        document.body.classList.remove('pdf-mode');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        
        showToast('✅ ' + window.I18N.pdf_success, 'success');
        
    } catch (err) {
        console.error('PDF Error:', err);
        
        element.style.cssText = originalCSS;
        document.body.classList.remove('pdf-mode');
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        
        showToast('❌ ' + window.I18N.pdf_error, 'error');
    }
}

// ============================================================
// 🍞 إشعار Toast
// ============================================================
function showToast(message, type = 'info') {
    const colors = {
        success: '#16a34a',
        error: '#dc2626',
        warning: '#d97706',
        info: '#2563eb'
    };
    
    const toast = document.createElement('div');
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: ${colors[type] || colors.info};
        color: white;
        padding: 14px 28px;
        border-radius: 10px;
        font-family: 'Cairo', sans-serif;
        font-size: 0.95rem;
        font-weight: 600;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        z-index: 10000;
        animation: slideUp 0.3s ease-out;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(20px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

const styleAnim = document.createElement('style');
styleAnim.textContent = `
    @keyframes slideUp {
        from { opacity: 0; transform: translateX(-50%) translateY(20px); }
        to { opacity: 1; transform: translateX(-50%) translateY(0); }
    }
`;
document.head.appendChild(styleAnim);

// ============================================================
// ⌨️ اختصار Ctrl+S
// ============================================================
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        downloadPDF();
    }
});

// ============================================================
// 🚀 التشغيل
// ============================================================
window.addEventListener('DOMContentLoaded', function() {
    setupCanvas('customerSignature');
    setupCanvas('officeSignature');
    console.log('✅ صفحة العقد جاهزة — PDF يملأ A4');
});
</script>
</body>
</html>