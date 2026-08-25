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
];
