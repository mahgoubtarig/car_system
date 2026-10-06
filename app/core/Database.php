<?php
/**
 * كلاس قاعدة البيانات (Singleton Pattern)
 * يستخدم PDO مع Prepared Statements للحماية من SQL Injection
 */

class Database
{
    private static ?Database $instance = null;
    private ?PDO $connection = null;
    
    private function __construct()
    {
        $config = require BASE_PATH . '/app/config/database.php';
        
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );
        
        try {
            $this->connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                $config['options']
            );
        } catch (PDOException $e) {
            // لا تعرض تفاصيل الخطأ للمستخدم
            error_log('Database connection failed: ' . $e->getMessage());
            die('حدث خطأ في الاتصال بقاعدة البيانات. يرجى المحاولة لاحقاً.');
        }
    }
    
    // منع الاستنساخ
    private function __clone() {}
    
    // منع إلغاء التسلسل
    public function __wakeup()
    {
        throw new Exception('Cannot unserialize singleton');
    }
    
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection(): PDO
    {
        return $this->connection;
    }
    
    /**
     * تنفيذ استعلام مع معاملات
     */
    public function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * جلب صف واحد
     */
    public function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }
    
    /**
     * جلب كل الصفوف
     */
    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * إدراج وإرجاع معرف السجل
     */
    public function insert(string $sql, array $params = []): int
    {
        $this->query($sql, $params);
        return (int) $this->connection->lastInsertId();
    }
    
    /**
     * تحديث/حذف وإرجاع عدد الصفوف المتأثرة
     */
    public function execute(string $sql, array $params = []): int
    {
        return $this->query($sql, $params)->rowCount();
    }
    
    /**
     * بدء معاملة
     */
    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }
    
    /**
     * تأكيد المعاملة
     */
    public function commit(): bool
    {
        return $this->connection->commit();
    }
    
    /**
     * التراجع عن المعاملة
     */
    public function rollback(): bool
    {
        return $this->connection->rollBack();
    }
}