<?php
declare(strict_types=1);

/*
 * إعدادات التطبيق.
 * تُقرأ القيم من app/config.local.php (يُنشأ أثناء النشر، غير مُتتبَّع في Git)،
 * أو من متغيرات البيئة، وإلا فقيم افتراضية.
 */

$__local = __DIR__ . '/config.local.php';
$__cfg = is_file($__local) ? (require $__local) : [];

if (!function_exists('cfg')) {
    function cfg(string $key, string $env, $default)
    {
        global $__cfg;
        if (is_array($__cfg) && array_key_exists($key, $__cfg)) {
            return $__cfg[$key];
        }
        $v = getenv($env);
        return $v !== false ? $v : $default;
    }
}

define('DB_HOST', (string) cfg('db_host', 'TT_DB_HOST', 'localhost'));
define('DB_PORT', (int) cfg('db_port', 'TT_DB_PORT', '3306'));
define('DB_NAME', (string) cfg('db_name', 'TT_DB_NAME', 'tasktrak_app'));
define('DB_USER', (string) cfg('db_user', 'TT_DB_USER', 'tasktrak_app'));
define('DB_PASS', (string) cfg('db_pass', 'TT_DB_PASS', ''));

define('APP_NAME', (string) cfg('app_name', 'TT_APP_NAME', 'نظام المهام'));
define('APP_ORG',  (string) cfg('app_org',  'TT_APP_ORG',  'TaskTrak'));

// إصدار الأصول (CSS/JS) لتجاوز الكاش عند التحديث — ارفعه عند تغيير style.css/app.js
define('ASSET_VER', '3');

// وضع التصحيح: يُظهر تفاصيل الأخطاء. أبقِه false على الإنتاج.
define('APP_DEBUG', filter_var(cfg('debug', 'TT_DEBUG', '0'), FILTER_VALIDATE_BOOL));

// إشعارات البريد
define('MAIL_ENABLED', filter_var(cfg('mail_enabled', 'TT_MAIL_ENABLED', '1'), FILTER_VALIDATE_BOOL));
define('MAIL_FROM', (string) cfg('mail_from', 'TT_MAIL_FROM', 'noreply@tasktrak.co'));
define('MAIL_FROM_NAME', (string) cfg('mail_from_name', 'TT_MAIL_FROM_NAME', APP_NAME));
