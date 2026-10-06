<?php
/**
 * ============================================================
 * نقطة الدخول الرئيسية (Front Controller)
 * نظام إدارة مكتب تأجير السيارات
 * ============================================================
 */

declare(strict_types=1);

// ============================================================
// 1. تفعيل عرض الأخطاء (للتطوير)
// ============================================================
define('APP_DEBUG', true);

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// ============================================================
// 2. المسارات الأساسية
// ============================================================
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', __DIR__);
define('APP_START', microtime(true));

// ============================================================
// 3. إنشاء المجلدات الضرورية
// ============================================================
$requiredDirs = [
    STORAGE_PATH,
    STORAGE_PATH . '/logs',
    STORAGE_PATH . '/backups',
    STORAGE_PATH . '/sessions',
    STORAGE_PATH . '/uploads',
    STORAGE_PATH . '/uploads/cars',
    STORAGE_PATH . '/uploads/customers',
    STORAGE_PATH . '/uploads/inspections',
    STORAGE_PATH . '/uploads/expenses',
    STORAGE_PATH . '/uploads/signatures',
    STORAGE_PATH . '/uploads/contracts',
];

foreach ($requiredDirs as $dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// ============================================================
// 4. سجل الأخطاء
// ============================================================
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-errors.log');

// ============================================================
// 5. تحميل الإعدادات
// ============================================================
$configFile = APP_PATH . '/config/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    die('❌ config.php غير موجود');
}
$config = require $configFile;

// ============================================================
// 6. الإعدادات العامة
// ============================================================
date_default_timezone_set($config['app_timezone'] ?? 'Asia/Riyadh');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// ============================================================
// 7. رؤوس الأمان
// ============================================================
if (!headers_sent()) {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// ============================================================
// 8. Autoloader
// ============================================================
spl_autoload_register(function (string $class) {
    $paths = [
        APP_PATH . '/core/'        . $class . '.php',
        APP_PATH . '/models/'      . $class . '.php',
        APP_PATH . '/controllers/' . $class . '.php',
        APP_PATH . '/services/'    . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// ============================================================
// 9. helpers
// ============================================================
require_once APP_PATH . '/core/helpers.php';

// ============================================================
// 10. بدء الجلسة
// ============================================================
Auth::startSession();

// ============================================================
// 11. تهيئة اللغة
// ============================================================
Lang::init();

// ============================================================
// 12. معالجة تغيير اللغة عبر ?lang=
// ============================================================
if (isset($_GET['lang'])) {
    $newLocale = $_GET['lang'];
    if (in_array($newLocale, ['ar', 'en'], true)) {
        Lang::setLocale($newLocale);
        
        $cleanUrl = strtok($_SERVER['REQUEST_URI'], '?');
        header('Location: ' . $cleanUrl);
        exit;
    }
}

// ============================================================
// 13. تحليل المسار
// ============================================================
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$requestUri = strtok($requestUri, '?');

$basePath = parse_url($config['app_url'], PHP_URL_PATH) ?? '';
$basePath = rtrim($basePath, '/');

if ($basePath && strpos($requestUri, $basePath) === 0) {
    $path = substr($requestUri, strlen($basePath));
} else {
    $path = $requestUri;
}

$path = '/' . trim($path, '/');
$path = str_replace('/index.php', '', $path);

$originalPath = $path;

if ($path === '' || $path === '/') {
    if (Auth::check()) {
        $path = '/dashboard';
    } else {
        $path = '/login';
    }
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$GLOBALS['current_path'] = $path;

// ============================================================
// 🛡️ حماية من حلقة إعادة التوجيه
// ============================================================
$now = time();
$redirectKey = '_redirect_tracking';

if (!isset($_SESSION[$redirectKey])) {
    $_SESSION[$redirectKey] = ['count' => 0, 'first_at' => $now];
}

if ($now - $_SESSION[$redirectKey]['first_at'] > 5) {
    $_SESSION[$redirectKey] = ['count' => 0, 'first_at' => $now];
}

if ($_SESSION[$redirectKey]['count'] > 5) {
    error_log('=== REDIRECT LOOP DETECTED ===');
    error_log('Path: ' . $path);
    error_log('Session: ' . print_r($_SESSION, true));
    
    $_SESSION = [];
    
    http_response_code(500);
    
    if (APP_DEBUG) {
        renderRedirectLoopError($path);
    } else {
        echo '<h1>حدث خطأ في النظام</h1>';
        echo '<p>تم اكتشاف حلقة إعادة توجيه. يرجى مسح الكوكيز والمحاولة مرة أخرى.</p>';
    }
    exit;
}

// ============================================================
// 🐛 وضع التصحيح
// ============================================================
if (isset($_GET['__debug'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "===========================================\n";
    echo "  🐛 Debug Mode\n";
    echo "===========================================\n\n";
    
    echo "📌 الطلب:\n";
    echo "  REQUEST_URI:    {$requestUri}\n";
    echo "  Original Path:  {$originalPath}\n";
    echo "  Base Path:      {$basePath}\n";
    echo "  Final Path:     {$path}\n";
    echo "  Method:         {$method}\n";
    echo "  POST Data:      " . (empty($_POST) ? 'فارغ' : 'موجود') . "\n\n";
    
    if (!empty($_POST)) {
        echo "📌 POST Contents:\n";
        foreach ($_POST as $k => $v) {
            $display = ($k === 'password' || $k === 'password_confirm') ? '***' : $v;
            echo "  {$k} = {$display}\n";
        }
        echo "\n";
    }
    
    echo "📌 الجلسة:\n";
    echo "  Session ID:      " . session_id() . "\n";
    echo "  Session Name:    " . session_name() . "\n";
    echo "  Logged In:       " . (Auth::check() ? 'YES' : 'NO') . "\n";
    echo "  User ID:         " . ($_SESSION['user_id'] ?? 'N/A') . "\n";
    echo "  Redirect Count:  " . ($_SESSION[$redirectKey]['count'] ?? 0) . "\n";
    if (Auth::check()) {
        $u = Auth::user();
        echo "  User:            " . ($u['full_name'] ?? 'N/A') . "\n";
        echo "  Role:            " . ($u['role'] ?? 'N/A') . "\n";
    }
    echo "\n";
    
    echo "📌 اللغة:\n";
    echo "  Locale:          " . Lang::current() . "\n";
    echo "  Direction:       " . Lang::direction() . "\n\n";
    
    echo "📌 الكلاسات:\n";
    $requiredClasses = [
        'Database', 'Auth', 'Csrf', 'Validator', 'Lang', 'User', 
        'Notification',
        'AuthController', 'DashboardController', 
        'CarController', 'Car',
        'CustomerController', 'Customer',
        'RentalController', 'Rental',
        'PaymentController',
        'ExpenseController', 'Expense',
        'ReportController',
        'SettingController', 'Setting',
        'UserController',
    ];
    foreach ($requiredClasses as $c) {
        echo "  " . (class_exists($c) ? '✅' : '❌') . " {$c}\n";
    }
    echo "\n";
    
    echo "📌 قاعدة البيانات:\n";
    try {
        $db = Database::getInstance();
        $result = $db->fetchOne("SELECT COUNT(*) as c FROM users");
        echo "  Status:          ✅ متصلة\n";
        echo "  Users Count:     " . $result['c'] . "\n";
        
        $cars = $db->fetchOne("SELECT COUNT(*) as c FROM cars");
        echo "  Cars Count:      " . $cars['c'] . "\n";
        
        $customers = $db->fetchOne("SELECT COUNT(*) as c FROM customers");
        echo "  Customers Count: " . $customers['c'] . "\n";
        
        $rentals = $db->fetchOne("SELECT COUNT(*) AS c FROM rentals");
        echo "  Rentals Count:   " . $rentals['c'] . "\n";
        
        $payments = $db->fetchOne("SELECT COUNT(*) AS c FROM payments");
        echo "  Payments Count:  " . $payments['c'] . "\n";
    } catch (Throwable $e) {
        echo "  Status:          ❌ خطأ: " . $e->getMessage() . "\n";
    }
    echo "\n";
    
    echo "📌 التحقق من مكتبة Dompdf:\n";
    $dompdfPath = BASE_PATH . '/vendor/autoload.php';
    if (file_exists($dompdfPath)) {
        echo "  Composer autoload: ✅ موجود\n";
    } else {
        echo "  Composer autoload: ❌ غير موجود\n";
        echo "  💡 قم بتشغيل: composer require dompdf/dompdf\n";
    }
    if (class_exists('\Dompdf\Dompdf')) {
        echo "  Dompdf Class:     ✅ متاح\n";
    } else {
        echo "  Dompdf Class:     ❌ غير متاح\n";
    }
    echo "\n";
    
    echo "===========================================\n";
    echo "💡 إذا كنت في حلقة إعادة توجيه، افتح:\n";
    echo "   ?__clear_session=1\n";
    echo "===========================================\n";
    
    exit;
}

// ============================================================
// 🧹 مسح الجلسة
// ============================================================
if (isset($_GET['__clear_session'])) {
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
    session_start();
    
    header('Location: ' . url('/login'));
    exit;
}

// ============================================================
// 14. التوجيه
// ============================================================
try {
    
    // ============================================================
    // API Routes
    // ============================================================
    
    if ($path === '/api/language') {
        $controller = new AuthController();
        $controller->changeLanguage();
        exit;
    }
    
    if ($path === '/api/theme') {
        $controller = new AuthController();
        $controller->changeTheme();
        exit;
    }
    
    if ($path === '/api/notifications') {
        if (!Auth::check()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit;
        }
        
        if (class_exists('DashboardController')) {
            $controller = new DashboardController();
            $controller->getNotificationsJson();
        }
        exit;
    }
    
    if ($path === '/api/notifications/read' && $method === 'POST') {
        if (class_exists('DashboardController')) {
            $controller = new DashboardController();
            $controller->markNotificationRead();
        }
        exit;
    }
    
    if ($path === '/api/notifications/read-all' && $method === 'POST') {
        if (!Auth::check()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit;
        }
        
        if (class_exists('Notification')) {
            $notification = new Notification();
            $notification->markAllAsRead();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($path === '/api/available-cars') {
        if (!Auth::check()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'unauthorized']);
            exit;
        }
        
        if (class_exists('RentalController')) {
            $controller = new RentalController();
            $controller->availableCars();
        }
        exit;
    }
    
    // ============================================================
    // Authentication Routes
    // ============================================================
    
    if ($path === '/logout') {
        $controller = new AuthController();
        $controller->logout();
        exit;
    }
    
    if ($path === '/login') {
        $_SESSION[$redirectKey]['count']++;
        
        if (Auth::check()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        $controller = new AuthController();
        
        if ($method === 'POST') {
            $controller->login();
        } else {
            $_SESSION[$redirectKey]['count'] = 0;
            $controller->showLogin();
        }
        exit;
    }
    
    // ============================================================
    // Dashboard Routes
    // ============================================================
    
    if ($path === '/dashboard') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        if (class_exists('DashboardController')) {
            $controller = new DashboardController();
            $controller->index();
        } else {
            renderFallbackDashboard();
        }
        exit;
    }
    
    // ============================================================
    // ✅ Users & Profile Routes
    // ============================================================
    
    if ($path === '/profile') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        if (!class_exists('UserController')) {
            http_response_code(500);
            die('❌ UserController غير موجود');
        }
        
        $controller = new UserController();
        $controller->profile();
        exit;
    }
    
    if ($path === '/profile/update' && $method === 'POST') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $controller = new UserController();
        $controller->updateProfile();
        exit;
    }
    
    if ($path === '/profile/change-password' && $method === 'POST') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $controller = new UserController();
        $controller->changeOwnPassword();
        exit;
    }
    
    if ($path === '/users') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check() || !Auth::isAdmin()) {
            Auth::setFlash('error', 'غير مصرح — الإدارة للمدير فقط');
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        if (!class_exists('UserController')) {
            http_response_code(500);
            die('❌ UserController غير موجود');
        }
        
        $controller = new UserController();
        
        if ($method === 'POST') {
            $controller->store();
        } else {
            $controller->index();
        }
        exit;
    }
    
    if ($path === '/users/create') {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        $controller = new UserController();
        $controller->create();
        exit;
    }
    
    if (preg_match('#^/users/(\d+)(/edit|/delete|/toggle-active|/reset-password)?$#', $path, $matches)) {
        if (!Auth::check() || !Auth::isAdmin()) {
            header('Location: ' . url('/dashboard'));
            exit;
        }
        
        $userId = (int) $matches[1];
        $subAction = $matches[2] ?? '';
        
        $controller = new UserController();
        
        switch ($subAction) {
            case '/edit':
                if ($method === 'POST') {
                    $controller->update($userId);
                } else {
                    $controller->edit($userId);
                }
                break;
            
            case '/delete':
                if ($method !== 'POST') {
                    header('Location: ' . url('/users/' . $userId));
                    exit;
                }
                $controller->delete($userId);
                break;
            
            case '/toggle-active':
                if ($method !== 'POST') {
                    header('Location: ' . url('/users/' . $userId));
                    exit;
                }
                $controller->toggleActive($userId);
                break;
            
            case '/reset-password':
                if ($method !== 'POST') {
                    header('Location: ' . url('/users/' . $userId));
                    exit;
                }
                $controller->resetPassword($userId);
                break;
            
            default:
                $controller->show($userId);
                break;
        }
        exit;
    }
    
    // ============================================================
    // Cars Routes (السيارات)
    // ============================================================
    
    if (preg_match('#^/cars/image/(\d+)/delete$#', $path, $matches)) {
        if (!Auth::check()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'غير مصرح']);
            exit;
        }
        
        $imageId = (int) $matches[1];
        $controller = new CarController();
        $controller->deleteImage($imageId);
        exit;
    }
    
    if ($path === '/cars') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        $controller = new CarController();
        
        if ($method === 'POST') {
            $controller->store();
        } else {
            $controller->index();
        }
        exit;
    }
    
    if ($path === '/cars/create') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        $controller = new CarController();
        $controller->create();
        exit;
    }
    
    if (preg_match('#^/cars/(\d+)(/edit|/delete|/status|/image/delete)?$#', $path, $matches)) {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        $carId = (int) $matches[1];
        $subAction = $matches[2] ?? '';
        
        $controller = new CarController();
        
        switch ($subAction) {
            case '/edit':
                if ($method === 'POST') {
                    $controller->update($carId);
                } else {
                    $controller->edit($carId);
                }
                break;
            
            case '/delete':
                if ($method !== 'POST') {
                    header('Location: ' . url('/cars/' . $carId));
                    exit;
                }
                $controller->delete($carId);
                break;
            
            case '/status':
                if ($method !== 'POST') {
                    header('Location: ' . url('/cars/' . $carId));
                    exit;
                }
                $controller->changeStatus($carId);
                break;
            
            case '/image/delete':
                if ($method !== 'POST') {
                    header('Location: ' . url('/cars/' . $carId));
                    exit;
                }
                $controller->deleteMainImage($carId);
                break;
            
            default:
                $controller->show($carId);
                break;
        }
        exit;
    }
    
    // ============================================================
    // Customers Routes (المستأجرون)
    // ============================================================
    
    if ($path === '/customers') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        $controller = new CustomerController();
        
        if ($method === 'POST') {
            $controller->store();
        } else {
            $controller->index();
        }
        exit;
    }
    
    if ($path === '/customers/create') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        $controller = new CustomerController();
        $controller->create();
        exit;
    }
    
    if (preg_match('#^/customers/(\d+)(/edit|/delete|/blacklist)?$#', $path, $matches)) {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        $customerId = (int) $matches[1];
        $subAction = $matches[2] ?? '';
        
        $controller = new CustomerController();
        
        switch ($subAction) {
            case '/edit':
                if ($method === 'POST') {
                    $controller->update($customerId);
                } else {
                    $controller->edit($customerId);
                }
                break;
            
            case '/delete':
                if ($method !== 'POST') {
                    header('Location: ' . url('/customers/' . $customerId));
                    exit;
                }
                $controller->delete($customerId);
                break;
            
            case '/blacklist':
                if ($method !== 'POST') {
                    header('Location: ' . url('/customers/' . $customerId));
                    exit;
                }
                $controller->toggleBlacklist($customerId);
                break;
            
            default:
                $controller->show($customerId);
                break;
        }
        exit;
    }
    
    // ============================================================
    // Rentals Routes (العقود) - مع دعم PDF والتوقيع
    // ============================================================
    
    // ✅ 1. مسار أرشيف العقود
    if ($path === '/rentals/archive') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new RentalController();
        $controller->archive();
        exit;
    }
    
    // ✅ 2. مسار توليد PDF العقد
    if (preg_match('#^/rentals/(\d+)/pdf$#', $path, $matches)) {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $rentalId = (int) $matches[1];
        $controller = new RentalController();
        $controller->downloadPdf($rentalId);
        exit;
    }
    
    // ✅ 3. مسار حفظ PDF في السيرفر
    if (preg_match('#^/rentals/(\d+)/save-pdf$#', $path, $matches)) {
        if (!Auth::check()) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'unauthorized']);
            exit;
        }
        
        $rentalId = (int) $matches[1];
        $controller = new RentalController();
        $controller->savePdfToServer($rentalId);
        exit;
    }
    
    // ✅ 4. مسار حفظ التوقيع الإلكتروني
    if (preg_match('#^/rentals/(\d+)/sign$#', $path, $matches)) {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $rentalId = (int) $matches[1];
        $controller = new RentalController();
        
        if ($method === 'POST') {
            $controller->saveSignature($rentalId);
        } else {
            header('Location: ' . url('/rentals/' . $rentalId));
            exit;
        }
        exit;
    }
    
    // ✅ 5. مسار طباعة العقد
    if (preg_match('#^/rentals/(\d+)/print$#', $path, $matches)) {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $rentalId = (int) $matches[1];
        $controller = new RentalController();
        $controller->print($rentalId);
        exit;
    }
    
    // ✅ 6. قائمة العقود
    if ($path === '/rentals') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        if (!class_exists('RentalController')) {
            http_response_code(500);
            die('❌ RentalController غير موجود');
        }
        
        $controller = new RentalController();
        
        if ($method === 'POST') {
            $controller->store();
        } else {
            $controller->index();
        }
        exit;
    }
    
    // ✅ 7. إنشاء عقد جديد
    if ($path === '/rentals/create') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        $controller = new RentalController();
        $controller->create();
        exit;
    }
    
    // ✅ 8. مسارات العقد الديناميكية (show, close, cancel)
    if (preg_match('#^/rentals/(\d+)(/close|/cancel)?$#', $path, $matches)) {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        $rentalId = (int) $matches[1];
        $subAction = $matches[2] ?? '';
        
        $controller = new RentalController();
        
        switch ($subAction) {
            case '/close':
                if ($method !== 'POST') {
                    header('Location: ' . url('/rentals/' . $rentalId));
                    exit;
                }
                $controller->close($rentalId);
                break;
            
            case '/cancel':
                if ($method !== 'POST') {
                    header('Location: ' . url('/rentals/' . $rentalId));
                    exit;
                }
                $controller->cancel($rentalId);
                break;
            
            default:
                $controller->show($rentalId);
                break;
        }
        exit;
    }
    
    // ============================================================
    // Payments Routes (المدفوعات)
    // ============================================================
    
    if (preg_match('#^/payments/(\d+)/print$#', $path, $matches)) {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $paymentId = (int) $matches[1];
        $controller = new PaymentController();
        $controller->print($paymentId);
        exit;
    }
    
    if ($path === '/payments') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        if (!class_exists('PaymentController')) {
            http_response_code(500);
            die('❌ PaymentController غير موجود');
        }
        
        $controller = new PaymentController();
        
        if ($method === 'POST') {
            $controller->store();
        } else {
            $controller->index();
        }
        exit;
    }
    
    // ============================================================
    // Expenses Routes (المصروفات)
    // ============================================================
    
    if ($path === '/expenses') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        if (!class_exists('ExpenseController')) {
            http_response_code(500);
            die('❌ ExpenseController غير موجود');
        }
        
        $controller = new ExpenseController();
        
        if ($method === 'POST') {
            $controller->store();
        } else {
            $controller->index();
        }
        exit;
    }
    
    if ($path === '/expenses/create') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        $controller = new ExpenseController();
        $controller->create();
        exit;
    }
    
    if (preg_match('#^/expenses/(\d+)(/edit|/delete)?$#', $path, $matches)) {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        $expenseId = (int) $matches[1];
        $subAction = $matches[2] ?? '';
        
        $controller = new ExpenseController();
        
        switch ($subAction) {
            case '/edit':
                if ($method === 'POST') {
                    $controller->update($expenseId);
                } else {
                    $controller->edit($expenseId);
                }
                break;
            
            case '/delete':
                if ($method !== 'POST') {
                    header('Location: ' . url('/expenses/' . $expenseId));
                    exit;
                }
                $controller->delete($expenseId);
                break;
            
            default:
                $controller->show($expenseId);
                break;
        }
        exit;
    }
    
    // ============================================================
    // Reports Routes (التقارير)
    // ============================================================
    
    if ($path === '/reports') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        if (!class_exists('ReportController')) {
            http_response_code(500);
            die('❌ ReportController غير موجود');
        }
        
        $controller = new ReportController();
        $controller->index();
        exit;
    }
    
    if ($path === '/reports/profit') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new ReportController();
        $controller->profit();
        exit;
    }
    
    if ($path === '/reports/expenses') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new ReportController();
        $controller->expenses();
        exit;
    }
    
    if ($path === '/reports/cars') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new ReportController();
        $controller->cars();
        exit;
    }
    
    if ($path === '/reports/payments') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new ReportController();
        $controller->payments();
        exit;
    }
    
    if ($path === '/reports/export') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new ReportController();
        $controller->export();
        exit;
    }
    
    // ============================================================
    // Settings Routes (الإعدادات)
    // ============================================================
    
    if ($path === '/settings') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        if (!class_exists('SettingController')) {
            http_response_code(500);
            die('❌ SettingController غير موجود');
        }
        
        $controller = new SettingController();
        $controller->index();
        exit;
    }
    
    if ($path === '/settings/office' && $method === 'POST') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new SettingController();
        $controller->saveOffice();
        exit;
    }
    
    if ($path === '/settings/notifications' && $method === 'POST') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new SettingController();
        $controller->saveNotifications();
        exit;
    }
    
    if ($path === '/settings/tax' && $method === 'POST') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new SettingController();
        $controller->saveTax();
        exit;
    }
    
    if ($path === '/settings/terms' && $method === 'POST') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new SettingController();
        $controller->saveTerms();
        exit;
    }
    
    if ($path === '/settings/backup' && $method === 'POST') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new SettingController();
        $controller->backup();
        exit;
    }
    
    if ($path === '/settings/clear-log' && $method === 'POST') {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        $controller = new SettingController();
        $controller->clearLog();
        exit;
    }
    
    // ============================================================
    // Notifications Routes (التنبيهات)
    // ============================================================
    
    if ($path === '/notifications') {
        $_SESSION[$redirectKey]['count']++;
        
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }
        
        $_SESSION[$redirectKey]['count'] = 0;
        
        if (!class_exists('Notification')) {
            http_response_code(500);
            die('❌ Notification class غير موجود');
        }
        
        $notification = new Notification();
        $notifications = $notification->getWithDetails(100);
        $unreadCount = $notification->countUnread();
        
        $user = Auth::user();
        $pageTitle = 'notifications';
        
        header('Content-Type: text/html; charset=utf-8');
        ?>
        <!DOCTYPE html>
        <html lang="<?= e(Lang::current()) ?>" dir="<?= e(Lang::direction()) ?>" data-theme="light">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?= e('notifications') ?> | <?= e('app_name') ?></title>
            <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🔔</text></svg>">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="<?= url('/assets/css/themes.css') ?>">
            <link rel="stylesheet" href="<?= url('/assets/css/style.css') ?>">
            <link rel="stylesheet" href="<?= url('/assets/css/dashboard.css') ?>">
            <link rel="stylesheet" href="<?= url('/assets/css/rentals.css') ?>">
            <script>
                (function() {
                    const savedTheme = localStorage.getItem('theme') || 'light';
                    let theme = savedTheme;
                    if (savedTheme === 'system') {
                        theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                    }
                    document.documentElement.setAttribute('data-theme', theme);
                })();
            </script>
        </head>
        <body class="dashboard-page">
            <div class="app-layout">
                <?php require APP_PATH . '/views/layout/sidebar.php'; ?>
                <div class="main-wrapper">
                    <?php require APP_PATH . '/views/layout/header.php'; ?>
                    <main class="main-content">
                        <div class="page-header">
                            <div>
                                <h2 class="page-title">🔔 <?= e('notifications') ?></h2>
                                <p class="page-subtitle">
                                    <?= count($notifications) ?> <?= e('notification') ?> — 
                                    <strong class="text-danger"><?= $unreadCount ?></strong> <?= e('unread') ?>
                                </p>
                            </div>
                            <?php if ($unreadCount > 0): ?>
                                <button type="button" class="btn btn-secondary" onclick="markAllRead()">
                                    ✅ <?= e('mark_all_read') ?>
                                </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card">
                            <div class="card-body no-padding">
                                <?php if (empty($notifications)): ?>
                                    <div class="empty-state-large">
                                        <div class="empty-icon">🔔</div>
                                        <h3><?= e('no_notifications') ?></h3>
                                    </div>
                                <?php else: ?>
                                    <div class="notifications-list-full">
                                        <?php foreach ($notifications as $notif): ?>
                                            <?php
                                            $icons = [
                                                'payment_due' => '💰',
                                                'payment_overdue' => '⚠️',
                                                'rental_ending' => '⏰',
                                                'rental_overdue' => '🚨',
                                                'doc_expiry' => '📋',
                                                'service_due' => '🔧',
                                            ];
                                            $icon = $icons[$notif['type'] ?? 'info'] ?? '🔔';
                                            ?>
                                            <div class="notification-row notification-<?= e($notif['type'] ?? 'info') ?>">
                                                <div class="notification-row-icon"><?= $icon ?></div>
                                                <div class="notification-row-content">
                                                    <strong><?= e($notif['type'] ?? '') ?></strong>
                                                    <p><?= e($notif['details'] ?? '') ?></p>
                                                    <small>📅 <?= e(format_date($notif['due_date'] ?? '')) ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </main>
                    <?php require APP_PATH . '/views/layout/footer.php'; ?>
                </div>
            </div>
            <script>
                function markAllRead() {
                    fetch('<?= url('/api/notifications/read-all') ?>', {
                        method: 'POST',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).then(() => window.location.reload());
                }
            </script>
            <script src="<?= url('/assets/js/theme.js') ?>"></script>
        </body>
        </html>
        <?php
        exit;
    }
    
    // ============================================================
    // 404 - صفحة غير موجودة
    // ============================================================
    http_response_code(404);
    
    $errorView = APP_PATH . '/views/errors/404.php';
    if (file_exists($errorView)) {
        require $errorView;
    } else {
        renderDefault404($path);
    }
    exit;
    
} catch (Throwable $e) {
    error_log(sprintf(
        "[%s] %s in %s:%d\nStack trace:\n%s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));
    
    http_response_code(500);
    
    $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
           && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => 'error_general',
            'message' => APP_DEBUG ? $e->getMessage() : 'حدث خطأ غير متوقع',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if (APP_DEBUG) {
        renderErrorPage($e);
    } else {
        $errorView = APP_PATH . '/views/errors/500.php';
        if (file_exists($errorView)) {
            require $errorView;
        } else {
            echo '<h1>حدث خطأ في النظام</h1>';
            echo '<p>يرجى المحاولة لاحقاً.</p>';
        }
    }
    exit;
}

// ============================================================
// دوال مساعدة
// ============================================================

function renderRedirectLoopError(string $path): void
{
    $safePath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
    
    echo <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>حلقة إعادة توجيه</title>
    <style>
        body { font-family: 'Cairo', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f7fa; padding: 20px; }
        .error-box { background: white; padding: 40px; border-radius: 16px; max-width: 600px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .error-icon { font-size: 4rem; margin-bottom: 16px; }
        h1 { color: #ef4444; margin-bottom: 12px; font-size: 1.4rem; }
        p { color: #6c757d; margin-bottom: 20px; }
        code { background: #e1e4e8; padding: 2px 8px; border-radius: 4px; font-size: 0.85rem; }
        .actions { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
    </style>
</head>
<body>
    <div class="error-box">
        <div class="error-icon">🔄</div>
        <h1>حلقة إعادة توجيه</h1>
        <p>تم اكتشاف إعادة توجيه متكررة للرابط: <code>{$safePath}</code></p>
        <p>الحل: امسح الكوكيز وابدأ من جديد.</p>
        
        <div class="actions">
            <a href="?__clear_session=1" class="btn btn-danger">🧹 مسح الجلسة</a>
            <a href="?__debug=1" class="btn btn-primary">🐛 وضع التصحيح</a>
        </div>
    </div>
</body>
</html>
HTML;
}

function renderFallbackDashboard(): void
{
    $user = Auth::user();
    $fullName = htmlspecialchars($user['full_name'] ?? 'مستخدم', ENT_QUOTES, 'UTF-8');
    $role = htmlspecialchars($user['role'] ?? '', ENT_QUOTES, 'UTF-8');
    $logoutUrl = url('/logout');
    
    echo <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم</title>
    <style>
        body { font-family: 'Cairo', sans-serif; background: #f5f7fa; padding: 40px; text-align: center; }
        .card { background: white; padding: 40px; border-radius: 16px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        h1 { color: #2563eb; }
        .info { text-align: right; margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px; }
        a { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #ef4444; color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>🎉 مرحباً {$fullName}</h1>
        <div class="info">
            <p><strong>الاسم:</strong> {$fullName}</p>
            <p><strong>الدور:</strong> {$role}</p>
            <p><strong>الوقت:</strong> " . date('Y-m-d H:i:s') . "</p>
        </div>
        <a href="{$logoutUrl}">تسجيل الخروج</a>
    </div>
</body>
</html>
HTML;
}

function renderDefault404(string $path): void
{
    $safePath = htmlspecialchars($path, ENT_QUOTES, 'UTF-8');
    $homeUrl = url('/dashboard');
    
    echo <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>404 - الصفحة غير موجودة</title>
    <style>
        body { font-family: 'Cairo', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; background: #f5f7fa; }
        .error-box { text-align: center; padding: 40px; }
        .error-code { font-size: 6rem; font-weight: 700; color: #2563eb; margin: 0; }
        h1 { font-size: 1.5rem; margin: 10px 0; }
        p { color: #6c757d; }
        code { background: #e1e4e8; padding: 2px 8px; border-radius: 4px; }
        a { display: inline-block; margin-top: 20px; padding: 10px 24px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="error-box">
        <p class="error-code">404</p>
        <h1>الصفحة غير موجودة</h1>
        <p>المسار: <code>{$safePath}</code></p>
        <a href="{$homeUrl}">العودة للوحة التحكم</a>
    </div>
</body>
</html>
HTML;
}

function renderErrorPage(Throwable $e): void
{
    $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $file = htmlspecialchars($e->getFile(), ENT_QUOTES, 'UTF-8');
    $line = $e->getLine();
    $trace = htmlspecialchars($e->getTraceAsString(), ENT_QUOTES, 'UTF-8');
    
    echo <<<HTML
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>خطأ في النظام</title>
    <style>
        body { font-family: 'Consolas', monospace; background: #1a1d21; color: #e8eaed; padding: 20px; line-height: 1.6; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #ef4444; border-bottom: 2px solid #ef4444; padding-bottom: 10px; }
        .error-box { background: #2d2d2d; padding: 20px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #ef4444; }
        .label { color: #fbbf24; font-weight: bold; display: inline-block; min-width: 120px; }
        .value { color: #60a5fa; }
        pre { background: #0f1419; padding: 15px; border-radius: 8px; overflow-x: auto; color: #d1d5db; }
        .debug-link { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; }
    </style>
</head>
<body>
<div class="container">
    <h1>❌ خطأ في النظام</h1>
    
    <div class="error-box">
        <p><span class="label">الرسالة:</span> <span class="value">{$message}</span></p>
        <p><span class="label">الملف:</span> <span class="value">{$file}</span></p>
        <p><span class="label">السطر:</span> <span class="value">{$line}</span></p>
    </div>
    
    <h3>📍 Stack Trace:</h3>
    <pre>{$trace}</pre>
    
    <a class="debug-link" href="?__debug=1">🐛 فتح وضع التصحيح</a>
</div>
</body>
</html>
HTML;
}