<?php
/**
 * دوال مساعدة عامة
 */

// ============================================================
// دالة الترجمة + HTML Escape
// تقبل: string, int, float, null, bool
// ============================================================
if (!function_exists('e')) {
    function e($key, array $replace = []): string
    {
        if ($key === null) return '';
        if (is_bool($key)) return $key ? '1' : '';
        if (is_int($key) || is_float($key)) {
            return htmlspecialchars((string) $key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if (!is_string($key)) return '';
        
        $text = $key;
        if (class_exists('Lang')) {
            $translated = Lang::get($key, $replace);
            if ($translated !== $key) $text = $translated;
        }
        
        return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// ============================================================
// دالة الترجمة فقط (بدون HTML Escape)
// ============================================================
if (!function_exists('__')) {
    function __($key, array $replace = []): string
    {
        if ($key === null) return '';
        if (is_int($key) || is_float($key)) return (string) $key;
        if (!is_string($key)) return '';
        if (class_exists('Lang')) return Lang::get($key, $replace);
        return $key;
    }
}

// ============================================================
// إنشاء رابط كامل (متوافق مع السيرفر المدمج و XAMPP)
// ============================================================
if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        static $config = null;
        if ($config === null) {
            $config = require BASE_PATH . '/app/config/config.php';
        }
        
        $isBuiltInServer = php_sapi_name() === 'cli-server';
        
        if ($isBuiltInServer) {
            $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
            $base = $protocol . '://' . $host;
        } else {
            $base = rtrim($config['app_url'] ?? 'http://localhost', '/');
        }

        $path = ltrim($path, '/');
        return $path === '' ? $base : $base . '/' . $path;
    }
}

// ============================================================
// رابط ملف أصول
// ============================================================
if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

// ============================================================
// إعادة توجيه
// ============================================================
if (!function_exists('redirect')) {
    function redirect(string $path): void
    {
        header('Location: ' . url($path));
        exit;
    }
}

// ============================================================
// استجابة JSON
// ============================================================
if (!function_exists('json_response')) {
    function json_response(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

// ============================================================
// هل الطلب AJAX؟
// ============================================================
if (!function_exists('is_ajax')) {
    function is_ajax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) 
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
}

// ============================================================
// قيمة حقل من الطلب السابق
// ============================================================
if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old'][$key] ?? $default;
    }
}

// ============================================================
// حقل CSRF مخفي
// ============================================================
if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return Csrf::field();
    }
}

// ============================================================
// رمز CSRF الحالي
// ============================================================
if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Csrf::getToken();
    }
}

// ============================================================
// تنسيق العملة
// ============================================================
if (!function_exists('format_money')) {
    function format_money($amount, string $currency = 'SAR'): string
    {
        $amount = (float) $amount;
        return number_format($amount, 2) . ' ' . $currency;
    }
}

// ============================================================
// تنسيق التاريخ
// ============================================================
if (!function_exists('format_date')) {
    function format_date(?string $date, string $format = 'Y-m-d'): string
    {
        if (empty($date) || $date === '0000-00-00') {
            return '—';
        }
        
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : '—';
    }
}

// ============================================================
// تنسيق التاريخ والوقت
// ============================================================
if (!function_exists('format_datetime')) {
    function format_datetime(?string $datetime): string
    {
        return format_date($datetime, 'Y-m-d H:i');
    }
}

// ============================================================
// حساب الأيام بين تاريخين
// ============================================================
if (!function_exists('days_between')) {
    function days_between(string $from, string $to): int
    {
        $fromTime = strtotime($from);
        $toTime = strtotime($to);
        
        if (!$fromTime || !$toTime) {
            return 0;
        }
        
        return (int) floor(($toTime - $fromTime) / 86400);
    }
}

// ============================================================
// الحصول على أحرف مختصرة من الاسم
// ============================================================
if (!function_exists('initials')) {
    function initials(?string $name, int $count = 1): string
    {
        if (empty($name)) {
            return '?';
        }
        
        $name = trim($name);
        return mb_substr($name, 0, $count, 'UTF-8');
    }
}

// ============================================================
// ✅ Badge للألوان
// ============================================================
if (!function_exists('status_badge')) {
    function status_badge(?string $status): string
    {
        return match ($status) {
            'available'      => 'success',
            'rented'         => 'info',
            'maintenance'    => 'warning',
            'out_of_service' => 'danger',
            'active'         => 'success',
            'inactive'       => 'danger',
            'reserved'       => 'info',
            'completed'      => 'secondary',
            'cancelled'      => 'danger',
            'pending'        => 'warning',
            'paid'           => 'success',
            'partial'        => 'info',
            'overdue'        => 'danger',
            default          => 'secondary',
        };
    }
}

if (!function_exists('status_label')) {
    function status_label(?string $status): string
    {
        if (empty($status)) {
            return '';
        }
        return e($status);
    }
}

// ============================================================
// ✅ أيقونات الحالة
// ============================================================
if (!function_exists('status_icon')) {
    function status_icon(?string $status): string
    {
        return match ($status) {
            'available'      => '✅',
            'rented'         => '🔑',
            'maintenance'    => '🔧',
            'out_of_service' => '🚫',
            'active'         => '✅',
            'inactive'       => '🚫',
            'pending'        => '⏳',
            'paid'           => '✅',
            'partial'        => '⏰',
            'overdue'        => '⚠️',
            'completed'      => '✅',
            'cancelled'      => '❌',
            'reserved'       => '📅',
            default          => '⚪',
        };
    }
}

// ============================================================
// ✅ الأدوار والصلاحيات
// ============================================================
if (!function_exists('role_badge')) {
    function role_badge(?string $role): string
    {
        return match ($role) {
            'admin'      => 'danger',
            'accountant' => 'info',
            'employee'   => 'success',
            default      => 'secondary',
        };
    }
}

if (!function_exists('role_icon')) {
    function role_icon(?string $role): string
    {
        return match ($role) {
            'admin'      => '👑',
            'accountant' => '💰',
            'employee'   => '🧑‍💼',
            default      => '👤',
        };
    }
}

if (!function_exists('role_name')) {
    function role_name(?string $role): string
    {
        return match ($role) {
            'admin'      => 'مدير',
            'accountant' => 'محاسب',
            'employee'   => 'موظف',
            default      => $role ?? '',
        };
    }
}

// ============================================================
// ✅ طرق الدفع
// ============================================================
if (!function_exists('method_icon')) {
    function method_icon(?string $method): string
    {
        return match ($method) {
            'cash'          => '💵',
            'network'       => '💳',
            'bank_transfer' => '🏦',
            default         => '💰',
        };
    }
}

if (!function_exists('method_name')) {
    function method_name(?string $method): string
    {
        return match ($method) {
            'cash'          => 'كاش',
            'network'       => 'شبكة',
            'bank_transfer' => 'تحويل بنكي',
            default         => $method ?? '',
        };
    }
}

// ============================================================
// ✅ أنواع الدفعات
// ============================================================
if (!function_exists('payment_type_icon')) {
    function payment_type_icon(?string $type): string
    {
        return match ($type) {
            'rent'           => '💰',
            'deposit'        => '🔒',
            'penalty'        => '⚠️',
            'deposit_refund' => '↩️',
            default          => '💵',
        };
    }
}

// ============================================================
// ✅ أنواع الوقود
// ============================================================
if (!function_exists('fuel_icon')) {
    function fuel_icon(?string $fuel): string
    {
        return match ($fuel) {
            'petrol'   => '⛽',
            'diesel'   => '🛢️',
            'hybrid'   => '🔋',
            'electric' => '⚡',
            default    => '⛽',
        };
    }
}

// ============================================================
// ✅ أنواع ناقل الحركة
// ============================================================
if (!function_exists('transmission_icon')) {
    function transmission_icon(?string $transmission): string
    {
        return match ($transmission) {
            'automatic' => '🅰️',
            'manual'    => '🅼',
            default     => '⚙️',
        };
    }
}