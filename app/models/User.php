<?php
/**
 * نموذج المستخدم
 */

class User
{
    private Database $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * جلب كل المستخدمين
     */
    public function all(array $filters = []): array
    {
        $where = [];
        $params = [];
        
        if (!empty($filters['search'])) {
            $where[] = "(full_name LIKE :search_name 
                        OR username LIKE :search_user 
                        OR phone LIKE :search_phone 
                        OR email LIKE :search_email)";
            $sv = '%' . $filters['search'] . '%';
            $params['search_name']  = $sv;
            $params['search_user']  = $sv;
            $params['search_phone'] = $sv;
            $params['search_email'] = $sv;
        }
        
        if (!empty($filters['role'])) {
            $where[] = "role = :role";
            $params['role'] = $filters['role'];
        }
        
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = "is_active = :is_active";
            $params['is_active'] = (int) $filters['is_active'];
        }
        
        $whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        return $this->db->fetchAll(
            "SELECT id, full_name, username, role, phone, email, 
                    is_active, last_login, created_at
             FROM users
             {$whereSql}
             ORDER BY 
                CASE role 
                    WHEN 'admin' THEN 1 
                    WHEN 'accountant' THEN 2 
                    WHEN 'employee' THEN 3 
                END,
                full_name ASC",
            $params
        );
    }
    
    /**
     * جلب مستخدم بالمعرف
     */
    public function find(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT id, full_name, username, role, phone, email, 
                    is_active, last_login, created_at, updated_at
             FROM users WHERE id = :id LIMIT 1",
            ['id' => $id]
        );
    }
    
    /**
     * البحث باسم المستخدم
     */
    public function findByUsername(string $username): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM users WHERE username = :username LIMIT 1",
            ['username' => $username]
        );
    }
    
    /**
     * إنشاء مستخدم جديد
     */
    public function create(array $data): int
    {
        return $this->db->insert(
            "INSERT INTO users 
                (full_name, username, password_hash, role, phone, email, is_active)
             VALUES 
                (:full_name, :username, :password_hash, :role, :phone, :email, :is_active)",
            [
                'full_name'     => $data['full_name'],
                'username'      => $data['username'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role'          => $data['role'],
                'phone'         => $data['phone'] ?: null,
                'email'         => $data['email'] ?: null,
                'is_active'     => isset($data['is_active']) ? 1 : 0,
            ]
        );
    }
    
    /**
     * تحديث مستخدم
     */
    public function update(int $id, array $data): int
    {
        $params = [
            'id'        => $id,
            'full_name' => $data['full_name'],
            'username'  => $data['username'],
            'role'      => $data['role'],
            'phone'     => $data['phone'] ?: null,
            'email'     => $data['email'] ?: null,
            'is_active' => isset($data['is_active']) ? 1 : 0,
        ];
        
        $sql = "UPDATE users SET
                full_name = :full_name,
                username = :username,
                role = :role,
                phone = :phone,
                email = :email,
                is_active = :is_active";
        
        // تحديث كلمة المرور (اختياري)
        if (!empty($data['password'])) {
            $sql .= ", password_hash = :password_hash";
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $sql .= " WHERE id = :id";
        
        return $this->db->execute($sql, $params);
    }
    
    /**
     * تحديث كلمة المرور
     */
    public function updatePassword(int $id, string $newHash): int
    {
        return $this->db->execute(
            "UPDATE users SET password_hash = :hash WHERE id = :id",
            ['hash' => $newHash, 'id' => $id]
        );
    }
    
    /**
     * حذف مستخدم
     */
    public function delete(int $id): bool
    {
        // منع حذف آخر مدير
        $adminCount = (int) ($this->db->fetchOne(
            "SELECT COUNT(*) as c FROM users WHERE role = 'admin' AND is_active = 1"
        )['c'] ?? 0);
        
        $user = $this->find($id);
        
        if ($user && $user['role'] === 'admin' && $adminCount <= 1) {
            return false; // لا يمكن حذف آخر مدير
        }
        
        $this->db->execute("DELETE FROM users WHERE id = :id", ['id' => $id]);
        return true;
    }
    
    /**
     * تفعيل/تعطيل مستخدم
     */
    public function toggleActive(int $id): bool
    {
        $user = $this->find($id);
        
        if (!$user) return false;
        
        // منع تعطيل آخر مدير نشط
        if ($user['role'] === 'admin' && $user['is_active']) {
            $activeAdmins = (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM users WHERE role = 'admin' AND is_active = 1"
            )['c'] ?? 0);
            
            if ($activeAdmins <= 1) {
                return false;
            }
        }
        
        $this->db->execute(
            "UPDATE users SET is_active = NOT is_active WHERE id = :id",
            ['id' => $id]
        );
        
        return true;
    }
    
    /**
     * إعادة تعيين كلمة المرور
     */
    public function resetPassword(int $id, string $newPassword): bool
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $this->db->execute(
            "UPDATE users SET password_hash = :hash, 
                              failed_login_attempts = 0,
                              locked_until = NULL
             WHERE id = :id",
            ['hash' => $hash, 'id' => $id]
        );
        
        return true;
    }
    
    /**
     * تغيير كلمة المرور (المستخدم نفسه)
     */
    public function changeOwnPassword(int $id, string $currentPassword, string $newPassword): array
    {
        $user = $this->db->fetchOne(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $id]
        );
        
        if (!$user) {
            return ['success' => false, 'error' => 'المستخدم غير موجود'];
        }
        
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'error' => 'كلمة المرور الحالية غير صحيحة'];
        }
        
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'error' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل'];
        }
        
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $this->updatePassword($id, $hash);
        
        return ['success' => true];
    }
    
    /**
     * هل اسم المستخدم مستخدم؟
     */
    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as c FROM users WHERE username = :username";
        $params = ['username' => $username];
        
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        return (int) ($this->db->fetchOne($sql, $params)['c'] ?? 0) > 0;
    }
    
    /**
     * تحديث آخر تسجيل دخول
     */
    public function updateLastLogin(int $id): void
    {
        $this->db->execute(
            "UPDATE users SET last_login = NOW(), 
                              failed_login_attempts = 0, 
                              locked_until = NULL 
             WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * زيادة محاولات الدخول الفاشلة
     */
    public function incrementFailedAttempts(int $id, int $maxAttempts, int $lockoutDuration): void
    {
        $this->db->execute(
            "UPDATE users 
             SET failed_login_attempts = failed_login_attempts + 1,
                 locked_until = IF(failed_login_attempts + 1 >= :max, 
                                   DATE_ADD(NOW(), INTERVAL :duration SECOND), 
                                   locked_until)
             WHERE id = :id",
            [
                'id'       => $id,
                'max'      => $maxAttempts,
                'duration' => $lockoutDuration,
            ]
        );
    }
    
    /**
     * إعادة تعيين محاولات الدخول
     */
    public function resetFailedAttempts(int $id): void
    {
        $this->db->execute(
            "UPDATE users SET failed_login_attempts = 0, locked_until = NULL WHERE id = :id",
            ['id' => $id]
        );
    }
    
    /**
     * حفظ تفضيلات المستخدم
     */
    public function updatePreferences(int $id, string $language, string $theme): void
    {
        $this->db->execute(
            "UPDATE users SET preferred_language = :lang, preferred_theme = :theme WHERE id = :id",
            ['id' => $id, 'lang' => $language, 'theme' => $theme]
        );
    }
    
    /**
     * إحصائيات المستخدمين
     */
    public function getStats(): array
    {
        return [
            'total'      => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM users")['c'] ?? 0),
            'admins'     => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'admin'")['c'] ?? 0),
            'accountants'=> (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'accountant'")['c'] ?? 0),
            'employees'  => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'employee'")['c'] ?? 0),
            'active'     => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_active = 1")['c'] ?? 0),
            'inactive'   => (int) ($this->db->fetchOne("SELECT COUNT(*) as c FROM users WHERE is_active = 0")['c'] ?? 0),
            'online_now' => (int) ($this->db->fetchOne(
                "SELECT COUNT(*) as c FROM users 
                 WHERE last_login >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)"
            )['c'] ?? 0),
        ];
    }
    
    /**
     * جلب الأدوار المتاحة
     */
    public function getRoles(): array
    {
        return [
            'admin'      => 'مدير',
            'accountant' => 'محاسب',
            'employee'   => 'موظف',
        ];
    }
    
    /**
     * جلب صلاحيات الدور
     */
    public function getRolePermissions(string $role): array
    {
        $permissions = [
            'admin' => [
                'إدارة المستخدمين والصلاحيات',
                'إضافة / تعديل / حذف السيارات',
                'إضافة / تعديل / حذف المستأجرين',
                'إنشاء وتعديل وإلغاء العقود',
                'تسجيل وتعديل المدفوعات',
                'تسجيل المصروفات',
                'الاطلاع على جميع التقارير',
                'الوصول للإعدادات والنسخ الاحتياطي',
                'حذف السجلات',
            ],
            'accountant' => [
                'عرض السيارات',
                'عرض المستأجرين',
                'عرض العقود',
                'تسجيل وتعديل المدفوعات',
                'تسجيل المصروفات',
                'الاطلاع على التقارير المالية',
                'عرض لوحة التحكم',
            ],
            'employee' => [
                'عرض السيارات',
                'إضافة / تعديل السيارات',
                'عرض وإضافة وتعديل المستأجرين',
                'عرض وإنشاء وتعديل العقود',
                'تسجيل المدفوعات',
                'عرض لوحة التحكم',
            ],
        ];
        
        return $permissions[$role] ?? [];
    }
    
    /**
     * جلب آخر عمليات المستخدم
     */
    public function getRecentActivity(int $userId, int $limit = 20): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM activity_log 
             WHERE user_id = :id
             ORDER BY created_at DESC
             LIMIT {$limit}",
            ['id' => $userId]
        );
    }
}