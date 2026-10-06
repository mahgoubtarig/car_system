<?php
/**
 * متحكم التقارير
 * 
 * الإصلاحات المطبقة:
 * - حساب profit و margin في getCarsProfit() و getCarsReport()
 * - إصلاح export() لتفادي HTML warnings في CSV
 * - دعم BOM للعربية في Excel
 */

class ReportController
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * الصفحة الرئيسية للتقارير
     */
    public function index(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $summary = $this->getSummary($dateFrom, $dateTo);
        $carsProfit = $this->getCarsProfit($dateFrom, $dateTo);
        $expensesByCategory = $this->getExpensesByCategory($dateFrom, $dateTo);
        $monthlyData = $this->getMonthlyData(12);
        
        $user = Auth::user();
        $pageTitle = 'reports';
        
        require BASE_PATH . '/app/views/reports/index.php';
    }
    
    /**
     * تقرير الأرباح
     */
    public function profit(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $carsProfit = $this->getCarsProfit($dateFrom, $dateTo);
        $summary = $this->getSummary($dateFrom, $dateTo);
        
        $user = Auth::user();
        $pageTitle = 'profit_report';
        
        require BASE_PATH . '/app/views/reports/profit.php';
    }
    
    /**
     * تقرير المصروفات
     */
    public function expenses(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $expensesByCategory = $this->getExpensesByCategory($dateFrom, $dateTo);
        $expensesByCar = $this->getExpensesByCar($dateFrom, $dateTo);
        $summary = $this->getSummary($dateFrom, $dateTo);
        
        $user = Auth::user();
        $pageTitle = 'expenses_report';
        
        require BASE_PATH . '/app/views/reports/expenses.php';
    }
    
    /**
     * تقرير السيارات
     */
    public function cars(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $carsReport = $this->getCarsReport($dateFrom, $dateTo);
        
        $user = Auth::user();
        $pageTitle = 'cars_report';
        
        require BASE_PATH . '/app/views/reports/cars.php';
    }
    
    /**
     * تقرير المدفوعات
     */
    public function payments(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $paymentsByMethod = $this->getPaymentsByMethod($dateFrom, $dateTo);
        $paymentsByDay = $this->getPaymentsByDay($dateFrom, $dateTo);
        $summary = $this->getSummary($dateFrom, $dateTo);
        
        $user = Auth::user();
        $pageTitle = 'payments_report';
        
        require BASE_PATH . '/app/views/reports/payments.php';
    }
    
    /**
     * ✅ تصدير CSV (مُصلح)
     */
    public function export(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        // ✅ تعطيل عرض الأخطاء (يمنع HTML warnings في CSV)
        error_reporting(0);
        ini_set('display_errors', '0');
        
        // ✅ مسح أي output سابق
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        $type = $_GET['type'] ?? 'profit';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $filename = "report_{$type}_{$dateFrom}_to_{$dateTo}.csv";
        
        // ✅ رؤوس CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        // ✅ BOM لدعم العربية في Excel
        echo "\xEF\xBB\xBF";
        
        $output = fopen('php://output', 'w');
        
        // ============================================================
        // تقرير الأرباح
        // ============================================================
        if ($type === 'profit') {
            fputcsv($output, [
                'السيارة',
                'اللوحة',
                'الإيرادات',
                'المصروفات',
                'صافي الربح',
                'الهامش %'
            ]);
            
            $cars = $this->getCarsProfit($dateFrom, $dateTo);
            
            foreach ($cars as $c) {
                fputcsv($output, [
                    trim(($c['brand'] ?? '') . ' ' . ($c['model'] ?? '')),
                    $c['plate_number'] ?? '',
                    number_format((float)($c['income'] ?? 0), 2, '.', ''),
                    number_format((float)($c['expenses'] ?? 0), 2, '.', ''),
                    number_format((float)($c['profit'] ?? 0), 2, '.', ''),
                    number_format((float)($c['margin'] ?? 0), 2, '.', '') . '%',
                ]);
            }
        }
        
        // ============================================================
        // تقرير المصروفات
        // ============================================================
        elseif ($type === 'expenses') {
            fputcsv($output, [
                'التاريخ',
                'السيارة',
                'اللوحة',
                'التصنيف',
                'الوصف',
                'المبلغ'
            ]);
            
            $expenses = $this->db->fetchAll(
                "SELECT e.*, 
                        c.plate_number, c.brand, c.model, 
                        ec.name_ar AS category_ar
                 FROM car_expenses e
                 LEFT JOIN cars c ON c.id = e.car_id
                 LEFT JOIN expense_categories ec ON ec.id = e.category_id
                 WHERE e.expense_date BETWEEN :from AND :to
                 ORDER BY e.expense_date DESC",
                ['from' => $dateFrom, 'to' => $dateTo]
            );
            
            foreach ($expenses as $e) {
                fputcsv($output, [
                    $e['expense_date'] ?? '',
                    trim(($e['brand'] ?? '') . ' ' . ($e['model'] ?? '')),
                    $e['plate_number'] ?? '',
                    $e['category_ar'] ?? '',
                    $e['description'] ?? '',
                    number_format((float)($e['amount'] ?? 0), 2, '.', ''),
                ]);
            }
        }
        
        // ============================================================
        // تقرير المدفوعات
        // ============================================================
        elseif ($type === 'payments') {
            fputcsv($output, [
                'التاريخ',
                'رقم السند',
                'العقد',
                'المستأجر',
                'الطريقة',
                'المبلغ'
            ]);
            
            $payments = $this->db->fetchAll(
                "SELECT p.*, 
                        r.contract_no, 
                        cu.full_name AS customer_name
                 FROM payments p
                 LEFT JOIN rentals r ON r.id = p.rental_id
                 LEFT JOIN customers cu ON cu.id = r.customer_id
                 WHERE DATE(p.paid_at) BETWEEN :from AND :to
                 ORDER BY p.paid_at DESC",
                ['from' => $dateFrom, 'to' => $dateTo]
            );
            
            $methodAr = [
                'cash' => 'كاش',
                'network' => 'شبكة',
                'bank_transfer' => 'تحويل بنكي',
            ];
            
            foreach ($payments as $p) {
                fputcsv($output, [
                    date('Y-m-d', strtotime($p['paid_at'] ?? 'now')),
                    $p['receipt_no'] ?? '',
                    $p['contract_no'] ?? '',
                    $p['customer_name'] ?? '',
                    $methodAr[$p['method'] ?? ''] ?? ($p['method'] ?? ''),
                    number_format((float)($p['amount'] ?? 0), 2, '.', ''),
                ]);
            }
        }
        
        // ============================================================
        // تقرير السيارات
        // ============================================================
        elseif ($type === 'cars') {
            fputcsv($output, [
                'السيارة',
                'اللوحة',
                'النوع',
                'السنة',
                'عدد العقود',
                'أيام التأجير',
                'الإيرادات',
                'المصروفات',
                'صافي الربح',
                'الحالة'
            ]);
            
            $cars = $this->getCarsReport($dateFrom, $dateTo);
            
            foreach ($cars as $c) {
                $typeName = Lang::isArabic() 
                    ? ($c['type_name_ar'] ?? '')
                    : ($c['type_name_en'] ?? '');
                
                fputcsv($output, [
                    trim(($c['brand'] ?? '') . ' ' . ($c['model'] ?? '')),
                    $c['plate_number'] ?? '',
                    $typeName,
                    $c['year'] ?? '',
                    $c['total_rentals'] ?? 0,
                    $c['rental_days'] ?? 0,
                    number_format((float)($c['income'] ?? 0), 2, '.', ''),
                    number_format((float)($c['expenses'] ?? 0), 2, '.', ''),
                    number_format((float)($c['profit'] ?? 0), 2, '.', ''),
                    $c['status'] ?? '',
                ]);
            }
        }
        
        fclose($output);
        exit;
    }
    
    // ============================================================
    // الدوال الخاصة
    // ============================================================
    
    /**
     * الملخص العام
     */
    private function getSummary(string $dateFrom, string $dateTo): array
    {
        $income = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total 
             FROM payments 
             WHERE payment_type IN ('rent', 'penalty')
             AND DATE(paid_at) BETWEEN :from AND :to",
            ['from' => $dateFrom, 'to' => $dateTo]
        )['total'] ?? 0);
        
        $expenses = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total 
             FROM car_expenses 
             WHERE expense_date BETWEEN :from AND :to",
            ['from' => $dateFrom, 'to' => $dateTo]
        )['total'] ?? 0);
        
        $rentals = (int) ($this->db->fetchOne(
            "SELECT COUNT(*) as c FROM rentals 
             WHERE DATE(created_at) BETWEEN :from AND :to",
            ['from' => $dateFrom, 'to' => $dateTo]
        )['c'] ?? 0);
        
        $profit = $income - $expenses;
        $margin = $income > 0 ? round(($profit / $income) * 100, 2) : 0;
        
        return [
            'income'   => $income,
            'expenses' => $expenses,
            'profit'   => $profit,
            'rentals'  => $rentals,
            'margin'   => $margin,
        ];
    }
    
    /**
     * ✅ الأرباح حسب السيارة (مع profit و margin)
     */
    private function getCarsProfit(string $dateFrom, string $dateTo): array
    {
        $cars = $this->db->fetchAll(
            "SELECT c.id, c.plate_number, c.brand, c.model, c.year, c.purchase_price,
                    COALESCE((SELECT SUM(p.amount)
                     FROM payments p
                     JOIN rentals r ON r.id = p.rental_id
                     WHERE r.car_id = c.id
                     AND p.payment_type IN ('rent', 'penalty')
                     AND DATE(p.paid_at) BETWEEN :from1 AND :to1), 0) AS income,
                    COALESCE((SELECT SUM(e.amount)
                     FROM car_expenses e
                     WHERE e.car_id = c.id
                     AND e.expense_date BETWEEN :from2 AND :to2), 0) AS expenses
             FROM cars c
             ORDER BY income DESC",
            [
                'from1' => $dateFrom, 'to1' => $dateTo,
                'from2' => $dateFrom, 'to2' => $dateTo,
            ]
        );
        
        // ✅ حساب profit و margin
        foreach ($cars as &$car) {
            $income = (float) ($car['income'] ?? 0);
            $expenses = (float) ($car['expenses'] ?? 0);
            
            $car['profit'] = $income - $expenses;
            $car['margin'] = $income > 0 
                ? round(($car['profit'] / $income) * 100, 2) 
                : 0;
        }
        unset($car);
        
        return $cars;
    }
    
    /**
     * المصروفات حسب التصنيف
     */
    private function getExpensesByCategory(string $dateFrom, string $dateTo): array
    {
        return $this->db->fetchAll(
            "SELECT ec.id, ec.name_ar, ec.name_en,
                    COUNT(e.id) as count,
                    COALESCE(SUM(e.amount), 0) as total
             FROM expense_categories ec
             LEFT JOIN car_expenses e ON e.category_id = ec.id
                 AND e.expense_date BETWEEN :from AND :to
             GROUP BY ec.id
             HAVING total > 0
             ORDER BY total DESC",
            ['from' => $dateFrom, 'to' => $dateTo]
        );
    }
    
    /**
     * المصروفات حسب السيارة
     */
    private function getExpensesByCar(string $dateFrom, string $dateTo): array
    {
        return $this->db->fetchAll(
            "SELECT c.id, c.plate_number, c.brand, c.model,
                    COUNT(e.id) as count,
                    COALESCE(SUM(e.amount), 0) as total
             FROM cars c
             LEFT JOIN car_expenses e ON e.car_id = c.id
                 AND e.expense_date BETWEEN :from AND :to
             GROUP BY c.id
             HAVING total > 0
             ORDER BY total DESC",
            ['from' => $dateFrom, 'to' => $dateTo]
        );
    }
    
    /**
     * المدفوعات حسب الطريقة
     */
    private function getPaymentsByMethod(string $dateFrom, string $dateTo): array
    {
        return $this->db->fetchAll(
            "SELECT method,
                    COUNT(*) as count,
                    COALESCE(SUM(amount), 0) as total
             FROM payments
             WHERE DATE(paid_at) BETWEEN :from AND :to
             AND payment_type IN ('rent', 'penalty')
             GROUP BY method",
            ['from' => $dateFrom, 'to' => $dateTo]
        );
    }
    
    /**
     * المدفوعات حسب اليوم
     */
    private function getPaymentsByDay(string $dateFrom, string $dateTo): array
    {
        return $this->db->fetchAll(
            "SELECT DATE(paid_at) as date,
                    COUNT(*) as count,
                    COALESCE(SUM(amount), 0) as total,
                    SUM(CASE WHEN method = 'cash' THEN amount ELSE 0 END) as cash_total,
                    SUM(CASE WHEN method = 'network' THEN amount ELSE 0 END) as network_total
             FROM payments
             WHERE DATE(paid_at) BETWEEN :from AND :to
             AND payment_type IN ('rent', 'penalty')
             GROUP BY DATE(paid_at)
             ORDER BY date DESC",
            ['from' => $dateFrom, 'to' => $dateTo]
        );
    }
    
    /**
     * البيانات الشهرية (آخر N شهر)
     */
    private function getMonthlyData(int $months = 12): array
    {
        $data = [
            'months'   => [],
            'income'   => [],
            'expenses' => [],
            'profit'   => [],
        ];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $data['months'][] = $month;
            
            $income = (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM payments 
                 WHERE DATE_FORMAT(paid_at, '%Y-%m') = :m
                 AND payment_type IN ('rent', 'penalty')",
                ['m' => $month]
            )['total'] ?? 0);
            
            $expenses = (float) ($this->db->fetchOne(
                "SELECT COALESCE(SUM(amount), 0) as total 
                 FROM car_expenses 
                 WHERE DATE_FORMAT(expense_date, '%Y-%m') = :m",
                ['m' => $month]
            )['total'] ?? 0);
            
            $data['income'][] = $income;
            $data['expenses'][] = $expenses;
            $data['profit'][] = $income - $expenses;
        }
        
        return $data;
    }
    
    /**
     * ✅ تقرير السيارات (مع profit)
     */
    private function getCarsReport(string $dateFrom, string $dateTo): array
    {
        $cars = $this->db->fetchAll(
            "SELECT c.*,
                    ct.name_ar AS type_name_ar, ct.name_en AS type_name_en,
                    (SELECT COUNT(*) FROM rentals r WHERE r.car_id = c.id) as total_rentals,
                    (SELECT COUNT(*) FROM rentals r WHERE r.car_id = c.id AND r.status = 'active') as active_rentals,
                    COALESCE((SELECT SUM(p.amount)
                     FROM payments p
                     JOIN rentals r ON r.id = p.rental_id
                     WHERE r.car_id = c.id
                     AND p.payment_type IN ('rent', 'penalty')
                     AND DATE(p.paid_at) BETWEEN :from1 AND :to1), 0) AS income,
                    COALESCE((SELECT SUM(e.amount)
                     FROM car_expenses e
                     WHERE e.car_id = c.id
                     AND e.expense_date BETWEEN :from2 AND :to2), 0) AS expenses,
                    COALESCE((SELECT SUM(r.rental_days)
                     FROM rentals r
                     WHERE r.car_id = c.id
                     AND r.status IN ('active', 'completed')
                     AND r.pickup_datetime BETWEEN :from3 AND :to3), 0) AS rental_days
             FROM cars c
             LEFT JOIN car_types ct ON ct.id = c.car_type_id
             ORDER BY c.plate_number",
            [
                'from1' => $dateFrom, 'to1' => $dateTo,
                'from2' => $dateFrom, 'to2' => $dateTo,
                'from3' => $dateFrom, 'to3' => $dateTo,
            ]
        );
        
        // ✅ حساب profit و margin
        foreach ($cars as &$car) {
            $income = (float) ($car['income'] ?? 0);
            $expenses = (float) ($car['expenses'] ?? 0);
            
            $car['profit'] = $income - $expenses;
            $car['margin'] = $income > 0 
                ? round(($car['profit'] / $income) * 100, 2) 
                : 0;
        }
        unset($car);
        
        return $cars;
    }
}