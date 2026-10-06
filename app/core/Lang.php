<?php
/**
 * إدارة اللغة (i18n)
 */

class Lang
{
    private static array $translations = [];
    private static string $currentLocale = 'ar';
    private static string $direction = 'rtl';

    public static function init(): void
    {
        $locale = $_SESSION['locale']
            ?? $_COOKIE['locale']
            ?? 'ar';

        if (!in_array($locale, ['ar', 'en'], true)) {
            $locale = 'ar';
        }

        self::setLocale($locale);
    }

    public static function setLocale(string $locale): void
    {
        if (!in_array($locale, ['ar', 'en'], true)) {
            return;
        }

        self::$currentLocale = $locale;
        self::$direction = ($locale === 'ar') ? 'rtl' : 'ltr';

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['locale'] = $locale;
        }

        if (!headers_sent()) {
            setcookie('locale', $locale, [
                'expires'  => time() + 365 * 24 * 60 * 60,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }

        self::loadTranslations($locale);
    }

    private static function loadTranslations(string $locale): void
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 2);
        $file = $basePath . "/app/lang/{$locale}.php";

        if (file_exists($file)) {
            self::$translations = require $file;
        } else {
            self::$translations = [];
        }
    }

    public static function get(string $key, array $replace = []): string
    {
        $text = self::$translations[$key] ?? $key;

        foreach ($replace as $k => $v) {
            $text = str_replace(':' . $k, (string) $v, $text);
        }

        return $text;
    }

    public static function current(): string
    {
        return self::$currentLocale;
    }

    public static function direction(): string
    {
        return self::$direction;
    }

    public static function isArabic(): bool
    {
        return self::$currentLocale === 'ar';
    }

    public static function toggle(): string
    {
        $new = self::$currentLocale === 'ar' ? 'en' : 'ar';
        self::setLocale($new);
        return $new;
    }
}