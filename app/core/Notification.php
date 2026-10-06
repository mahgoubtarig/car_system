<?php
/**
 * كلاس إدارة التنبيهات
 */

class Notification
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    // ============================================================
    // ✅ توليد التنبيهات
    // ============================================================
    
    /**
     * توليد جميع التنبيهات
     */
    public function generateAll(): array
    {
        return [
            'payment_due'     => $this->generatePaymentDue(),
            'payment_overdue' => $this->generatePaymentOverdue(),
            'rental_ending'   => $this->generateRentalEnding(),
            'rental_overdue'  => $this->generateRentalOverdue(),
            'doc_expiry'      => $this->generateDocExpiry(),
            'service_due'     => $this->generateServiceDue(),
        ];
    }
    
    /**
     * تنبيهات الدفعات المستحقة قريباً
     */
    public function generatePaymentDue(): int
    {
        $config = require BASE_PATH . '/app/config/config.php';
        $daysBefore = (int) ($config['notification_days_before_payment'] ?? 3);
        
        $schedules = $this->db->fetchAll(
            "SELECT ps.id, ps.due_date
             FROM payment_schedules ps
             JOIN rentals r ON r.id = ps.rental_id
             WHERE ps.status IN ('pending', 'partial')
             AND r.status = 'active'
             AND ps.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)",
            ['days' => $daysBefore]
        );
        
        $count = 0;
        foreach ($schedules as $s) {
            if ($this->insertNotification('payment_due', 'payment_schedules', (int)$s['id'], $s['due_date'])) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * تنبيهات الدفعات المتأخرة
     */
    public function generatePaymentOverdue(): int
    {
        $schedules = $this->db->fetchAll(
            "SELECT ps.id, ps.due_date
             FROM payment_schedules ps
             JOIN rentals r ON r.id = ps.rental_id
             WHERE ps.status IN ('pending', 'partial', 'overdue')
             AND r.status = 'active'
             AND ps.due_date < CURDATE()"
        );
        
        $count = 0;
        foreach ($schedules as $s) {
            $this->db->execute(
                "UPDATE payment_schedules SET status = 'overdue' WHERE id = :id",
                ['id' => $s['id']]
            );
            
            if ($this->insertNotification('payment_overdue', 'payment_schedules', (int)$s['id'], $s['due_date'])) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * تنبيهات العقود التي تنتهي قريباً
     */
    public function generateRentalEnding(): int
    {
        $config = require BASE_PATH . '/app/config/config.php';
        $daysBefore = (int) ($config['notification_days_before_rental_end'] ?? 1);
        
        $rentals = $this->db->fetchAll(
            "SELECT id, return_due_datetime
             FROM rentals
             WHERE status = 'active'
             AND DATE(return_due_datetime) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)",
            ['days' => $daysBefore]
        );
        
        $count = 0;
        foreach ($rentals as $r) {
            if ($this->insertNotification('rental_ending', 'rentals', (int)$r['id'], date('Y-m-d', strtotime($r['return_due_datetime'])))) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * تنبيهات العقود المتأخرة
     */
    public function generateRentalOverdue(): int
    {
        $rentals = $this->db->fetchAll(
            "SELECT id, return_due_datetime
             FROM rentals
             WHERE status = 'active'
             AND return_due_datetime < NOW()"
        );
        
        $count = 0;
        foreach ($rentals as $r) {
            if ($this->insertNotification('rental_overdue', 'rentals', (int)$r['id'], date('Y-m-d', strtotime($r['return_due_datetime'])))) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * تنبيهات انتهاء الوثائق
     */
    public function generateDocExpiry(): int
    {
        $config = require BASE_PATH . '/app/config/config.php';
        $daysBefore = (int) ($config['notification_days_before_doc_expiry'] ?? 30);
        
        $cars = $this->db->fetchAll(
            "SELECT id, registration_expiry, insurance_expiry, inspection_expiry
             FROM cars
             WHERE status NOT IN ('out_of_service')
             AND (
                 (registration_expiry IS NOT NULL AND registration_expiry <= DATE_ADD(CURDATE(), INTERVAL :days DAY))
                 OR (insurance_expiry IS NOT NULL AND insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL :days DAY))
                 OR (inspection_expiry IS NOT NULL AND inspection_expiry <= DATE_ADD(CURDATE(), INTERVAL :days DAY))
             )",
            ['days' => $daysBefore]
        );
        
        $count = 0;
        foreach ($cars as $c) {
            foreach (['registration_expiry', 'insurance_expiry', 'inspection_expiry'] as $field) {
                if (!empty($c[$field]) && $c[$field] <= date('Y-m-d', strtotime("+{$daysBefore} days"))) {
                    if ($this->insertNotification('doc_expiry', 'cars', (int)$c['id'], $c[$field])) {
                        $count++;
                    }
                }
            }
        }
        return $count;
    }
    
    /**
     * ✅ تنبيهات الصيانة المستحقة
     */
    public function generateServiceDue(): int
    {
        // صيانة حسب الكيلومتر أو التاريخ
        $expenses = $this->db->fetchAll(
            "SELECT e.car_id, e.next_service_date, e.next_service_km,
                    c.plate_number, c.odometer
             FROM car_expenses e
             JOIN cars c ON c.id = e.car_id
             WHERE (e.next_service_date IS NOT NULL 
                    AND e.next_service_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))
                OR (e.next_service_km IS NOT NULL 
                    AND c.odometer >= e.next_service_km - 500)"
        );
        
        $count = 0;
        foreach ($expenses as $e) {
            $dueDate = $e['next_service_date'] ?? date('Y-m-d');
            
            if ($this->insertNotification('service_due', 'cars', (int)$e['car_id'], $dueDate)) {
                $count++;
            }
        }
        return $count;
    }
    
    /**
     * إدراج تنبيه (مع منع التكرار)
     */
    private function insertNotification(string $type, string $refTable, int $refId, string $dueDate): bool
    {
        try {
            $stmt = $this->db->query(
                "INSERT IGNORE INTO notifications 
                    (type, ref_table, ref_id, due_date, is_read)
                 VALUES 
                    (:type, :ref_table, :ref_id, :due_date, 0)",
                [
                    'type'      => $type,
                    'ref_table' => $refTable,
                    'ref_id'    => $refId,
                    'due_date'  => $dueDate,
                ]
            );
            
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            error_log('Insert notification error: ' . $e->getMessage());
            return false;
        }
    }
    
    // ============================================================
    // ✅ جلب التنبيهات
    // ============================================================
    
    /**
     * جلب التنبيهات غير المقروءة
     */
    public function getUnread(int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM notifications 
             WHERE is_read = 0
             ORDER BY due_date ASC
             LIMIT {$limit}"
        );
    }
    
    /**
     * جلب التنبيهات مع التفاصيل
     */
    public function getWithDetails(int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT n.*,
                    CASE 
                        WHEN n.ref_table = 'payment_schedules' THEN 
                            (SELECT CONCAT(r.contract_no, ' — ', cu.full_name, ' — ', c.plate_number)
                             FROM payment_schedules ps
                             JOIN rentals r ON r.id = ps.rental_id
                             JOIN customers cu ON cu.id = r.customer_id
                             JOIN cars c ON c.id = r.car_id
                             WHERE ps.id = n.ref_id)
                        WHEN n.ref_table = 'rentals' THEN
                            (SELECT CONCAT(r.contract_no, ' — ', cu.full_name)
                             FROM rentals r
                             JOIN customers cu ON cu.id = r.customer_id
                             WHERE r.id = n.ref_id)
                        WHEN n.ref_table = 'cars' THEN
                            (SELECT CONCAT(c.plate_number, ' — ', c.brand, ' ', c.model)
                             FROM cars c
                             WHERE c.id = n.ref_id)
                        ELSE NULL
                    END AS details
             FROM notifications n
             WHERE n.is_read = 0
             ORDER BY n.due_date ASC
             LIMIT {$limit}"
        );
    }
    
    /**
     * عدد التنبيهات غير المقروءة
     */
    public function countUnread(): int
    {
        return (int) ($this->db->fetchOne(
            "SELECT COUNT(*) as c FROM notifications WHERE is_read = 0"
        )['c'] ?? 0);
    }
    
    /**
     * إحصائيات التنبيهات
     */
    public function getStats(): array
    {
        $stats = $this->db->fetchAll(
            "SELECT type, COUNT(*) as count 
             FROM notifications 
             WHERE is_read = 0
             GROUP BY type"
        );
        
        $result = [
            'total'           => 0,
            'payment_due'     => 0,
            'payment_overdue' => 0,
            'rental_ending'   => 0,
            'rental_overdue'  => 0,
            'doc_expiry'      => 0,
            'service_due'     => 0,
        ];
        
        foreach ($stats as $s) {
            $result[$s['type']] = (int) $s['count'];
            $result['total'] += (int) $s['count'];
        }
        
        return $result;
    }
    
    // ============================================================
    // ✅ تحديث التنبيهات
    // ============================================================
    
    /**
     * وضع علامة مقروء
     */
    public function markAsRead(int $id): void
    {
        $this->db->execute(
            "UPDATE notifications SET is_read = 1, processed_at = NOW() WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * وضع علامة مقروء للكل
     */
    public function markAllAsRead(): void
    {
        $this->db->execute(
            "UPDATE notifications SET is_read = 1, processed_at = NOW() WHERE is_read = 0"
        );
    }
    
    /**
     * حذف التنبيهات القديمة
     */
    public function cleanOld(int $daysOld = 90): int
    {
        return $this->db->execute(
            "DELETE FROM notifications 
             WHERE is_read = 1 
             AND created_at < DATE_SUB(NOW(), INTERVAL :days DAY)",
            ['days' => $daysOld]
        );
    }
    
    /**
     * ✅ حذف تنبيهات جدول دفعة معين
     */
    public function clearScheduleNotifications(int $scheduleId): int
    {
        return $this->db->execute(
            "DELETE FROM notifications 
             WHERE ref_table = 'payment_schedules' 
             AND ref_id = :schedule_id",
            ['schedule_id' => $scheduleId]
        );
    }
    
    /**
     * ✅ حذف تنبيهات عقد معين
     */
    public function clearRentalNotifications(int $rentalId): int
    {
        $deleted = 0;
        
        // حذف تنبيهات الجدولات
        $deleted += $this->db->execute(
            "DELETE n FROM notifications n
             JOIN payment_schedules ps ON ps.id = n.ref_id
             WHERE n.ref_table = 'payment_schedules'
             AND ps.rental_id = :rental_id",
            ['rental_id' => $rentalId]
        );
        
        // حذف تنبيهات العقد
        $deleted += $this->db->execute(
            "DELETE FROM notifications 
             WHERE ref_table = 'rentals' 
             AND ref_id = :rental_id",
            ['rental_id' => $rentalId]
        );
        
        return $deleted;
    }
    
    // ============================================================
    // ✅ واتساب
    // ============================================================
    
    /**
     * ✅ تنظيف رقم الواتساب وإضافة كود الدولة
     */
    public function formatWhatsAppPhone(string $phone): string
    {
        // إزالة كل ما ليس رقماً
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // إضافة كود السعودية
        if (strlen($phone) === 10 && strpos($phone, '05') === 0) {
            // 05xxxxxxxx → 9665xxxxxxxx
            $phone = '966' . substr($phone, 1);
        } elseif (strlen($phone) === 9 && strpos($phone, '5') === 0) {
            // 5xxxxxxxx → 9665xxxxxxxx
            $phone = '966' . $phone;
        } elseif (strlen($phone) === 12 && strpos($phone, '966') === 0) {
            // 9665xxxxxxxx → كما هو
            // لا تغيير
        }
        
        return $phone;
    }
    
    /**
     * ✅ توليد رابط واتساب
     */
    public function getWhatsAppLink(string $phone, string $message): string
    {
        $phone = $this->formatWhatsAppPhone($phone);
        
        if (empty($phone)) {
            return '';
        }
        
        return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
    }
    
    /**
     * ✅ توليد رسائل واتساب للدفعات المستحقة اليوم
     */
    public function generateWhatsAppReminders(): array
    {
        $dueRentals = $this->getDueTodayRentals();
        
        $messages = [];
        
        foreach ($dueRentals as $r) {
            $phone = $r['customer_phone'] ?? '';
            if (empty($phone)) continue;
            
            $formattedPhone = $this->formatWhatsAppPhone($phone);
            if (empty($formattedPhone)) continue;
            
            $message = "🔔 تذكير بموعد الدفعة\n\n" .
                       "المستأجر: {$r['customer_name']}\n" .
                       "السيارة: {$r['plate_number']} - {$r['brand']} {$r['model']}\n" .
                       "رقم العقد: {$r['contract_no']}\n" .
                       "المبلغ المستحق: " . number_format((float)$r['remaining_amount'], 2) . " ر.س\n" .
                       "تاريخ الاستحقاق: {$r['due_date']}\n\n" .
                       "يرجى التواصل معنا لتأكيد الدفع.\n" .
                       "شكراً لتعاملكم معنا 🙏";
            
            $messages[] = [
                'customer_name'   => $r['customer_name'],
                'customer_phone'  => $phone,
                'formatted_phone' => $formattedPhone,
                'contract_no'     => $r['contract_no'],
                'amount'          => (float) $r['remaining_amount'],
                'due_date'        => $r['due_date'],
                'message'         => $message,
                'wa_link'         => $this->getWhatsAppLink($phone, $message),
            ];
        }
        
        return $messages;
    }
    
    /**
     * ✅ رسالة واتساب لعقد محدد
     */
    public function getWhatsAppReminderForRental(int $rentalId): ?array
    {
        try {
            $rentalModel = new Rental();
            $rental = $rentalModel->find($rentalId);
            
            if (!$rental) return null;
            
            $paidAmount = $rentalModel->getPaidAmount($rentalId);
            $remaining = (float) $rental['total_amount'] - $paidAmount;
            
            $nextPayment = $rentalModel->getNextPayment($rentalId);
            
            $phone = $rental['customer_phone'] ?? '';
            if (empty($phone)) return null;
            
            $message = "مرحباً {$rental['customer_name']}،\n\n" .
                       "نتواصل معك بخصوص عقد الإيجار: {$rental['contract_no']}\n" .
                       "السيارة: {$rental['plate_number']} - {$rental['brand']} {$rental['model']}\n\n";
            
            if ($remaining > 0) {
                $message .= "💰 المتبقي على العقد: " . number_format($remaining, 2) . " ر.س\n";
                
                if ($nextPayment) {
                    $message .= "📅 الدفعة القادمة: " . number_format((float)$nextPayment['amount'], 2) . " ر.س\n";
                    $message .= "🗓️ تاريخ الاستحقاق: {$nextPayment['due_date']}\n";
                }
            } else {
                $message .= "✅ العقد مسدد بالكامل\n";
            }
            
            $message .= "\nشكراً لتعاملكم معنا 🙏";
            
            return [
                'customer_name' => $rental['customer_name'],
                'customer_phone'=> $phone,
                'contract_no'   => $rental['contract_no'],
                'remaining'     => $remaining,
                'message'       => $message,
                'wa_link'       => $this->getWhatsAppLink($phone, $message),
            ];
            
        } catch (Exception $e) {
            error_log('WhatsApp reminder error: ' . $e->getMessage());
            return null;
        }
    }
    
    // ============================================================
    // ✅ جلب بيانات للتنبيهات
    // ============================================================
    
    /**
     * ✅ جلب العقود المستحقة اليوم
     */
    public function getDueTodayRentals(): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, 
                    cu.full_name AS customer_name,
                    cu.phone AS customer_phone,
                    c.plate_number, c.brand, c.model,
                    ps.id AS schedule_id,
                    ps.due_date,
                    (ps.amount - ps.paid_amount) AS remaining_amount
             FROM payment_schedules ps
             JOIN rentals r ON r.id = ps.rental_id
             JOIN customers cu ON cu.id = r.customer_id
             JOIN cars c ON c.id = r.car_id
             WHERE ps.status IN ('pending', 'partial')
             AND r.status = 'active'
             AND ps.due_date <= CURDATE()"
        );
    }
    
    /**
     * ✅ جلب العقود التي تنتهي قريباً
     */
    public function getUpcomingRentals(int $days = 3): array
    {
        return $this->db->fetchAll(
            "SELECT r.*, 
                    cu.full_name AS customer_name,
                    cu.phone AS customer_phone,
                    c.plate_number, c.brand, c.model
             FROM rentals r
             JOIN customers cu ON cu.id = r.customer_id
             JOIN cars c ON c.id = r.car_id
             WHERE r.status = 'active'
             AND r.return_due_datetime BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL :days DAY)
             ORDER BY r.return_due_datetime ASC",
            ['days' => $days]
        );
    }
    
    /**
     * ✅ جلب تفاصيل المتأخرات
     */
    public function getOverduePaymentsDetails(): array
    {
        return $this->db->fetchAll(
            "SELECT ps.*,
                    r.contract_no,
                    cu.full_name AS customer_name,
                    cu.phone AS customer_phone,
                    c.plate_number, c.brand, c.model,
                    DATEDIFF(CURDATE(), ps.due_date) AS days_overdue,
                    (ps.amount - ps.paid_amount) AS remaining_amount
             FROM payment_schedules ps
             JOIN rentals r ON r.id = ps.rental_id
             JOIN customers cu ON cu.id = r.customer_id
             JOIN cars c ON c.id = r.car_id
             WHERE ps.status IN ('pending', 'partial', 'overdue')
             AND r.status = 'active'
             AND ps.due_date < CURDATE()
             ORDER BY ps.due_date ASC"
        );
    }
    
    /**
     * ✅ جلب الوثائق التي تنتهي قريباً
     */
    public function getExpiringDocs(int $days = 30): array
    {
        return $this->db->fetchAll(
            "SELECT id, plate_number, brand, model,
                    registration_expiry, insurance_expiry, inspection_expiry
             FROM cars
             WHERE status NOT IN ('out_of_service')
             AND (
                 (registration_expiry IS NOT NULL AND registration_expiry <= DATE_ADD(CURDATE(), INTERVAL :days1 DAY))
                 OR (insurance_expiry IS NOT NULL AND insurance_expiry <= DATE_ADD(CURDATE(), INTERVAL :days2 DAY))
                 OR (inspection_expiry IS NOT NULL AND inspection_expiry <= DATE_ADD(CURDATE(), INTERVAL :days3 DAY))
             )
             ORDER BY plate_number",
            ['days1' => $days, 'days2' => $days, 'days3' => $days]
        );
    }
}