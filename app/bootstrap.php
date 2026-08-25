<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/models.php';
require_once __DIR__ . '/migrate.php';
require_once __DIR__ . '/mailer.php';

// صفحات ديناميكية بمصادقة: امنع تخزينها في المتصفح (أمان + يضمن عمل صفحة عدم الاتصال)
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Cache-Control: no-store, must-revalidate');
}

start_session();
