<?php
/**
 * ============================================================
 * سكريبت التنبيهات اليومية
 * ============================================================
 * 
 * Cron Job:
 *   0 6 * * * cd /path/to/project && php cron/daily_notifications.php
 * 
 * يدوياً:
 *   php cron/daily_notifications.php
 *   أو من المتصفح: http://localhost/car_system/car-rental/cron/daily_notifications.php
 * 
 * المهام:
 *   1. توليد التنبيهات تلقائياً (دفعات، عقود، وثائق، صيانة)
 *   2. توليد رسائل واتساب للدفعات المستحقة اليوم
 *   3. حذف التنبيهات القديمة
 *   4. حفظ تقرير يومي
 * ============================================================
 */

// منع التشغيل من المتصفح بدون مفتاح (اختياري)
$isCli = php_sapi_name() === 'cli';
$isWeb = !$isCli;

if ($isWeb) {
    // للتشغيل من المتصفح، يتطلب ?key=CRON_SECRET
    $secret = $_GET['key'] ?? '';
    if ($secret !== 'cron_2026_secret_key') {
        // للتطوير: اسمح بالتشغيل المباشر
        // في الإنتاج: علّق هذا السطر
        // die('❌ غير مصرح');
    }
    
    header('Content-Type: text/html; charset=utf-8');
    echo '<pre style="font-family: Consolas, monospace; background: #1a1d21; color: #e8eaed; padding: 20px; line-height: 1.6;">';
}

// تحميل البنية
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');

require BASE_PATH . '/app/config/config.php';
require BASE_PATH . '/app/core/Database.php';
require BASE_PATH . '/app/core/Notification.php';
require BASE_PATH . '/app/models/Rental.php';

// ============================================================
// بدء التنفيذ
// ============================================================
$startTime = microtime(true);

echo "═══════════════════════════════════════════════════════\n";
echo "  🔔 نظام التنبيهات اليومية\n";
echo "  📅 " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════\n\n";

try {
    $notification = new Notification();
    $rentalModel = new Rental();
    
    // ============================================================
    // 1. توليد جميع التنبيهات
    // ============================================================
    echo "📌 [1/5] توليد التنبيهات...\n";
    echo "───────────────────────────────────────────────────────\n";
    
    $results = $notification->generateAll();
    
    $total = 0;
    $typeLabels = [
        'payment_due'     => '💰 دفعات مستحقة قريباً',
        'payment_overdue' => '⚠️  دفعات متأخرة',
        'rental_ending'   => '⏰ عقود تنتهي قريباً',
        'rental_overdue'  => '🚨 عقود متأخرة',
        'doc_expiry'      => '📋 وثائق تنتهي قريباً',
        'service_due'     => '🔧 صيانة مستحقة',
    ];
    
    foreach ($results as $type => $count) {
        $label = $typeLabels[$type] ?? $type;
        $icon = $count > 0 ? '✅' : '⚪';
        printf("  %s %-40s %d\n", $icon, $label, $count);
        $total += $count;
    }
    
    echo "\n  📊 إجمالي التنبيهات الجديدة: {$total}\n\n";
    
    // ============================================================
    // 2. توليد رسائل واتساب للدفعات المستحقة اليوم
    // ============================================================
    echo "📌 [2/5] توليد رسائل واتساب...\n";
    echo "───────────────────────────────────────────────────────\n";
    
    $whatsappMessages = $notification->generateWhatsAppReminders();
    
    if (empty($whatsappMessages)) {
        echo "  ⚪ لا توجد دفعات مستحقة اليوم\n";
    } else {
        echo "  📱 تم توليد " . count($whatsappMessages) . " رسالة:\n\n";
        
        foreach ($whatsappMessages as $index => $msg) {
            echo "  ┌─────────────────────────────────────────────────┐\n";
            echo "  │ 📱 رسالة #" . ($index + 1) . "\n";
            echo "  ├─────────────────────────────────────────────────┤\n";
            echo "  │ 👤 المستأجر:    " . $msg['customer_name'] . "\n";
            echo "  │ 📞 الجوال:      " . $msg['customer_phone'] . "\n";
            echo "  │ 📄 رقم العقد:    " . $msg['contract_no'] . "\n";
            echo "  │ 💰 المبلغ:       " . number_format($msg['amount'], 2) . " ر.س\n";
            echo "  │ 📅 الاستحقاق:   " . $msg['due_date'] . "\n";
            echo "  │\n";
            echo "  │ 📝 نص الرسالة:\n";
            
            $messageLines = explode("\n", $msg['message']);
            foreach ($messageLines as $line) {
                echo "  │    " . $line . "\n";
            }
            
            echo "  │\n";
            echo "  │ 🔗 رابط واتساب:\n";
            echo "  │    " . $msg['wa_link'] . "\n";
            echo "  └─────────────────────────────────────────────────┘\n\n";
        }
    }
    
    // ============================================================
    // 3. حفظ رسائل واتساب في ملف
    // ============================================================
    echo "📌 [3/5] حفظ رسائل واتساب...\n";
    echo "───────────────────────────────────────────────────────\n";
    
    if (!empty($whatsappMessages)) {
        $whatsappDir = STORAGE_PATH . '/whatsapp';
        if (!is_dir($whatsappDir)) {
            @mkdir($whatsappDir, 0755, true);
        }
        
        $filename = 'whatsapp_' . date('Y-m-d_H-i-s') . '.txt';
        $filepath = $whatsappDir . '/' . $filename;
        
        $content = "═══════════════════════════════════════════════════════\n";
        $content .= "  📱 رسائل واتساب - " . date('Y-m-d H:i:s') . "\n";
        $content .= "═══════════════════════════════════════════════════════\n\n";
        
        foreach ($whatsappMessages as $index => $msg) {
            $content .= "رسالة #" . ($index + 1) . "\n";
            $content .= "───────────────────────────────────────────────────────\n";
            $content .= "المستأجر:  " . $msg['customer_name'] . "\n";
            $content .= "الجوال:    " . $msg['customer_phone'] . "\n";
            $content .= "العقد:     " . $msg['contract_no'] . "\n";
            $content .= "المبلغ:    " . number_format($msg['amount'], 2) . " ر.س\n";
            $content .= "الاستحقاق: " . $msg['due_date'] . "\n";
            $content .= "\nالنص:\n" . $msg['message'] . "\n";
            $content .= "\nالرابط:\n" . $msg['wa_link'] . "\n";
            $content .= "\n\n";
        }
        
        file_put_contents($filepath, $content);
        echo "  ✅ تم الحفظ في: storage/whatsapp/{$filename}\n";
    } else {
        echo "  ⚪ لا توجد رسائل للحفظ\n";
    }
    echo "\n";
    
    // ============================================================
    // 4. حذف التنبيهات القديمة
    // ============================================================
    echo "📌 [4/5] حذف التنبيهات القديمة...\n";
    echo "───────────────────────────────────────────────────────\n";
    
    $deleted = $notification->cleanOld(90);
    echo "  🧹 تم حذف {$deleted} تنبيه قديم (أكثر من 90 يوم)\n\n";
    
    // ============================================================
    // 5. الإحصائيات النهائية
    // ============================================================
    echo "📌 [5/5] الإحصائيات النهائية...\n";
    echo "───────────────────────────────────────────────────────\n";
    
    $unread = $notification->countUnread();
    echo "  📬 التنبيهات غير المقروءة: {$unread}\n";
    
    // إحصائيات إضافية من قاعدة البيانات
    $db = Database::getInstance();
    
    $stats = [
        'overdue_amount' => (float) ($db->fetchOne(
            "SELECT COALESCE(SUM(ps.amount - ps.paid_amount), 0) as total
             FROM payment_schedules ps
             JOIN rentals r ON r.id = ps.rental_id
             WHERE ps.status IN ('pending', 'partial', 'overdue')
             AND r.status = 'active'
             AND ps.due_date < CURDATE()"
        )['total'] ?? 0),
        
        'overdue_count' => (int) ($db->fetchOne(
            "SELECT COUNT(*) as c FROM payment_schedules ps
             JOIN rentals r ON r.id = ps.rental_id
             WHERE ps.status IN ('pending', 'partial', 'overdue')
             AND r.status = 'active'
             AND ps.due_date < CURDATE()"
        )['c'] ?? 0),
        
        'active_rentals' => (int) ($db->fetchOne(
            "SELECT COUNT(*) as c FROM rentals WHERE status = 'active'"
        )['c'] ?? 0),
        
        'ending_today' => (int) ($db->fetchOne(
            "SELECT COUNT(*) as c FROM rentals 
             WHERE status = 'active' 
             AND DATE(return_due_datetime) = CURDATE()"
        )['c'] ?? 0),
        
        'docs_expiring' => (int) ($db->fetchOne(
            "SELECT COUNT(*) as c FROM cars 
             WHERE status NOT IN ('out_of_service')
             AND (
                 (registration_expiry IS NOT NULL AND registration_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
                 OR (insurance_expiry IS NOT NULL AND insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
                 OR (inspection_expiry IS NOT NULL AND inspection_expiry <= DATE_ADD(CURDATE(), INTERVAL 30 DAY))
             )"
        )['c'] ?? 0),
    ];
    
    echo "\n  📊 ملخص النظام:\n";
    printf("     • العقود النشطة:          %d\n", $stats['active_rentals']);
    printf("     • تنتهي اليوم:            %d\n", $stats['ending_today']);
    printf("     • دفعات متأخرة:           %d\n", $stats['overdue_count']);
    printf("     • إجمالي المتأخرات:       %s ر.س\n", number_format($stats['overdue_amount'], 2));
    printf("     • وثائق تنتهي خلال 30 يوم: %d\n", $stats['docs_expiring']);
    
    // ============================================================
    // تقرير HTML للعرض في المتصفح
    // ============================================================
    if ($isWeb && !empty($whatsappMessages)) {
        echo "\n\n";
        echo "═══════════════════════════════════════════════════════\n";
        echo "  🚀 روابط واتساب السريعة\n";
        echo "═══════════════════════════════════════════════════════\n\n";
        
        foreach ($whatsappMessages as $index => $msg) {
            echo '<div style="background: #2d2d2d; padding: 15px; margin: 10px 0; border-radius: 8px; border-right: 4px solid #25D366;">';
            echo '<strong style="color: #25D366; font-size: 1.1rem;">#' . ($index + 1) . ' — ' . htmlspecialchars($msg['customer_name']) . '</strong><br>';
            echo '<span style="color: #60a5fa;">' . htmlspecialchars($msg['customer_phone']) . '</span> — ';
            echo '<span style="color: #fbbf24;">' . number_format($msg['amount'], 2) . ' ر.س</span><br>';
            echo '<a href="' . htmlspecialchars($msg['wa_link']) . '" target="_blank" style="display: inline-block; margin-top: 10px; padding: 8px 20px; background: #25D366; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">📱 إرسال واتساب</a>';
            echo '</div>';
        }
    }
    
    // ============================================================
    // النهاية
    // ============================================================
    $executionTime = round(microtime(true) - $startTime, 3);
    
    echo "\n\n";
    echo "═══════════════════════════════════════════════════════\n";
    echo "  ✅ انتهى بنجاح\n";
    echo "  ⏱️  الوقت: {$executionTime} ثانية\n";
    echo "  📅 " . date('Y-m-d H:i:s') . "\n";
    echo "═══════════════════════════════════════════════════════\n";
    
    if ($isWeb) {
        echo '</pre>';
    }
    
} catch (Throwable $e) {
    echo "\n\n";
    echo "═══════════════════════════════════════════════════════\n";
    echo "  ❌ خطأ\n";
    echo "═══════════════════════════════════════════════════════\n";
    echo "  الرسالة: " . $e->getMessage() . "\n";
    echo "  الملف:   " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "═══════════════════════════════════════════════════════\n";
    
    error_log('Cron notifications error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    
    if ($isWeb) {
        echo '</pre>';
    }
    
    exit(1);
}