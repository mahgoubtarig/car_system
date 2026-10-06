<?php
/**
 * حماية من هجمات CSRF
 */

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';
    private const TOKEN_TIME_KEY = '_csrf_time';
    
    /**
     * توليد رمز CSRF جديد
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // إذا كان هناك رمز صالح، أرجعه
        if (isset($_SESSION[self::TOKEN_KEY]) 
            && isset($_SESSION[self::TOKEN_TIME_KEY])
            && (time() - $_SESSION[self::TOKEN_TIME_KEY]) < 3600) {
            return $_SESSION[self::TOKEN_KEY];
        }
        
        // توليد رمز جديد
        $token = bin2hex(random_bytes(32));
        $_SESSION[self::TOKEN_KEY] = $token;
        $_SESSION[self::TOKEN_TIME_KEY] = time();
        
        return $token;
    }
    
    /**
     * الحصول على الرمز الحالي
     */
    public static function getToken(): string
    {
        return self::generateToken();
    }
    
    /**
     * التحقق من صحة الرمز
     */
    public static function validateToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($token) || empty($_SESSION[self::TOKEN_KEY])) {
            return false;
        }
        
        // التحقق من انتهاء الصلاحية
        if (isset($_SESSION[self::TOKEN_TIME_KEY]) 
            && (time() - $_SESSION[self::TOKEN_TIME_KEY]) > 3600) {
            self::destroyToken();
            return false;
        }
        
        // مقارنة آمنة ضد Timing Attacks
        return hash_equals($_SESSION[self::TOKEN_KEY], $token);
    }
    
    /**
     * تدمير الرمز
     */
    public static function destroyToken(): void
    {
        unset($_SESSION[self::TOKEN_KEY], $_SESSION[self::TOKEN_TIME_KEY]);
    }
    
    /**
     * توليد حقل مخفي للنماذج
     */
    public static function field(): string
    {
        $token = self::getToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }
}