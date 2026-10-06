<?php
/**
 * نموذج عقد الإيجار
 */

class Rental
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * جلب كل العقود مع الفلترة والبحث
     */
    public function all(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $where = [];
        $params = [];
        
        // البحث
        if (!empty($filters['search'])) {
            $where[] = "(r.contract_no LIKE :search_contract 
                        OR cu.full_name LIKE :search_customer 
                        OR c.plate_number LIKE :search_plate)";
            
            $searchValue = '%' . $filters['search'] . '%';
            $params['search_contract'] = $searchValue;
            $params['search_customer'] = $searchValue;
            $params['search_plate']    = $searchValue;
        }
        
        // الحالة
        if (!empty($filters['status'])) {
            $where[] = "r.status = :status";
            $params['status'] = $filters['status'];
        }
        
        // من تاريخ
        if (!empty($filters['date_from'])) {
            $where[] = "DATE(r.pickup_datetime) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        // إلى تاريخ
        if (!empty($filters['date_to'])) {
            $where[] = "DATE(r.pickup_datetime) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // العدد الإجمالي
        $total = (int) ($this->db->fetchOne(
            "SELECT COUNT(*) as c 
             FROM rentals r
             LEFT JOIN customers cu ON cu.id = r.customer_id
             LEFT JOIN cars c ON c.id = r.car_id
             {$whereSql}",
            $params
        )['c'] ?? 0);
        
        // Pagination
        $offset = ($page - 1) * $perPage;
        
        // جلب البيانات
        $rentals = $this->db->fetchAll(
            "SELECT r.*,
                    cu.full_name AS customer_name,
                    cu.phone AS customer_phone,
                    c.plate_number, c.brand, c.model,
                    (SELECT COALESCE(SUM(p.amount), 0)
                     FROM payments p
                     WHERE p.rental_id = r.id
                     AND p.payment_type IN ('rent', 'penalty')) AS paid_amount
             FROM rentals r
             LEFT JOIN customers cu ON cu.id = r.customer_id
             LEFT JOIN cars c ON c.id = r.car_id
             {$whereSql}
             ORDER BY r.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        
        return [
            'items'      => $rentals,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages'=> (int) ceil($total / $perPage),
        ];
    }
    
    /**
     * جلب عقد بالمعرف
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT r.*,
                    cu.full_name AS customer_name,
                    cu.phone AS customer_phone,
                    cu.id_number AS customer_id_number,
                    c.plate_number, c.brand, c.model, c.year, c.color,
                    u.full_name AS created_by_name
             FROM rentals r
             LEFT JOIN customers cu ON cu.id = r.customer_id
             LEFT JOIN cars c ON c.id = r.car_id
             LEFT JOIN users u ON u.id = r.created_by
             WHERE r.id = :id
             LIMIT 1",
            ['id' => $id]
        );
    }
    
    /**
     * إنشاء عقد جديد
     */
    public function create(array $data): int
    {
        // توليد رقم العقد
        $data['contract_no'] = $this->generateContractNo();
        
        $sql = "INSERT INTO rentals 
                (contract_no, car_id, customer_id, created_by,
                 pickup_datetime, pickup_location,
                 return_due_datetime, return_location,
                 rate_type, rate_amount, rental_days,
                 subtotal, discount, tax_amount, extra_charges,
                 total_amount, deposit_amount, km_limit_per_day,
                 status, notes)
                VALUES 
                (:contract_no, :car_id, :customer_id, :created_by,
                 :pickup_datetime, :pickup_location,
                 :return_due_datetime, :return_location,
                 :rate_type, :rate_amount, :rental_days,
                 :subtotal, :discount, :tax_amount, :extra_charges,
                 :total_amount, :deposit_amount, :km_limit_per_day,
                 :status, :notes)";
        
        return $this->db->insert($sql, $data);
    }
    
    /**
     * توليد رقم العقد
     */
    public function generateContractNo(): string
    {
        $year = date('Y');
        
        $last = $this->db->fetchOne(
            "SELECT contract_no FROM rentals 
             WHERE contract_no LIKE :pattern
             ORDER BY id DESC LIMIT 1",
            ['pattern' => "RC-{$year}-%"]
        );
        
        if ($last) {
            $parts = explode('-', $last['contract_no']);
            $number = (int) end($parts) + 1;
        } else {
            $number = 1;
        }
        
        return sprintf('RC-%s-%04d', $year, $number);
    }
    
    /**
     * تحديث عقد
     */
    public function update(int $id, array $data): int
    {
        $data['id'] = $id;
        
        $sql = "UPDATE rentals SET
                pickup_datetime = :pickup_datetime,
                pickup_location = :pickup_location,
                return_due_datetime = :return_due_datetime,
                return_location = :return_location,
                rate_type = :rate_type,
                rate_amount = :rate_amount,
                rental_days = :rental_days,
                subtotal = :subtotal,
                discount = :discount,
                tax_amount = :tax_amount,
                extra_charges = :extra_charges,
                total_amount = :total_amount,
                deposit_amount = :deposit_amount,
                km_limit_per_day = :km_limit_per_day,
                status = :status,
                notes = :notes
                WHERE id = :id";
        
        return $this->db->execute($sql, $data);
    }
    
    /**
     * إغلاق عقد
     */
    public function close(int $id): int
    {
        return $this->db->execute(
            "UPDATE rentals SET 
                status = 'completed',
                actual_return_datetime = NOW()
             WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * إلغاء عقد
     */
    public function cancel(int $id, string $reason = ''): int
    {
        return $this->db->execute(
            "UPDATE rentals SET 
                status = 'cancelled',
                cancellation_reason = :reason
             WHERE id = :id",
            ['id' => $id, 'reason' => $reason]
        );
    }
    
    /**
     * تغيير حالة العقد
     */
    public function changeStatus(int $id, string $status): int
    {
        return $this->db->execute(
            "UPDATE rentals SET status = :status WHERE id = :id",
            ['status' => $status, 'id' => $id]
        );
    }
    
    /**
     * التحقق من التداخل
     */
    public function hasOverlap(int $carId, string $pickup, string $return, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as c FROM rentals 
                WHERE car_id = :car_id
                AND status IN ('reserved', 'active')
                AND pickup_datetime < :return_due
                AND return_due_datetime > :pickup";
        
        $params = [
            'car_id'    => $carId,
            'pickup'    => $pickup,
            'return_due'=> $return,
        ];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        return (int) ($this->db->fetchOne($sql, $params)['c'] ?? 0) > 0;
    }
    
    /**
     * جلب السيارات المتاحة
     */
    public function getAvailableCars(string $pickup, string $return): array
    {
        return $this->db->fetchAll(
            "SELECT c.*, ct.name_ar AS type_name_ar, ct.name_en AS type_name_en
             FROM cars c
             LEFT JOIN car_types ct ON ct.id = c.car_type_id
             WHERE c.status NOT IN ('maintenance', 'out_of_service')
             AND c.id NOT IN (
                 SELECT car_id FROM rentals
                 WHERE status IN ('reserved', 'active')
                 AND pickup_datetime < :return_due
                 AND return_due_datetime > :pickup
             )
             ORDER BY c.brand, c.model",
            ['pickup' => $pickup, 'return_due' => $return]
        );
    }
    
    /**
     * جلب دفعات العقد
     */
    public function getPayments(int $rentalId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM payments 
             WHERE rental_id = :id
             ORDER BY paid_at DESC",
            ['id' => $rentalId]
        );
    }
    
    /**
     * جلب جدول الدفعات
     */
    public function getPaymentSchedules(int $rentalId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM payment_schedules 
             WHERE rental_id = :id
             ORDER BY installment_no",
            ['id' => $rentalId]
        );
    }
    
    /**
     * جلب الفحوصات
     */
    public function getInspections(int $rentalId): array
    {
        $inspections = $this->db->fetchAll(
            "SELECT * FROM rental_inspections 
             WHERE rental_id = :id
             ORDER BY inspected_at DESC",
            ['id' => $rentalId]
        );
        
        foreach ($inspections as &$insp) {
            $insp['photos'] = $this->db->fetchAll(
                "SELECT * FROM inspection_photos 
                 WHERE inspection_id = :id",
                ['id' => $insp['id']]
            );
        }
        
        return $inspections;
    }
    
    // ============================================================
    // ✅ دوال الدفعات الجديدة
    // ============================================================
    
    /**
     * ✅ حساب المبلغ المدفوع للعقد
     */
    public function getPaidAmount(int $rentalId): float
    {
        $result = $this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total 
             FROM payments 
             WHERE rental_id = :id 
             AND payment_type IN ('rent', 'penalty')",
            ['id' => $rentalId]
        );
        
        return (float) ($result['total'] ?? 0);
    }
    
    /**
     * ✅ حساب المتبقي على العقد
     */
    public function getRemainingAmount(int $rentalId): float
    {
        $rental = $this->find($rentalId);
        if (!$rental) {
            return 0;
        }
        
        $paid = $this->getPaidAmount($rentalId);
        return (float) $rental['total_amount'] - $paid;
    }
    
    /**
     * ✅ جلب الدفعة القادمة
     */
    public function getNextPayment(int $rentalId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM payment_schedules 
             WHERE rental_id = :id 
             AND status IN ('pending', 'partial', 'overdue')
             ORDER BY due_date ASC 
             LIMIT 1",
            ['id' => $rentalId]
        );
    }
    
    /**
     * ✅ جلب الدفعات القادمة (خلال N يوم)
     */
    public function getUpcomingPayments(int $days = 7): array
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
     * ✅ جلب الدفعات المتأخرة
     */
    public function getOverduePayments(): array
    {
        return $this->db->fetchAll(
            "SELECT ps.*,
                    r.contract_no,
                    cu.full_name AS customer_name,
                    cu.phone AS customer_phone,
                    c.plate_number, c.brand, c.model,
                    DATEDIFF(CURDATE(), ps.due_date) AS days_overdue
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
     * ✅ إحصائيات الدفعات
     */
    public function getPaymentStats(): array
    {
        return [
            'upcoming' => (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM payment_schedules ps
                 JOIN rentals r ON r.id = ps.rental_id
                 WHERE ps.status IN ('pending', 'partial')
                 AND r.status = 'active'
                 AND ps.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"
            )['c'] ?? 0),
            
            'overdue' => (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM payment_schedules ps
                 JOIN rentals r ON r.id = ps.rental_id
                 WHERE ps.status IN ('pending', 'partial', 'overdue')
                 AND r.status = 'active'
                 AND ps.due_date < CURDATE()"
            )['c'] ?? 0),
            
            'total_due' => (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(ps.amount - ps.paid_amount), 0) as total
                 FROM payment_schedules ps
                 JOIN rentals r ON r.id = ps.rental_id
                 WHERE ps.status IN ('pending', 'partial', 'overdue')
                 AND r.status = 'active'"
            )['total'] ?? 0),
            
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
     * ✅ تحديث حالة الدفعة
     */
    public function updateScheduleStatus(int $scheduleId): void
    {
        $schedule = $this->db->fetchOne(
            "SELECT * FROM payment_schedules WHERE id = :id",
            ['id' => $scheduleId]
        );
        
        if (!$schedule) return;
        
        // حساب المدفوع لهذه الدفعة
        $paid = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM payments
             WHERE schedule_id = :id
             AND payment_type IN ('rent', 'penalty')",
            ['id' => $scheduleId]
        )['total'] ?? 0);
        
        // تحديد الحالة
        $status = 'pending';
        
        if ($paid >= (float) $schedule['amount']) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        } elseif (strtotime($schedule['due_date']) < time()) {
            $status = 'overdue';
        }
        
        // تحديث
        $this->db->execute(
            "UPDATE payment_schedules 
             SET paid_amount = :paid, status = :status 
             WHERE id = :id",
            [
                'paid'   => $paid,
                'status' => $status,
                'id'     => $scheduleId,
            ]
        );
    }
    
    /**
     * ✅ إنشاء جدول دفعات
     */
    public function createSchedule(int $rentalId, string $dueDate, float $amount, int $installmentNo = 1): int
    {
        return $this->db->insert(
            "INSERT INTO payment_schedules 
                (rental_id, installment_no, due_date, amount, paid_amount, status)
             VALUES 
                (:rental_id, :installment_no, :due_date, :amount, 0, 'pending')",
            [
                'rental_id'      => $rentalId,
                'installment_no' => $installmentNo,
                'due_date'       => $dueDate,
                'amount'         => $amount,
            ]
        );
    }
    
    /**
     * ✅ حذف جدول دفعات العقد
     */
    public function deleteSchedules(int $rentalId): int
    {
        return $this->db->execute(
            "DELETE FROM payment_schedules WHERE rental_id = :id",
            ['id' => $rentalId]
        );
    }
    
    
    /**
     * ✅ إحصائيات العقود
     */
    public function getStats(): array
    {
        return [
            'total'      => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM rentals")['c'] ?? 0),
            'active'     => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM rentals WHERE status = 'active'")['c'] ?? 0),
            'reserved'   => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM rentals WHERE status = 'reserved'")['c'] ?? 0),
            'completed'  => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM rentals WHERE status = 'completed'")['c'] ?? 0),
            'cancelled'  => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM rentals WHERE status = 'cancelled'")['c'] ?? 0),
            'overdue'    => (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM rentals 
                 WHERE status = 'active' 
                 AND return_due_datetime < NOW()"
            )['c'] ?? 0),
            'ending_today' => (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM rentals 
                 WHERE status = 'active' 
                 AND DATE(return_due_datetime) = CURDATE()"
            )['c'] ?? 0),
            'total_income' => (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(total_amount), 0) as total FROM rentals 
                 WHERE status IN ('active', 'completed')"
            )['total'] ?? 0),
            'total_paid' => (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total FROM payments 
                 WHERE payment_type IN ('rent', 'penalty')"
            )['total'] ?? 0),
        ];
    }
}