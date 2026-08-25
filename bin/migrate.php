<?php
declare(strict_types=1);
/*
 * تطبيق ترحيلات قاعدة البيانات (يُستدعى من سكربت النشر بعد كل تحديث).
 *   php bin/migrate.php
 */
require __DIR__ . '/../app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

try {
    run_migrations();
    $row = q_one("SELECT v FROM app_meta WHERE k = 'schema_version'");
    fwrite(STDOUT, 'schema_version=' . ($row['v'] ?? '?') . " (OK)\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'migrate error: ' . $e->getMessage() . "\n");
    exit(1);
}
