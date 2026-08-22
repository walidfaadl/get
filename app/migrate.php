<?php
declare(strict_types=1);

/*
 * نظام ترحيل خفيف لتحديث مخطط قاعدة البيانات بأمان دون فقد بيانات.
 * الترحيلات فكرية idempotent (ADD COLUMN IF NOT EXISTS) فيمكن إعادة تشغيلها.
 */

if (!defined('SCHEMA_VERSION')) {
    define('SCHEMA_VERSION', 2); // 1: الجداول الأساسية · 2: عمود البريد
}

/** الترحيلات المطلوبة عند كل إصدار. */
function migrations_map(): array
{
    return [
        2 => [
            'ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(190) NULL',
        ],
    ];
}

/** تطبيق الترحيلات المعلّقة (يتوقف بأمان إن لم تكن القاعدة مهيّأة بعد). */
function run_migrations(): void
{
    // اقرأ الإصدار الحالي؛ إن لم يوجد جدول التعريف أنشئه
    try {
        $row = q_one("SELECT v FROM app_meta WHERE k = 'schema_version'");
    } catch (Throwable $e) {
        db()->exec(
            'CREATE TABLE IF NOT EXISTS app_meta (
                k VARCHAR(50) NOT NULL PRIMARY KEY,
                v VARCHAR(190) NOT NULL
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
        $row = null;
    }

    $cur = $row ? (int) $row['v'] : 0;
    if ($cur >= SCHEMA_VERSION) {
        return; // محدّث — لا شيء
    }

    // لا تُرحّل قاعدة غير مهيّأة (لا يوجد جدول users بعد)
    try {
        q_one('SELECT 1 FROM users LIMIT 1');
    } catch (Throwable $e) {
        return;
    }

    $map = migrations_map();
    for ($v = $cur + 1; $v <= SCHEMA_VERSION; $v++) {
        foreach ($map[$v] ?? [] as $sql) {
            db()->exec($sql);
        }
    }
    q(
        "INSERT INTO app_meta (k, v) VALUES ('schema_version', ?)
         ON DUPLICATE KEY UPDATE v = VALUES(v)",
        [SCHEMA_VERSION]
    );
}
