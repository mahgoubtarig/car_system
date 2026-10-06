<?php
/**
 * نموذج المستأجر
 */

class Customer
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * جلب كل المستأجرين مع الفلترة والبحث
     */
    public function all(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $where = [];
        $params = [];
        
        // البحث (معاملات متعددة)
        if (!empty($filters['search'])) {
            $where[] = "(cu.full_name LIKE :search_name 
                        OR cu.phone LIKE :search_phone 
                        OR cu.id_number LIKE :search_id 
                        OR cu.email LIKE :search_email)";
            
            $searchValue = '%' . $filters['search'] . '%';
            $params['search_name']  = $searchValue;
            $params['search_phone'] = $searchValue;
            $params['search_id']    = $searchValue;
            $params['search_email'] = $searchValue;
        }
        
        // المدينة
        if (!empty($filters['city'])) {
            $where[] = "cu.city = :city";
            $params['city'] = $filters['city'];
        }
        
        // نوع الإثبات
        if (!empty($filters['id_type'])) {
            $where[] = "cu.id_type = :id_type";
            $params['id_type'] = $filters['id_type'];
        }
        
        // القائمة السوداء
        if (isset($filters['is_blacklisted']) && $filters['is_blacklisted'] !== '') {
            $where[] = "cu.is_blacklisted = :is_blacklisted";
            $params['is_blacklisted'] = (int) $filters['is_blacklisted'];
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // العدد الإجمالي
        $total = (int) ($this->db->fetchOne(
            "SELECT COUNT(*) as c FROM customers cu {$whereSql}",
            $params
        )['c'] ?? 0);
        
        // Pagination
        $offset = ($page - 1) * $perPage;
        
        // جلب البيانات
        $customers = $this->db->fetchAll(
            "SELECT cu.*,
                    (SELECT COUNT(*) FROM rentals r WHERE r.customer_id = cu.id) AS total_rentals,
                    (SELECT COUNT(*) FROM rentals r WHERE r.customer_id = cu.id AND r.status = 'active') AS active_rentals
             FROM customers cu
             {$whereSql}
             ORDER BY cu.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $params
        );
        
        return [
            'items'      => $customers,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'total_pages'=> (int) ceil($total / $perPage),
        ];
    }
    
    /**
     * جلب مستأجر بالمعرف
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM customers WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
    }
    
    /**
     * البحث برقم الهوية
     */
    public function findByIdNumber(string $idNumber, ?int $excludeId = null): ?array
    {
        $sql = "SELECT * FROM customers WHERE id_number = :id_number";
        $params = ['id_number' => $idNumber];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $sql .= " LIMIT 1";
        
        return $this->db->fetchOne($sql, $params);
    }
    
    /**
     * إضافة مستأجر
     */
    public function create(array $data): int
    {
        $sql = "INSERT INTO customers 
                (full_name, nationality, id_type, id_number, id_expiry, birth_date,
                 phone, phone2, email, city, address,
                 license_number, license_expiry, sponsor_or_employer,
                 emergency_contact, id_image, license_image,
                 is_blacklisted, blacklist_reason, notes)
                VALUES 
                (:full_name, :nationality, :id_type, :id_number, :id_expiry, :birth_date,
                 :phone, :phone2, :email, :city, :address,
                 :license_number, :license_expiry, :sponsor_or_employer,
                 :emergency_contact, :id_image, :license_image,
                 :is_blacklisted, :blacklist_reason, :notes)";
        
        return $this->db->insert($sql, $data);
    }
    
    /**
     * تحديث مستأجر
     */
    public function update(int $id, array $data): int
    {
        $data['id'] = $id;
        
        $sql = "UPDATE customers SET
                full_name = :full_name,
                nationality = :nationality,
                id_type = :id_type,
                id_number = :id_number,
                id_expiry = :id_expiry,
                birth_date = :birth_date,
                phone = :phone,
                phone2 = :phone2,
                email = :email,
                city = :city,
                address = :address,
                license_number = :license_number,
                license_expiry = :license_expiry,
                sponsor_or_employer = :sponsor_or_employer,
                emergency_contact = :emergency_contact,
                is_blacklisted = :is_blacklisted,
                blacklist_reason = :blacklist_reason,
                notes = :notes
                WHERE id = :id";
        
        return $this->db->execute($sql, $data);
    }
    
    /**
     * حذف مستأجر
     */
    public function delete(int $id): bool
    {
        // التحقق من عدم وجود عقود
        $hasRentals = (int) ($this->db->fetchOne(
            "SELECT COUNT(*) as c FROM rentals WHERE customer_id = :id",
            ['id' => $id]
        )['c'] ?? 0);
        
        if ($hasRentals > 0) {
            return false;
        }
        
        $this->db->execute("DELETE FROM customers WHERE id = :id", ['id' => $id]);
        return true;
    }
    
    /**
     * التحقق من وجود رقم الهوية
     */
    public function idNumberExists(string $idNumber, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as c FROM customers WHERE id_number = :id_number";
        $params = ['id_number' => $idNumber];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        return (int) ($this->db->fetchOne($sql, $params)['c'] ?? 0) > 0;
    }
    
    /**
     * تغيير حالة القائمة السوداء
     */
    public function setBlacklist(int $id, bool $blacklisted, ?string $reason = null): int
    {
        return $this->db->execute(
            "UPDATE customers SET is_blacklisted = :bl, blacklist_reason = :reason WHERE id = :id",
            [
                'bl'     => $blacklisted ? 1 : 0,
                'reason' => $blacklisted ? $reason : null,
                'id'     => $id,
            ]
        );
    }
    
    /**
     * جلب إحصائيات المستأجرين
     */
    public function getStats(): array
    {
        return [
            'total'          => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM customers")['c'] ?? 0),
            'blacklisted'    => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM customers WHERE is_blacklisted = 1")['c'] ?? 0),
            'with_active'    => (int) ($this->db->fetchOne(
                "SELECT COUNT(DISTINCT customer_id) as c FROM rentals WHERE status = 'active'"
            )['c'] ?? 0),
            'expired_ids'    => (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM customers WHERE id_expiry IS NOT NULL AND id_expiry < CURDATE()"
            )['c'] ?? 0),
        ];
    }
    
    /**
     * جلب المدن المتاحة
     */
    public function getCities(): array
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT city FROM customers WHERE city IS NOT NULL AND city != '' ORDER BY city"
        );
    }
}