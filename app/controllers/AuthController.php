<?php
/**
 * متحكم المصادقة
 */

class AuthController
{
    private Auth $auth;
    
    public function __construct()
    {
        $this->auth = new Auth();
    }
    
    /**
     * عرض صفحة تسجيل الدخول
     */
    public function showLogin(): void
    {
        if (Auth::check()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        $csrfToken = Csrf::getToken();
        
        // جلب رسالة الخطأ من الجلسة
        $error = Auth::getFlash('error');
        $errorData = Auth::getFlash('error_data');
        
        // تنظيف: تأكد من أن $error نص أو null
        if ($error !== null && !is_string($error)) {
            $error = null;
        }
        
        // تعديل مهم: السماح بالنصوص والأرقام (الدقائق) بالمرور
        if (is_array($errorData)) {
            $errorData = array_filter($errorData, function($item) {
                return is_string($item) || is_numeric($item); // السماح للأرقام
            });
            // إعادة ترتيب المفاتيح لضمان وجود العنصر [0]
            $errorData = array_values($errorData); 
            
            if (empty($errorData)) {
                $errorData = null;
            }
        } elseif ($errorData !== null && !is_string($errorData)) {
            $errorData = null;
        }
        
        require BASE_PATH . '/app/views/auth/login.php';
    }
    
    /**
     * معالجة تسجيل الدخول
     */
    public function login(): void
    {
        // التحقق من طريقة الطلب
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . url('/login'));
            exit;
        }
        
        // التحقق من CSRF
        $csrfToken = $_POST['_csrf_token'] ?? '';
        if (!Csrf::validateToken($csrfToken)) {
            Auth::setFlash('error', 'error_csrf');
            header('Location: ' . url('/login'));
            exit;
        }
        
        // التحقق من المدخلات
        $validator = new Validator($_POST);
        $validator
            ->required('username', 'اسم المستخدم')
            ->maxLength('username', 50)
            ->required('password', 'كلمة المرور')
            ->maxLength('password', 255);
        
        if (!$validator->isValid()) {
            $errors = $validator->getErrors();
            Auth::setFlash('error', 'validation_failed');
            Auth::setFlash('error_data', array_values($errors));
            $_SESSION['_old']['username'] = $_POST['username'] ?? '';
            header('Location: ' . url('/login'));
            exit;
        }
        
        // محاولة تسجيل الدخول
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $remember = isset($_POST['remember']) && $_POST['remember'] === '1';
        
        $result = $this->auth->attempt($username, $password, $remember);
        
        if (!$result['success']) {
            // تأكد من أن error نص
            $errorMsg = $result['error'] ?? 'error_general';
            if (!is_string($errorMsg)) {
                $errorMsg = 'error_general';
            }
            
            Auth::setFlash('error', $errorMsg);
            
            // إذا كان هناك دقائق للقفل
            if (isset($result['minutes']) && is_numeric($result['minutes'])) {
                Auth::setFlash('error_data', [(int)$result['minutes']]);
            }
            
            $_SESSION['_old']['username'] = $username;
            header('Location: ' . url('/login'));
            exit;
        }
        
        // ✅ نجح الدخول
        header('Location: ' . url('/dashboard'));
        exit;
    }
    
    /**
     * تسجيل الخروج
     */
    public function logout(): void
    {
        Auth::logout();
        header('Location: ' . url('/login'));
        exit;
    }
    
    /**
     * تغيير اللغة
     */
    public function changeLanguage(): void
    {
        $locale = $_POST['locale'] ?? $_GET['locale'] ?? 'ar';
        
        if (in_array($locale, ['ar', 'en'], true)) {
            Lang::setLocale($locale);
            
            if (Auth::check()) {
                try {
                    $db = Database::getInstance();
                    $db->execute(
                        "UPDATE users SET preferred_language = :lang WHERE id = :id",
                        ['lang' => $locale, 'id' => Auth::id()]
                    );
                } catch (Exception $e) {
                    error_log('Language save error: ' . $e->getMessage());
                }
            }
        }
        
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'locale' => $locale]);
            exit;
        }
        
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/login');
        header('Location: ' . $referer);
        exit;
    }
    
    /**
     * تغيير الثيم
     */
    public function changeTheme(): void
    {
        $theme = $_POST['theme'] ?? 'light';
        
        if (in_array($theme, ['light', 'dark', 'system'], true)) {
            $_SESSION['theme'] = $theme;
            
            if (Auth::check()) {
                try {
                    $db = Database::getInstance();
                    $db->execute(
                        "UPDATE users SET preferred_theme = :theme WHERE id = :id",
                        ['theme' => $theme, 'id' => Auth::id()]
                    );
                } catch (Exception $e) {
                    error_log('Theme save error: ' . $e->getMessage());
                }
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'theme' => $theme]);
        exit;
    }
}