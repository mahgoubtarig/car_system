<?php
/**
 * متحكم المدفوعات
 */

class PaymentController
{
    private Database $db;
    private Rental $rentalModel;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->rentalModel = new Rental();
    }
    
    /**
     * ✅ قائمة المدفوعات
     */
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        // الفلاتر
        $filters = [
            'search'    => trim($_GET['search'] ?? ''),
            'method'    => trim($_GET['method'] ?? ''),
            'type'      => trim($_GET['type'] ?? ''),
            'date_from' => trim($_GET['date_from'] ?? ''),
            'date_to'   => trim($_GET['date_to'] ?? ''),
            'tab'       => trim($_GET['tab'] ?? 'payments'),
        ];
        
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = 20;
        
        // إحصائيات
        $stats = $this->getStats();
        
        // الدفعات المسجلة
        $paymentsResult = $this->getPayments($filters, $page, $perPage);
        $payments = $paymentsResult['items'];
        $total = $paymentsResult['total'];
        $totalPages = $paymentsResult['total_pages'];
        $currentPage = $page;
        
        // الدفعات المجدولة (المتبقية)
        $schedules = $this->getSchedules($filters);
        
        // الدفعات القادمة
        $upcoming = $this->getUpcomingPayments(30);
        
        // الدفعات المتأخرة
        $overdue = $this->getOverduePayments();
        
        $user = Auth::user();
        $pageTitle = 'payments';
        
        require BASE_PATH . '/app/views/payments/index.php';
    }
    
    /**
     * ✅ حفظ دفعة جديدة (مع التحقق من عدم تجاوز المبلغ المتبقي)
     */
    public function store(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/payments'));
            exit;
        }
        
        // CSRF
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'انتهت صلاحية الجلسة. يرجى المحاولة مرة أخرى');
            $rentalId = (int) ($_POST['rental_id'] ?? 0);
            $referer = $rentalId > 0 ? url('/rentals/' . $rentalId) : url('/payments');
            header('Location: ' . $referer);
            exit;
        }
        
        // البيانات
        $rentalId = (int) ($_POST['rental_id'] ?? 0);
        $scheduleId = !empty($_POST['schedule_id']) ? (int) $_POST['schedule_id'] : null;
        $paymentType = $_POST['payment_type'] ?? 'rent';
        $method = $_POST['method'] ?? 'cash';
        $amount = (float) ($_POST['amount'] ?? 0);
        $referenceNo = trim($_POST['reference_no'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        
        $db = Database::getInstance();
        $errors = [];
        
        // التحقق الأساسي
        if ($rentalId <= 0) $errors[] = 'معرف العقد غير صحيح';
        if ($amount <= 0) $errors[] = 'المبلغ يجب أن يكون أكبر من صفر';
        if (!in_array($paymentType, ['rent', 'penalty', 'deposit_refund'])) $errors[] = 'نوع الدفعة غير صحيح';
        if (!in_array($method, ['cash', 'network', 'bank_transfer'])) $errors[] = 'طريقة الدفع غير صحيحة';
        
        // ✅ التحقق الحاسم: منع إدخال مبلغ أكبر من المتبقي (إذا كانت الدفعة إيجار)
        if ($paymentType === 'rent') {
            $rental = $db->fetchOne("SELECT total_amount FROM rentals WHERE id = :id", ['id' => $rentalId]);
            $totalAmount = (float) ($rental['total_amount'] ?? 0);

            $paidTotal = (float) ($db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE rental_id = :id AND payment_type IN ('rent', 'penalty')",
                ['id' => $rentalId]
            )['total'] ?? 0);

            $remaining = $totalAmount - $paidTotal;

            // نستخدم round لتجنب أخطاء الفواصل العشرية
            if (round($amount, 2) > round($remaining, 2)) {
                $errors[] = 'مرفوض! المبلغ المدخل (' . $amount . ' ر.س) أكبر من المبلغ المتبقي في العقد (' . number_format($remaining, 2) . ' ر.س)';
            }
        }
        
        if (!empty($errors)) {
            Auth::setFlash('error', implode(' | ', $errors));
            header('Location: ' . url('/rentals/' . $rentalId));
            exit;
        }
        
        try {
            $db->beginTransaction();
            
            // 1. توليد رقم السند
            $receiptNo = $this->generateReceiptNo();
            
            // 2. إدراج الدفعة
            $paymentId = $db->insert(
                "INSERT INTO payments 
                    (receipt_no, rental_id, schedule_id, payment_type, method,
                     amount, reference_no, paid_at, received_by, notes)
                 VALUES 
                    (:receipt_no, :rental_id, :schedule_id, :payment_type, :method,
                     :amount, :reference_no, NOW(), :received_by, :notes)",
                [
                    'receipt_no'    => $receiptNo,
                    'rental_id'     => $rentalId,
                    'schedule_id'   => $scheduleId,
                    'payment_type'  => $paymentType,
                    'method'        => $method,
                    'amount'        => $amount,
                    'reference_no'  => $referenceNo ?: null,
                    'received_by'   => Auth::id(),
                    'notes'         => $notes ?: null,
                ]
            );
            
            // 3. تحديث حالة الدفعة المجدولة
            if ($scheduleId) {
                $this->updateScheduleStatus($scheduleId);
                // 4. إغلاق التنبيهات المرتبطة بالدفعة
                $this->clearSchedulePayment($scheduleId);
            } else {
                // إذا لم تُحدد جدولة، حدّث جميع الجدولات المعلقة
                $this->updateAllSchedules($rentalId, $amount);
            }
            
            // 5. تسجيل النشاط
            Auth::logActivityStatic(Auth::id(), 'create', 'payments', [
                'payment_id' => $paymentId,
                'rental_id'  => $rentalId,
                'amount'     => $amount,
            ]);
            
            $db->commit();
            
            Auth::setFlash('success', "تم تسجيل الدفعة بنجاح — رقم السند: {$receiptNo}");
            
            // العودة للصفحة السابقة
            $returnTo = $_POST['return_to'] ?? 'rental';
            if ($returnTo === 'payments') {
                header('Location: ' . url('/payments'));
            } else {
                header('Location: ' . url('/rentals/' . $rentalId));
            }
            exit;
            
        } catch (Exception $e) {
            if (isset($db)) $db->rollback();
            error_log('Payment store error: ' . $e->getMessage());
            Auth::setFlash('error', 'حدث خطأ أثناء الحفظ: ' . $e->getMessage());
            header('Location: ' . url('/rentals/' . $rentalId));
            exit;
        }
    }

    /**
     * ✅ طباعة سند قبض / دفع
     */
    public function print(int $id): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $payment = $this->db->fetchOne(
            "SELECT p.*,
                    r.contract_no,
                    cu.full_name AS customer_name,
                    cu.id_number AS customer_id_number,
                    cu.phone AS customer_phone,
                    c.plate_number, c.brand, c.model,
                    u.full_name AS received_by_name
             FROM payments p
             LEFT JOIN rentals r ON r.id = p.rental_id
             LEFT JOIN customers cu ON cu.id = r.customer_id
             LEFT JOIN cars c ON c.id = r.car_id
             LEFT JOIN users u ON u.id = p.received_by
             WHERE p.id = :id",
            ['id' => $id]
        );
        
        if (!$payment) {
            Auth::setFlash('error', 'السند غير موجود');
            header('Location: ' . url('/payments'));
            exit;
        }
        
        // 🚀 جلب إعدادات المكتب لطباعتها في السند
        require_once BASE_PATH . '/app/models/Setting.php';
        $settingModel = new Setting();
        $settings = $settingModel->getAll();
        
        $office = [
            'name_ar' => $settings['office_name'] ?? e('app_name'),
            'name_en' => $settings['office_name_en'] ?? '',
            'phone' => $settings['office_phone'] ?? '',
            'address' => $settings['office_address'] ?? '',
            'tax_number' => $settings['tax_number'] ?? '',
        ];
        
        require BASE_PATH . '/app/views/payments/print.php';
    }
    
    /**
     * ✅ جلب المدفوعات
     */
    private function getPayments(array $filters, int $page, int $perPage): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['search'])) {
            $where[] = "(p.receipt_no LIKE :search_receipt 
                        OR r.contract_no LIKE :search_contract 
                        OR cu.full_name LIKE :search_customer)";
            $searchValue = '%' . $filters['search'] . '%';
            $params['search_receipt'] = $searchValue;
            $params['search_contract'] = $searchValue;
            $params['search_customer'] = $searchValue;
        }
        
        if (!empty($filters['method'])) {
            $where[] = "p.method = :method";
            $params['method'] = $filters['method'];
        }
        
        if (!empty($filters['type'])) {
            $where[] = "p.payment_type = :type";
            $params['type'] = $filters['type'];
        }
        
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(p.paid_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(p.paid_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // العدد
        $total = (int) ($this->db->fetchOne(
            "SELECT COUNT(*) as c FROM payments p
             LEFT JOIN rentals r ON r.id = p.rental_id
             LEFT JOIN customers cu ON cu.id = r.customer_id
             {$whereSql}",
            $params
        )['c'] ?? 0);
        
        // Pagination
        $offset = ($page - 1) * $perPage;
        
        // البيانات
        $payments = $this->db->fetchAll(
            "SELECT p.*,
                    r.contract_no,
                    cu.full_name AS customer_name,
                    cu.phone AS customer_phone,
                    c.plate_number, c.brand, c.model,
                    u.full_name AS received_by_name
             FROM payments p
             LEFT JOIN rentals r ON r.id = p.rental_id
             LEFT JOIN customers cu ON cu.id = r.customer_id
             LEFT JOIN cars c ON c.id = r.car_id
             LEFT JOIN users u ON u.id = p.received_by
             {$whereSql}
             ORDER BY p.paid_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        
        return [
            'items'      => $payments,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages'=> (int) ceil($total / $perPage),
        ];
    }
    
    /**
     * ✅ جلب الدفعات المجدولة (المتبقية)
     */
    private function getSchedules(array $filters): array
    {
        $where = ["ps.status IN ('pending', 'partial', 'overdue')", "r.status = 'active'"];
        $params = [];
        
        if (!empty($filters['search'])) {
            $where[] = "(r.contract_no LIKE :s_contract 
                        OR cu.full_name LIKE :s_customer 
                        OR c.plate_number LIKE :s_plate)";
            $searchValue = '%' . $filters['search'] . '%';
            $params['s_contract'] = $searchValue;
            $params['s_customer'] = $searchValue;
            $params['s_plate'] = $searchValue;
        }
        
        $whereSql = 'WHERE ' . implode(' AND ', $where);
        
        return $this->db->fetchAll(
            "SELECT ps.*,
                    r.contract_no,
                    r.return_due_datetime,
                    cu.full_name AS customer_name,
                    cu.phone AS customer_phone,
                    c.plate_number, c.brand, c.model,
                    DATEDIFF(ps.due_date, CURDATE()) AS days_remaining,
                    (ps.amount - ps.paid_amount) AS remaining_amount
             FROM payment_schedules ps
             JOIN rentals r ON r.id = ps.rental_id
             JOIN customers cu ON cu.id = r.customer_id
             JOIN cars c ON c.id = r.car_id
             {$whereSql}
             ORDER BY ps.due_date ASC",
            $params
        );
    }
    
    /**
     * ✅ الدفعات القادمة
     */
    private function getUpcomingPayments(int $days = 30): array
    {
        return $this->db->fetchAll(
            "SELECT ps.*,
                    r.contract_no,
                    cu.full_name AS customer_name,
                    cu.phone AS customer_phone,
                    c.plate_number, c.brand, c.model,
                    DATEDIFF(ps.due_date, CURDATE()) AS days_remaining
             FROM payment_schedules ps
             JOIN rentals r ON r.id = ps.rental_id
             JOIN customers cu ON cu.id = r.customer_id
             JOIN cars c ON c.id = r.car_id
             WHERE ps.status IN ('pending', 'partial')
             AND r.status = 'active'
             AND ps.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL :days DAY)
             ORDER BY ps.due_date ASC",
            ['days' => $days]
        );
    }
    
    /**
     * ✅ الدفعات المتأخرة
     */
    private function getOverduePayments(): array
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
     * ✅ الإحصائيات
     */
    private function getStats(): array
    {
        return [
            'total_payments' => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM payments")['c'] ?? 0),
            'today_payments' => (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments 
                 WHERE DATE(paid_at) = CURDATE()
                 AND payment_type IN ('rent', 'penalty')"
            )['total'] ?? 0),
            'today_cash' => (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments 
                 WHERE DATE(paid_at) = CURDATE()
                 AND method = 'cash'
                 AND payment_type IN ('rent', 'penalty')"
            )['total'] ?? 0),
            'today_network' => (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments 
                 WHERE DATE(paid_at) = CURDATE()
                 AND method = 'network'
                 AND payment_type IN ('rent', 'penalty')"
            )['total'] ?? 0),
            'month_total' => (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments 
                 WHERE MONTH(paid_at) = MONTH(CURDATE())
                 AND YEAR(paid_at) = YEAR(CURDATE())
                 AND payment_type IN ('rent', 'penalty')"
            )['total'] ?? 0),
            'total_remaining' => (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(ps.amount - ps.paid_amount), 0) as total
                 FROM payment_schedules ps
                 JOIN rentals r ON r.id = ps.rental_id
                 WHERE ps.status IN ('pending', 'partial', 'overdue')
                 AND r.status = 'active'"
            )['total'] ?? 0),
            'overdue_count' => (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM payment_schedules ps
                 JOIN rentals r ON r.id = ps.rental_id
                 WHERE ps.status IN ('pending', 'partial', 'overdue')
                 AND r.status = 'active'
                 AND ps.due_date < CURDATE()"
            )['c'] ?? 0),
            'overdue_amount' => (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(ps.amount - ps.paid_amount), 0) as total
                 FROM payment_schedules ps
                 JOIN rentals r ON r.id = ps.rental_id
                 WHERE ps.status IN ('pending', 'partial', 'overdue')
                 AND r.status = 'active'
                 AND ps.due_date < CURDATE()"
            )['total'] ?? 0),
        ];
    }
    
    /**
     * ✅ تحديث حالة دفعة مجدولة
     */
    private function updateScheduleStatus(int $scheduleId): void
    {
        $schedule = $this->db->fetchOne(
            "SELECT * FROM payment_schedules WHERE id = :id",
            ['id' => $scheduleId]
        );
        
        if (!$schedule) return;
        
        $paid = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM payments
             WHERE schedule_id = :id
             AND payment_type IN ('rent', 'penalty')",
            ['id' => $scheduleId]
        )['total'] ?? 0);
        
        $status = 'pending';
        if ($paid >= (float) $schedule['amount']) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        } elseif (strtotime($schedule['due_date']) < time()) {
            $status = 'overdue';
        }
        
        $this->db->execute(
            "UPDATE payment_schedules 
             SET paid_amount = :paid, status = :status 
             WHERE id = :id",
            ['paid' => $paid, 'status' => $status, 'id' => $scheduleId]
        );
    }
    
    /**
     * ✅ تحديث جميع الجدولات المعلقة
     */
    private function updateAllSchedules(int $rentalId, float $paymentAmount): void
    {
        $schedules = $this->db->fetchAll(
            "SELECT * FROM payment_schedules 
             WHERE rental_id = :id 
             AND status IN ('pending', 'partial', 'overdue')
             ORDER BY due_date ASC",
            ['id' => $rentalId]
        );
        
        $remaining = $paymentAmount;
        
        foreach ($schedules as $s) {
            if ($remaining <= 0) break;
            
            $scheduleRemaining = (float) $s['amount'] - (float) $s['paid_amount'];
            $apply = min($remaining, $scheduleRemaining);
            
            $newPaid = (float) $s['paid_amount'] + $apply;
            $status = $newPaid >= (float) $s['amount'] ? 'paid' : 'partial';
            
            $this->db->execute(
                "UPDATE payment_schedules 
                 SET paid_amount = :paid, status = :status 
                 WHERE id = :id",
                ['paid' => $newPaid, 'status' => $status, 'id' => $s['id']]
            );
            
            // إغلاق التنبيهات المرتبطة بهذه الجدولة بعد الدفع
            $this->clearSchedulePayment((int) $s['id']);
            
            // ربط الدفعة بالجدول
            $this->db->execute(
                "UPDATE payments 
                 SET schedule_id = :schedule_id 
                 WHERE rental_id = :rental_id 
                 AND schedule_id IS NULL
                 ORDER BY id DESC LIMIT 1",
                ['schedule_id' => $s['id'], 'rental_id' => $rentalId]
            );
            
            $remaining -= $apply;
        }
    }
    
    /**
     * ✅ إزالة/إغلاق التنبيهات المرتبطة بالجدولة عند الدفع
     */
    private function clearSchedulePayment(int $scheduleId): void
    {
        try {
            $this->db->execute(
                "UPDATE notifications 
                 SET is_read = 1, processed_at = NOW() 
                 WHERE ref_table = 'payment_schedules' AND ref_id = :id AND is_read = 0",
                ['id' => $scheduleId]
            );
        } catch (Exception $e) {
            // تجاهل الخطأ في حالة عدم وجود جدول notifications 
            // error_log('Clear schedule notification error: ' . $e->getMessage());
        }
    }

    /**
     * ✅ توليد رقم السند
     */
    private function generateReceiptNo(): string
    {
        $year = date('Y');
        
        $last = $this->db->fetchOne(
            "SELECT receipt_no FROM payments 
             WHERE receipt_no LIKE :pattern
             ORDER BY id DESC LIMIT 1",
            ['pattern' => "RCP-{$year}-%"]
        );
        
        if ($last) {
            $parts = explode('-', $last['receipt_no']);
            $number = (int) end($parts) + 1;
        } else {
            $number = 1;
        }
        
        return sprintf('RCP-%s-%04d', $year, $number);
    }
}