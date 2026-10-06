<?php
/**
 * نموذج المصروف
 */

class Expense
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * جلب كل المصروفات مع الفلترة
     */
    public function all(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where = [];
        $params = [];
        
        // البحث
        if (!empty($filters['search'])) {
            $where[] = "(e.description LIKE :search_desc 
                        OR e.vendor LIKE :search_vendor 
                        OR e.invoice_no LIKE :search_invoice 
                        OR c.plate_number LIKE :search_plate)";
            $sv = '%' . $filters['search'] . '%';
            $params['search_desc']    = $sv;
            $params['search_vendor']  = $sv;
            $params['search_invoice'] = $sv;
            $params['search_plate']   = $sv;
        }
        
        // السيارة
        if (!empty($filters['car_id'])) {
            $where[] = "e.car_id = :car_id";
            $params['car_id'] = (int) $filters['car_id'];
        }
        
        // التصنيف
        if (!empty($filters['category_id'])) {
            $where[] = "e.category_id = :category_id";
            $params['category_id'] = (int) $filters['category_id'];
        }
        
        // من تاريخ
        if (!empty($filters['date_from'])) {
            $where[] = "e.expense_date >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        
        // إلى تاريخ
        if (!empty($filters['date_to'])) {
            $where[] = "e.expense_date <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // العدد الإجمالي
        $total = (int) ($this->db->fetchOne(
            "SELECT COUNT(*) as c FROM car_expenses e
             LEFT JOIN cars c ON c.id = e.car_id
             {$whereSql}",
            $params
        )['c'] ?? 0);
        
        // المجموع
        $totalAmount = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(e.amount), 0) as total FROM car_expenses e
             LEFT JOIN cars c ON c.id = e.car_id
             {$whereSql}",
            $params
        )['total'] ?? 0);
        
        // Pagination
        $offset = ($page - 1) * $perPage;
        
        // البيانات
        $expenses = $this->db->fetchAll(
            "SELECT e.*,
                    c.plate_number, c.brand, c.model,
                    ec.name_ar AS category_name_ar, ec.name_en AS category_name_en,
                    u.full_name AS created_by_name
             FROM car_expenses e
             LEFT JOIN cars c ON c.id = e.car_id
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             LEFT JOIN users u ON u.id = e.created_by
             {$whereSql}
             ORDER BY e.expense_date DESC, e.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        
        return [
            'items'         => $expenses,
            'total'         => $total,
            'total_amount'  => $totalAmount,
            'page'          => $page,
            'per_page'      => $perPage,
            'total_pages'   => (int) ceil($total / $perPage),
        ];
    }
    
    /**
     * جلب مصروف بالمعرف
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT e.*,
                    c.plate_number, c.brand, c.model, c.year,
                    ec.name_ar AS category_name_ar, ec.name_en AS category_name_en,
                    u.full_name AS created_by_name
             FROM car_expenses e
             LEFT JOIN cars c ON c.id = e.car_id
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.id = :id
             LIMIT 1",
            ['id' => $id]
        );
    }
    
    /**
     * إضافة مصروف
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO car_expenses 
                (car_id, category_id, description, amount, expense_date,
                 vendor, invoice_no, invoice_image, odometer_at,
                 next_service_date, next_service_km, created_by)
                VALUES 
                (:car_id, :category_id, :description, :amount, :expense_date,
                 :vendor, :invoice_no, :invoice_image, :odometer_at,
                 :next_service_date, :next_service_km, :created_by)";
        
        return $this->db->insert($sql, $data);
    }
    
    /**
     * تحديث مصروف
     */
    public function update(int $id, array $data): int
    {
        $data['id'] = $id;
        
        $sql = "UPDATE car_expenses SET
                car_id = :car_id,
                category_id = :category_id,
                description = :description,
                amount = :amount,
                expense_date = :expense_date,
                vendor = :vendor,
                invoice_no = :invoice_no,
                odometer_at = :odometer_at,
                next_service_date = :next_service_date,
                next_service_km = :next_service_km
                WHERE id = :id";
        
        return $this->db->execute($sql, $data);
    }
    
    /**
     * حذف مصروف
     */
    public function delete(int $id): int
    {
        return $this->db->execute(
            "DELETE FROM car_expenses WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * جلب التصنيفات
     */
    public function getCategories(): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM expense_categories WHERE is_active = 1 ORDER BY name_ar"
        );
    }
    
    /**
     * جلب السيارات
     */
    public function getCars(): array
    {
        return $this->db->fetchAll(
            "SELECT id, plate_number, brand, model, year 
             FROM cars 
             ORDER BY plate_number"
        );
    }
    
    /**
     * جلب مصروفات سيارة معينة
     */
    public function getByCar(int $carId, int $limit = 10): array
    {
        return $this->db->fetchAll(
            "SELECT e.*, ec.name_ar AS category_name_ar, ec.name_en AS category_name_en
             FROM car_expenses e
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             WHERE e.car_id = :car_id
             ORDER BY e.expense_date DESC
             LIMIT {$limit}",
            ['car_id' => $carId]
        );
    }
    
    /**
     * إحصائيات المصروفات
     */
    public function getStats(): array
    {
        $total = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM car_expenses"
        )['total'] ?? 0);
        
        $thisMonth = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM car_expenses
             WHERE MONTH(expense_date) = MONTH(CURDATE())
             AND YEAR(expense_date) = YEAR(CURDATE())"
        )['total'] ?? 0);
        
        $today = (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total FROM car_expenses
             WHERE DATE(expense_date) = CURDATE()"
        )['total'] ?? 0);
        
        $count = (int) ($this->db->fetchOne(
            "SELECT COUNT(*) as c FROM car_expenses"
        )['c'] ?? 0);
        
        // حسب التصنيف
        $byCategory = $this->db->fetchAll(
            "SELECT ec.name_ar, ec.name_en,
                    COUNT(e.id) as count,
                    COALESCE(SUM(e.amount), 0) as total
             FROM expense_categories ec
             LEFT JOIN car_expenses e ON e.category_id = ec.id
             GROUP BY ec.id
             ORDER BY total DESC"
        );
        
        return [
            'total'       => $total,
            'this_month'  => $thisMonth,
            'today'       => $today,
            'count'       => $count,
            'by_category' => $byCategory,
        ];
    }
    
    /**
     * مصروفات سيارة معينة (إجمالي)
     */
    public function getCarTotal(int $carId): float
    {
        return (float) ($this->db->fetchOne(
            "SELECT COALESCE(SUM(amount), 0) as total 
             FROM car_expenses 
             WHERE car_id = :id",
            ['id' => $carId]
        )['total'] ?? 0);
    }
    
    /**
     * أحدث المصروفات
     */
    public function getRecent(int $limit = 5): array
    {
        return $this->db->fetchAll(
            "SELECT e.*, c.plate_number, c.brand, c.model,
                    ec.name_ar AS category_name_ar
             FROM car_expenses e
             LEFT JOIN cars c ON c.id = e.car_id
             LEFT JOIN expense_categories ec ON ec.id = e.category_id
             ORDER BY e.created_at DESC
             LIMIT {$limit}"
        );
    }
}