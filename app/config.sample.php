<?php
/*
 * انسخ هذا الملف إلى app/config.local.php واملأ بيانات قاعدة البيانات.
 * لا تُدرِج config.local.php في Git (مُستبعَد في .gitignore).
 *
 *   cp app/config.sample.php app/config.local.php
 */
return [
    'db_host'  => 'localhost',
    'db_port'  => 3306,
    'db_name'  => 'tasktrak_app',      // يجب أن يبدأ بـ tasktrak_ على cPanel
    'db_user'  => 'tasktrak_app',      // يجب أن يبدأ بـ tasktrak_ على cPanel
    'db_pass'  => 'ضع_كلمة_مرور_قاعدة_البيانات_هنا',

    'app_name' => 'نظام المهام',
    'app_org'  => 'TaskTrak',
    'debug'    => false,

    // تذكير المواعيد عبر واتساب (اختياري) — راجع deploy/whatsapp-reminders.md
    // 'wa_enabled'  => true,
    // 'wa_provider' => 'cloud',          // cloud (رسمي) | ultramsg | twilio
    // 'wa_token'    => '',               // توكن المزوّد
    // 'wa_from'     => '',               // cloud: Phone Number ID · ultramsg: instance id · twilio: رقم المُرسِل
    // 'wa_sid'      => '',               // Twilio فقط: Account SID
    // 'wa_template' => '',               // cloud فقط: اسم قالب معتمد بمتغيّر {{1}}
    // 'wa_lang'     => 'ar',
];
