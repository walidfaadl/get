<?php
declare(strict_types=1);

/*
 * إعدادات التطبيق.
 * تُقرأ القيم من app/config.local.php (يُنشأ أثناء النشر، غير مُتتبَّع في Git)،
 * أو من متغيرات البيئة، وإلا فقيم افتراضية.
 */

$__local = __DIR__ . '/config.local.php';
$__cfg = is_file($__local) ? (require $__local) : [];
if (!is_array($__cfg)) {
    $__cfg = [];
}
// إعدادات سرّية إضافية (تُزرَع على الخادم من أسرار GitHub، مثل واتساب) تُدمَج فوق المحلّية
$__secret = __DIR__ . '/config.secret.php';
if (is_file($__secret)) {
    $__s = require $__secret;
    if (is_array($__s)) {
        $__cfg = array_merge($__cfg, $__s);
    }
}

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
define('ASSET_VER', '12');

// وضع التصحيح: يُظهر تفاصيل الأخطاء. أبقِه false على الإنتاج.
define('APP_DEBUG', filter_var(cfg('debug', 'TT_DEBUG', '0'), FILTER_VALIDATE_BOOL));

// إشعارات البريد
define('MAIL_ENABLED', filter_var(cfg('mail_enabled', 'TT_MAIL_ENABLED', '1'), FILTER_VALIDATE_BOOL));
define('MAIL_FROM', (string) cfg('mail_from', 'TT_MAIL_FROM', 'noreply@tasktrak.co'));
define('MAIL_FROM_NAME', (string) cfg('mail_from_name', 'TT_MAIL_FROM_NAME', APP_NAME));

// تكامل واتساب (تُضبط القيم الحسّاسة في app/config.local.php)
// WA_PROVIDER: cloud (الرسمي، موصى به) | ultramsg | twilio
define('WA_ENABLED', filter_var(cfg('wa_enabled', 'TT_WA_ENABLED', '0'), FILTER_VALIDATE_BOOL));
define('WA_PROVIDER', (string) cfg('wa_provider', 'TT_WA_PROVIDER', 'cloud'));
define('WA_TOKEN', (string) cfg('wa_token', 'TT_WA_TOKEN', ''));      // Bearer/Instance token
define('WA_FROM', (string) cfg('wa_from', 'TT_WA_FROM', ''));         // cloud: phone_number_id · ultramsg: instance id · twilio: from (+9665..)
define('WA_SID', (string) cfg('wa_sid', 'TT_WA_SID', ''));            // twilio Account SID
define('WA_TEMPLATE', (string) cfg('wa_template', 'TT_WA_TEMPLATE', '')); // cloud: اسم القالب المعتمد (متغيّر {{1}})
define('WA_LANG', (string) cfg('wa_lang', 'TT_WA_LANG', 'ar'));       // لغة القالب

// تكامل تيليجرام (مجاني — عبر بوت)
define('TG_ENABLED', filter_var(cfg('tg_enabled', 'TT_TG_ENABLED', '0'), FILTER_VALIDATE_BOOL));
define('TG_BOT_TOKEN', (string) cfg('tg_bot_token', 'TT_TG_BOT_TOKEN', ''));     // توكن البوت من @BotFather
define('TG_BOT_USERNAME', (string) cfg('tg_bot_username', 'TT_TG_BOT_USERNAME', '')); // اسم البوت (بلا @) لرابط الربط
define('TG_WEBHOOK_SECRET', (string) cfg('tg_webhook_secret', 'TT_TG_WEBHOOK_SECRET', '')); // سِرّ تأمين الـ webhook
