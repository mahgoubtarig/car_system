<?php
/**
 * موديل الإعدادات
 */
class Setting
{
    private Database $db;
    private string $keyCol = 'setting_key';
    private string $valCol = 'setting_value';
    
    public function __construct()
    {
        $this->db = Database::getInstance();$this->initTableAndColumns();
    }
    
    /**
     * فحص الجدول والتعرف التلقائي على أسماء الأعمدة
     */
    private function initTableAndColumns(): void
    {
        // 1. التحقق من وجود الجدول
        $tableExists = false;
        try {
            $result =$this->db->fetchAll("SHOW TABLES LIKE 'settings'");
            $tableExists = count($result) > 0;         } catch (Exception$e) {}

        // 2. إذا لم يكن موجوداً، نقوم بإنشائه
        if (!$tableExists) {$sql = "CREATE TABLE `settings` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `setting_key` varchar(100) NOT NULL,
                `setting_value` text DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `setting_key` (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
            try { $this->db->execute($sql); } catch(Exception$e) {}
            return;
        }

        // 3. إذا كان موجوداً، نستخرج أسماء الأعمدة الفعلية
        try {
            $cols =$this->db->fetchAll("SHOW COLUMNS FROM settings");
            $candidates = [];
            foreach ($cols as$col) {
                $f =$col['Field'];
                // نستبعد أعمدة الـ id والتواريخ، لنحصل على مفتاح وقيمة الإعداد
                if (!in_array(strtolower($f), ['id', 'created_at', 'updated_at'])) {
                    $candidates[] =$f;
                }
            }

            // نفترض أن العمود الأول (غير الـ id) هو المفتاح، والثاني هو القيمة
            if (count($candidates) >= 2) {$this->keyCol = $candidates[0];$this->valCol = $candidates[1];             }         } catch (Exception$e) {
            // تجاهل في حالة الفشل
        }
    }
    
    /**
     * جلب جميع الإعدادات
     */
    public function getAll(): array
    {
        $settings = [];
        try {
            $rows =$this->db->fetchAll("SELECT * FROM settings");
            foreach ($rows as$row) {
                if (isset($row[$this->keyCol])) {$settings[$row[$this->keyCol]] = $row[$this->valCol] ?? '';
                }
            }
        } catch (Exception $e) {
            error_log('Error fetching settings: ' . $e->getMessage());
        }
        return $settings;
    }
    
    /**
     * حفظ الإعداد
     */
    public function set(string $key, string$value): void
    {
        $k =$this->keyCol;
        $v =$this->valCol;
        
        try {
            $exists =$this->db->fetchOne("SELECT * FROM settings WHERE `$k` = :key", ['key' => $key]);
            
            if ($exists) {
                // منع التحديث إذا كانت القيمة نفسها لتجنب أخطاء قاعدة البيانات
                if (isset($exists[$v]) && (string)$exists[$v] === $value) {                     return;                 }$this->db->execute("UPDATE settings SET `$v` = :val WHERE `$k` = :key", [
                    'val' => $value,
                    'key' => $key
                ]);
            } else {
                $this->db->execute("INSERT INTO settings (`$k`, `$v`) VALUES (:key, :val)", [
                    'key' => $key,
                    'val' => $value
                ]);
            }
        } catch (Exception $e) {
            error_log("Failed to save setting [{$key}]: " . $e->getMessage());
            throw $e; // نمرر الخطأ للكنترولر ليطبعه
        }
    }
    
    /**
     * جلب شروط العقد بشكل مخصص للطباعة
     */
    public function getContractTerms(): array
    {
        $all =$this->getAll();
        return [
            'contract_terms_ar' => $all['contract_terms_ar'] ?? '',
            'contract_terms_en' => $all['contract_terms_en'] ?? ''
        ];
    }
}