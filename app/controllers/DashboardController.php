<?php
/**
 * متحكم لوحة التحكم
 */

class DashboardController
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * عرض لوحة التحكم
     */
    public function index(): void
    {
        // التحقق من تسجيل الدخول
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        // جلب الإحصائيات
        $stats = $this->getStatistics();
        
        // جلب بيانات الرسم البياني
        $chartData = $this->getChartData();
        
        // جلب أحدث العقود
        $recentRentals = $this->getRecentRentals();
        
        // جلب التنبيهات
        $notifications = $this->getNotifications();
        
        // جلب أعلى السيارات ربحاً
        $topCars = $this->getTopCars();
        
        // جلب بيانات المستخدم
        $user = Auth::user();
        
        // عرض الصفحة
        require BASE_PATH . '/app/views/dashboard/index.php';
    }
    
    /**
     * جلب الإحصائيات
     */
    private function getStatistics(): array
    {
        $stats = [];
        
        try {
            // إجمالي السيارات
            $stats['total_cars'] = (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM cars"
            )['c'] ?? 0);
            
            // السيارات المتاحة
            $stats['available_cars'] = (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM cars WHERE status = 'available'"
            )['c'] ?? 0);
            
            // السيارات المؤجرة
            $stats['rented_cars'] = (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM cars WHERE status = 'rented'"
            )['c'] ?? 0);
            
            // السيارات في الصيانة
            $stats['maintenance_cars'] = (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM cars WHERE status = 'maintenance'"
            )['c'] ?? 0);
            
            // إجمالي المستأجرين
            $stats['total_customers'] = (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM customers"
            )['c'] ?? 0);
            
            // العقود النشطة
            $stats['active_rentals'] = (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM rentals WHERE status = 'active'"
            )['c'] ?? 0);
            
            // العقود المنتهية اليوم
            $stats['rentals_ending_today'] = (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM rentals 
                 WHERE status = 'active' 
                 AND DATE(return_due_datetime) = CURDATE()"
            )['c'] ?? 0);
            
            // إيرادات الشهر الحالي
            $stats['month_income'] = (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM payments 
                 WHERE payment_type IN ('rent', 'penalty')
                 AND MONTH(paid_at) = MONTH(CURDATE())
                 AND YEAR(paid_at) = YEAR(CURDATE())"
            )['total'] ?? 0);
            
            // مصروفات الشهر الحالي
            $stats['month_expenses'] = (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM car_expenses 
                 WHERE MONTH(expense_date) = MONTH(CURDATE())
                 AND YEAR(expense_date) = YEAR(CURDATE())"
            )['total'] ?? 0);
            
            // صافي الربح
            $stats['month_profit'] = $stats['month_income'] - $stats['month_expenses'];
            
            // دفعات اليوم
            $stats['today_payments'] = (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM payments 
                 WHERE DATE(paid_at) = CURDATE()
                 AND payment_type IN ('rent', 'penalty')"
            )['total'] ?? 0);
            
            // الكاش اليوم
            $stats['today_cash'] = (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM payments 
                 WHERE DATE(paid_at) = CURDATE()
                 AND method = 'cash'
                 AND payment_type IN ('rent', 'penalty')"
            )['total'] ?? 0);
            
            // الشبكة اليوم
            $stats['today_network'] = (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM payments 
                 WHERE DATE(paid_at) = CURDATE()
                 AND method = 'network'
                 AND payment_type IN ('rent', 'penalty')"
            )['total'] ?? 0);
            
            // المتأخرات
            $stats['overdue_payments'] = (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM payment_schedules 
                 WHERE status IN ('pending', 'partial', 'overdue')
                 AND due_date < CURDATE()"
            )['c'] ?? 0);
            
            // إجمالي المبالغ المتأخرة
            $stats['overdue_amount'] = (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount - paid_amount), 0) as total 
                 FROM payment_schedules 
                 WHERE status IN ('pending', 'partial', 'overdue')
                 AND due_date < CURDATE()"
            )['total'] ?? 0);
            
        } catch (Exception $e) {
            error_log('Dashboard stats error: ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * جلب بيانات الرسم البياني (آخر 12 شهراً)
     */
    private function getChartData(): array
    {
        $months = [];
        $income = [];
        $expenses = [];
        
        try {
            // آخر 12 شهراً
            for ($i = 11; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-{$i} months"));
                $months[] = $month;
                
                // الإيرادات
                $inc = $this->db->fetchOne(
                    "SELECT COALESCE(SUM(amount), 0) as total 
                     FROM payments 
                     WHERE DATE_FORMAT(paid_at, '%Y-%m') = :m
                     AND payment_type IN ('rent', 'penalty')",
                    ['m' => $month]
                );
                $income[] = (float) ($inc['total'] ?? 0);
                
                // المصروفات
                $exp = $this->db->fetchOne(
                    "SELECT COALESCE(SUM(amount), 0) as total 
                     FROM car_expenses 
                     WHERE DATE_FORMAT(expense_date, '%Y-%m') = :m",
                    ['m' => $month]
                );
                $expenses[] = (float) ($exp['total'] ?? 0);
            }
        } catch (Exception $e) {
            error_log('Chart data error: ' . $e->getMessage());
        }
        
        return [
            'months'   => $months,
            'income'   => $income,
            'expenses' => $expenses,
        ];
    }
    
    /**
     * جلب أحدث العقود
     */
    private function getRecentRentals(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT r.id, r.contract_no, r.status, r.total_amount,
                        r.pickup_datetime, r.return_due_datetime,
                        c.plate_number, c.brand, c.model,
                        cu.full_name as customer_name, cu.phone as customer_phone
                 FROM rentals r
                 JOIN cars c ON c.id = r.car_id
                 JOIN customers cu ON cu.id = r.customer_id
                 ORDER BY r.created_at DESC
                 LIMIT 5"
            );
        } catch (Exception $e) {
            error_log('Recent rentals error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * جلب التنبيهات
     */
    private function getNotifications(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT * FROM notifications 
                 WHERE is_read = 0 
                 ORDER BY due_date ASC
                 LIMIT 5"
            );
        } catch (Exception $e) {
            error_log('Notifications error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * جلب أعلى السيارات ربحاً
     */
    private function getTopCars(): array
    {
        try {
            return $this->db->fetchAll(
                "SELECT c.id, c.plate_number, c.brand, c.model,
                        COALESCE((SELECT SUM(p.amount)
                         FROM payments p
                         JOIN rentals r ON r.id = p.rental_id
                         WHERE r.car_id = c.id
                         AND p.payment_type IN ('rent', 'penalty')), 0) as income,
                        COALESCE((SELECT SUM(e.amount)
                         FROM car_expenses e
                         WHERE e.car_id = c.id), 0) as expenses
                 FROM cars c
                 ORDER BY income DESC
                 LIMIT 5"
            );
        } catch (Exception $e) {
            error_log('Top cars error: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * جلب أحدث التنبيهات للشريط العلوي (AJAX)
     */
    public function getNotificationsJson(): void
    {
        if (!Auth::check()) {
            http_response_code(401);
            echo json_encode(['error' => 'unauthorized']);
            exit;
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            $notifications = $this->db->fetchAll(
                "SELECT * FROM notifications 
                 WHERE is_read = 0 
                 ORDER BY due_date ASC
                 LIMIT 10"
            );
            
            echo json_encode([
                'success' => true,
                'count'   => count($notifications),
                'items'   => $notifications,
            ], JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}