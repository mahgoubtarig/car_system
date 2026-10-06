<?php
/**
 * كلاس المصادقة والجلسات
 */

class Auth
{
    private User $userModel;
    private array $config;
    
    public function __construct()
    {
        $this->userModel = new User();
        $this->config = require BASE_PATH . '/app/config/config.php';
    }
    
    /**
     * بدء الجلسة بشكل آمن
     * ✅ تم التعديل لتتوافق الجلسات بشكل مثالي مع بيئة localhost
     */
    public static function startSession(): void
    {
        $config = require BASE_PATH . '/app/config/config.php';
        $sessionName = $config['session_name'] ?? 'CAR_RENTAL_SESSION';
        
        // إذا كانت الجلسة نشطة بالفعل، تحقق من الاسم
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (session_name() !== $sessionName) {
                // الاسم مختلف - أغلق وأعد البدء
                session_write_close();
            } else {
                return;
            }
        }
        
        // 🔴 تم إيقاف المسار المخصص مؤقتاً لتجنب مشاكل صلاحيات المجلدات في XAMPP
        // والتي تمنع حفظ الجلسة وتسبب حلقة إعادة التوجيه
        /*
        $sessionPath = STORAGE_PATH . '/sessions';
        if (!is_dir($sessionPath)) {
            @mkdir($sessionPath, 0755, true);
        }
        
        if (is_writable($sessionPath)) {
            ini_set('session.save_path', $sessionPath);
        }
        */
        
        // إعدادات الجلسة الأساسية
        ini_set('session.use_cookies', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.gc_maxlifetime', (string)($config['session_lifetime'] ?? 1800));
        
        // ✅ التعديل الأهم: إعدادات الكوكي بمرونة لتعمل على السيرفر المحلي والحي
        $isSecure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $isLocalhost = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || ($_SERVER['SERVER_NAME'] ?? '') === 'localhost';
        
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            // لا تفرض secure أبدًا على localhost لتجنب حظر المتصفح للكوكي
            'secure'   => $isLocalhost ? false : $isSecure, 
            'httponly' => true,
            'samesite' => $isLocalhost ? 'Lax' : 'Strict',
        ]);
        
        // بدء الجلسة
        if (session_status() === PHP_SESSION_NONE) {
            session_name($sessionName);
            session_start();
        }
        
        // 🐛 تسجيل للتشخيص
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('=== SESSION START ===');
            error_log('Session Name: ' . session_name());
            error_log('Session ID:   ' . session_id());
            error_log('Save Path:    ' . session_save_path());
            error_log('Logged In:    ' . (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'YES' : 'NO'));
            error_log('User ID:      ' . ($_SESSION['user_id'] ?? 'N/A'));
        }
        
        // تجديد معرف الجلسة دورياً (كل 30 دقيقة)
        if (!isset($_SESSION['_last_regenerate'])) {
            $_SESSION['_last_regenerate'] = time();
        } elseif (time() - $_SESSION['_last_regenerate'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['_last_regenerate'] = time();
        }
        
        // التحقق من انتهاء الجلسة
        self::checkSessionTimeout($config['session_lifetime'] ?? 1800);
    }
    
    /**
     * التحقق من انتهاء الجلسة
     */
    private static function checkSessionTimeout(int $lifetime): void
    {
        if (!isset($_SESSION['_last_activity'])) {
            $_SESSION['_last_activity'] = time();
            return;
        }
        
        if (time() - $_SESSION['_last_activity'] > $lifetime) {
            self::logout();
            $_SESSION['_flash']['error'] = 'error_session_expired';
            return;
        }
        
        $_SESSION['_last_activity'] = time();
    }
    
    /**
     * محاولة تسجيل الدخول
     */
    public function attempt(string $username, string $password, bool $remember = false): array
    {
        // جلب المستخدم
        $user = $this->userModel->findByUsername($username);
        
        if (!$user) {
            usleep(random_int(200000, 500000));
            return ['success' => false, 'error' => 'error_invalid_credentials'];
        }
        
        // التحقق من قفل الحساب
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $minutes = ceil((strtotime($user['locked_until']) - time()) / 60);
            return [
                'success' => false,
                'error'   => 'error_account_locked',
                'minutes' => $minutes,
            ];
        }
        
        // التحقق من تفعيل الحساب
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'error_account_inactive'];
        }
        
        // التحقق من كلمة المرور
        if (!password_verify($password, $user['password_hash'])) {
            $this->userModel->incrementFailedAttempts(
                (int) $user['id'],
                $this->config['max_login_attempts'],
                $this->config['lockout_duration']
            );
            
            $user = $this->userModel->findByUsername($username);
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $minutes = ceil((strtotime($user['locked_until']) - time()) / 60);
                return [
                    'success' => false,
                    'error'   => 'error_account_locked',
                    'minutes' => $minutes,
                ];
            }
            
            return ['success' => false, 'error' => 'error_invalid_credentials'];
        }
        
        // نجح تسجيل الدخول
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $this->userModel->updatePassword((int) $user['id'], $newHash);
        }
        
        // تحديث آخر تسجيل دخول
        $this->userModel->updateLastLogin((int) $user['id']);
        
        // حفظ بيانات المستخدم في الجلسة
        $_SESSION['user_id']          = (int) $user['id'];
        $_SESSION['username']         = $user['username'];
        $_SESSION['full_name']        = $user['full_name'];
        $_SESSION['role']             = $user['role'];
        $_SESSION['logged_in']        = true;
        $_SESSION['logged_in_at']     = time();
        $_SESSION['_last_activity']   = time();
        $_SESSION['_last_regenerate'] = time();
        
        // تطبيق تفضيلات المستخدم
        if (!empty($user['preferred_language'])) {
            Lang::setLocale($user['preferred_language']);
        }
        $_SESSION['theme'] = $user['preferred_theme'] ?? 'light';
        
        // تجديد معرف الجلسة بعد الحفظ
        session_regenerate_id(true);
        
        // 🐛 تسجيل
        if (defined('APP_DEBUG') && APP_DEBUG) {
            error_log('=== LOGIN SUCCESS ===');
            error_log('New Session ID: ' . session_id());
            error_log('User ID: ' . $_SESSION['user_id']);
            error_log('Logged In: ' . ($_SESSION['logged_in'] ? 'YES' : 'NO'));
        }
        
        // "تذكرني"
        if ($remember) {
            $this->createRememberToken((int) $user['id']);
        }
        
        // تسجيل العملية
        $this->logActivity((int) $user['id'], 'login');
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * إنشاء رمز "تذكرني"
     */
    private function createRememberToken(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        
        $db = Database::getInstance();
        $db->execute(
            "UPDATE users SET remember_token = :token WHERE id = :id",
            ['token' => $hash, 'id' => $userId]
        );
        
        setcookie('remember_token', $token, [
            'expires'  => time() + 30 * 24 * 60 * 60,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    
    /**
     * التحقق من تسجيل الدخول
     */
    public static function check(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * الحصول على المستخدم الحالي
     */
    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }
        
        return [
            'id'        => $_SESSION['user_id'] ?? null,
            'username'  => $_SESSION['username'] ?? null,
            'full_name' => $_SESSION['full_name'] ?? null,
            'role'      => $_SESSION['role'] ?? null,
        ];
    }
    
    /**
     * الحصول على معرف المستخدم
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * الحصول على دور المستخدم
     */
    public static function role(): ?string
    {
        return $_SESSION['role'] ?? null;
    }
    
    /**
     * هل المستخدم مدير؟
     */
    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }
    
    /**
     * هل المستخدم محاسب؟
     */
    public static function isAccountant(): bool
    {
        return self::role() === 'accountant';
    }
    
    /**
     * هل لديه صلاحية معينة؟
     */
    public static function can(string $permission): bool
    {
        $role = self::role();
        
        if ($role === 'admin') {
            return true;
        }
        
        $permissions = [
            'accountant' => [
                'view_reports', 'view_payments', 'add_payment', 
                'edit_payment', 'view_customers', 'view_rentals',
            ],
            'employee' => [
                'view_cars', 'add_car', 'edit_car',
                'view_customers', 'add_customer', 'edit_customer',
                'view_rentals', 'add_rental', 'edit_rental',
                'add_inspection', 'add_payment',
            ],
        ];
        
        return in_array($permission, $permissions[$role] ?? [], true);
    }
    
    /**
     * تسجيل الخروج
     */
    public static function logout(): void
    {
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        
        if (isset($_SESSION['user_id'])) {
            self::logActivityStatic((int) $_SESSION['user_id'], 'logout', 'users', []);
        }
        
        $_SESSION = [];
        
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        
        session_destroy();
        
        // بدء جلسة جديدة
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * تسجيل عملية في سجل العمليات
     */
    private function logActivity(int $userId, string $action): void
    {
        self::logActivityStatic($userId, $action, 'users', []);
    }
    
    /**
     * تسجيل عملية (static)
     */
    public static function logActivityStatic(
        int $userId,
        string $action,
        string $tableName = 'users',
        array $details = []
    ): void {
        try {
            $db = Database::getInstance();
            $db->execute(
                "INSERT INTO activity_log (user_id, action, table_name, details, ip_address, user_agent) 
                 VALUES (:user_id, :action, :table, :details, :ip, :ua)",
                [
                    'user_id' => $userId,
                    'action'  => $action,
                    'table'   => $tableName,
                    'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                    'ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
                    'ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
                ]
            );
        } catch (Exception $e) {
            error_log('Failed to log activity: ' . $e->getMessage());
        }
    }
    
    /**
     * الحصول على رسائل Flash
     */
    public static function getFlash(string $key): mixed
    {
        if (!isset($_SESSION['_flash'][$key])) {
            return null;
        }
        
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
        
        if (empty($_SESSION['_flash'])) {
            unset($_SESSION['_flash']);
        }
        
        return $value;
    }
    
    /**
     * تعيين رسالة Flash
     */
    public static function setFlash(string $key, mixed $value): void
    {
        $_SESSION['_flash'][$key] = $value;
    }
    
    /**
     * التحقق من وجود رسالة Flash
     */
    public static function hasFlash(string $key): bool
    {
        return isset($_SESSION['_flash'][$key]);
    }
    
    /**
     * الحصول على كل رسائل Flash
     */
    public static function getAllFlash(): array
    {
        $flash = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flash;
    }
}