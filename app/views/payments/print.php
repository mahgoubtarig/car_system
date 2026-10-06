<?php
/**
 * شاشة طباعة السند المالي
 * - دعم PDF يملأ A4 بالكامل
 * - 3 أزرار: طباعة، PDF، إغلاق
 * - جميع النصوص مترجمة
 */
$isArabic = Lang::isArabic();

$typeText = $payment['payment_type'] === 'deposit_refund' 
    ? ($isArabic ? 'سند صرف (رد تأمين)' : 'Payment Voucher (Deposit Refund)')
    : ($isArabic ? 'سند قبض (إيجار/مخالفات)' : 'Receipt Voucher (Rent/Penalties)');

$methodText = match ($payment['method']) {
    'cash' => $isArabic ? 'نقدي 💵' : 'Cash 💵',
    'network' => $isArabic ? 'شبكة 💳' : 'Network 💳',
    'bank_transfer' => $isArabic ? 'حوالة بنكية 🏦' : 'Bank Transfer 🏦',
    default => $payment['method']
};
?>
<!DOCTYPE html>
<html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($typeText) ?> - <?= e($payment['receipt_no']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Cairo', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        
        /* ============================================================
           أزرار التحكم (3 أزرار)
           ============================================================ */
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
        
        .btn-close { background: #ef4444; box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2); }
        .btn-close:hover { background: #dc2626; }
        
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
        
        /* ============================================================
           حاوية السند
           ============================================================ */
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border-top: 8px solid #2563eb;
        }
        
        /* الترويسة */
        .office-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #eee;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header-info-ar { text-align: right; width: 33%; }
        .header-center { text-align: center; width: 33%; }
        .header-info-en { text-align: left; width: 33%; }
        
        .office-header h1 { margin: 0 0 5px 0; color: #1e293b; font-size: 1.5rem; font-weight: 800; }
        .office-header p { margin: 2px 0; color: #64748b; font-size: 0.95rem; }
        
        /* عنوان السند */
        .receipt-title {
            text-align: center;
            margin-bottom: 30px;
            background: #eff6ff;
            padding: 15px;
            border-radius: 8px;
            border: 1px dashed #bfdbfe;
        }
        .receipt-title h2 { margin: 0 0 10px 0; color: #1d4ed8; font-size: 1.8rem; }
        .receipt-title p { margin: 5px 0; color: #475569; font-size: 1.1rem; }
        
        /* مبلغ السند */
        .amount-box {
            text-align: center;
            padding: 20px;
            border-bottom: 2px solid #eee;
            margin-bottom: 30px;
        }
        .amount-box h2 {
            font-size: 3.5rem;
            margin: 0;
            color: #0f172a;
        }
        .amount-box p {
            margin: 10px 0 0 0;
            font-size: 1.3rem;
            color: #475569;
            font-weight: 700;
        }
        
        /* تفاصيل العميل والسيارة */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-box {
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .info-box span {
            display: block;
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 5px;
        }
        .info-box strong {
            display: block;
            font-size: 1.1rem;
            color: #1e293b;
            margin-bottom: 15px;
        }
        .info-box strong:last-child { margin-bottom: 0; }
        
        /* التواقيع */
        .footer-signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }
        .signature-box { text-align: center; }
        .signature-line {
            border-bottom: 1px dashed #cbd5e1;
            width: 80%;
            margin: 50px auto 10px;
        }
        
        /* التذييل */
        .receipt-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 0.95rem;
            color: #64748b;
            display: flex;
            justify-content: center;
            gap: 25px;
            flex-wrap: wrap;
        }
        
        /* ============================================================
           وضع PDF
           ============================================================ */
        .pdf-mode { 
            background: white !important; 
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .pdf-mode .print-actions { display: none !important; }
        
        .pdf-mode .receipt-container {
            box-shadow: none !important;
            border-radius: 0 !important;
            border-top: none !important;
            padding: 15px !important;
            margin: 0 auto !important;
            max-width: 210mm !important;
            width: 210mm !important;
            min-height: 297mm !important;
            background: white !important;
        }
        
        /* ============================================================
           الطباعة
           ============================================================ */
        @media print {
            body { background: #fff; padding: 0; }
            .receipt-container { box-shadow: none; border-top: none; max-width: 100%; padding: 20px; }
            .print-actions { display: none !important; }
            .info-box { border-color: #ddd; }
        }
    </style>
</head>
<body>

<!-- أزرار التحكم (3 أزرار) -->
<div class="print-actions">
    <button class="btn-action btn-print" onclick="window.print()">
        🖨️ <?= e('print') ?>
    </button>
    
    <button class="btn-action btn-pdf" id="btnPdf" onclick="downloadPDF()">
        <span class="btn-pdf-icon">📄</span>
        <span class="btn-pdf-text"><?= e('download_pdf') ?? 'تحميل PDF' ?></span>
    </button>
    
    <button class="btn-action btn-close" onclick="window.close()">
        ✕ <?= e('close') ?>
    </button>
</div>

<div class="receipt-container" id="receiptContainer">
    
    <!-- الترويسة -->
    <div class="office-header">
        <div class="header-info-ar">
            <h1><?= e($office['name_ar'] ?? '') ?></h1>
            <?php if(!empty($office['tax_number'])): ?>
                <p><?= e('tax_number') ?>: <?= e($office['tax_number']) ?></p>
            <?php endif; ?>
        </div>
        
        <div class="header-center">
            <h1 style="color:#2563eb; margin:0; font-size: 3rem;">📝</h1>
        </div>
        
        <div class="header-info-en">
            <h1 style="direction:ltr;"><?= e($office['name_en'] ?? '') ?></h1>
            <?php if(!empty($office['tax_number'])): ?>
                <p style="direction:ltr;">VAT: <?= e($office['tax_number']) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- عنوان ورقم السند -->
    <div class="receipt-title">
        <h2><?= e($typeText) ?></h2>
        <p>
            <?= e('receipt_no') ?>: <strong style="color:#0f172a;"><?= e($payment['receipt_no']) ?></strong> 
            | 
            <?= e('date') ?>: <strong><bdi><?= date('Y-m-d h:i A', strtotime($payment['paid_at'])) ?></bdi></strong>
        </p>
    </div>

    <!-- مبلغ السند -->
    <div class="amount-box">
        <h2><?= number_format((float)$payment['amount'], 2) ?> <?= e('sar') ?></h2>
        <p><?= e('payment_method') ?>: <?= $methodText ?></p>
    </div>

    <!-- التفاصيل -->
    <div class="details-grid">
        <div class="info-box">
            <span><?= e('customer') ?>:</span>
            <strong><?= e($payment['customer_name'] ?? '') ?></strong>
            
            <span><?= e('id_number') ?>:</span>
            <strong><?= e($payment['customer_id_number'] ?? '---') ?></strong>
            
            <span><?= e('phone') ?>:</span>
            <strong><bdi><?= e($payment['customer_phone'] ?? '---') ?></bdi></strong>
        </div>

        <div class="info-box">
            <span><?= e('contract_details') ?>:</span>
            <strong><?= e('contract_no') ?>: <?= e($payment['contract_no'] ?? '') ?></strong>
            
            <span><?= e('car') ?>:</span>
            <strong><?= e($payment['plate_number'] ?? '') ?> - <?= e($payment['brand'] ?? '') ?> <?= e($payment['model'] ?? '') ?></strong>
            
            <?php if(!empty($payment['reference_no'])): ?>
                <span><?= e('reference_no') ?>:</span>
                <strong><?= e($payment['reference_no']) ?></strong>
            <?php endif; ?>
        </div>
    </div>

    <!-- الملاحظات -->
    <?php if(!empty($payment['notes'])): ?>
        <div class="info-box" style="margin-bottom: 30px;">
            <span><?= e('notes') ?>:</span>
            <strong><?= e($payment['notes']) ?></strong>
        </div>
    <?php endif; ?>

    <!-- التواقيع -->
    <div class="footer-signatures">
        <div class="signature-box">
            <p><?= e('signature_receiver') ?></p>
            <div class="signature-line"></div>
            <strong><?= e($payment['received_by_name'] ?? $office['name_ar'] ?? '') ?></strong>
        </div>
        <div class="signature-box">
            <p><?= e('signature_payer') ?></p>
            <div class="signature-line"></div>
            <strong><?= e($payment['customer_name'] ?? '') ?></strong>
        </div>
    </div>
    
    <!-- التذييل -->
    <div class="receipt-footer">
        <?php if(!empty($office['address'])): ?>
            <span>📍 <?= e($office['address']) ?></span>
        <?php endif; ?>
        
        <?php if(!empty($office['phone'])): ?>
            <span>📞 <bdi><?= e($office['phone']) ?></bdi></span>
        <?php endif; ?>
    </div>
    
</div>

<script>
// ============================================================
// الترجمات
// ============================================================
window.I18N = {
    pdf_generating:  '<?= e('pdf_generating') ?? 'جارٍ التوليد...' ?>',
    pdf_success:     '<?= e('pdf_success') ?? 'تم توليد PDF بنجاح' ?>',
    pdf_error:       '<?= e('pdf_error') ?? 'حدث خطأ أثناء توليد PDF' ?>'
};

window.RECEIPT_CONFIG = {
    receiptNo: '<?= e($payment['receipt_no'] ?? '') ?>'
};

// ============================================================
// 📄 توليد PDF يملأ A4 بالكامل
// ============================================================
async function downloadPDF() {
    const btn = document.getElementById('btnPdf');
    const originalHTML = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-inline"></span> ' + window.I18N.pdf_generating;
    
    document.body.classList.add('pdf-mode');
    
    const element = document.getElementById('receiptContainer');
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
        
        // ✅ 5. أبعاد A4
        const A4_WIDTH = 210;
        const A4_HEIGHT = 297;
        
        // ✅ 6. حساب نسبة الأبعاد
        const contentRatio = contentWidth / contentHeight;
        const a4Ratio = A4_WIDTH / A4_HEIGHT;
        
        let imgWidth, imgHeight, xOffset, yOffset;
        
        if (contentRatio > a4Ratio) {
            imgWidth = A4_WIDTH;
            imgHeight = A4_WIDTH / contentRatio;
            xOffset = 0;
            yOffset = (A4_HEIGHT - imgHeight) / 2;
        } else {
            imgHeight = A4_HEIGHT;
            imgWidth = A4_HEIGHT * contentRatio;
            xOffset = (A4_WIDTH - imgWidth) / 2;
            yOffset = 0;
        }
        
        // ✅ 7. إنشاء PDF
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
        const fileName = 'Receipt-' + window.RECEIPT_CONFIG.receiptNo + '.pdf';
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

console.log('✅ صفحة السند جاهزة');
</script>
</body>
</html>