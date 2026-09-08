<?php
declare(strict_types=1);

/*
 * نظام ترحيل خفيف لتحديث مخطط قاعدة البيانات بأمان دون فقد بيانات.
 * الترحيلات فكرية idempotent (ADD COLUMN IF NOT EXISTS) فيمكن إعادة تشغيلها.
 */

if (!defined('SCHEMA_VERSION')) {
    define('SCHEMA_VERSION', 5); // 1:الأساس 2:البريد 3:المواعيد 4:التنبيهات+حالة 5:رابط مشاركة الموعد
}

/** الترحيلات المطلوبة عند كل إصدار. */
function migrations_map(): array
{
    return [
        5 => [
            'ALTER TABLE appointments ADD COLUMN IF NOT EXISTS share_token VARCHAR(40) DEFAULT NULL',
            'ALTER TABLE appointments ADD INDEX IF NOT EXISTS idx_share (share_token)',
        ],
        2 => [
            'ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(190) NULL',
        ],
        4 => [
            "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'مجدول'",
            "ALTER TABLE appointments ADD COLUMN IF NOT EXISTS postponed_to DATETIME NULL",
            "CREATE TABLE IF NOT EXISTS notifications (
                id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id    INT UNSIGNED NOT NULL,
                type       VARCHAR(20)  NOT NULL,
                title      VARCHAR(200) NOT NULL,
                body       VARCHAR(300) DEFAULT NULL,
                route      VARCHAR(30)  NOT NULL,
                ref_id     INT UNSIGNED DEFAULT NULL,
                is_read    TINYINT(1)   NOT NULL DEFAULT 0,
                created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_user_read (user_id, is_read),
                CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        ],
        3 => [
            "CREATE TABLE IF NOT EXISTS appointments (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                subject     VARCHAR(200) NOT NULL,
                with_whom   VARCHAR(200) DEFAULT NULL,
                starts_at   DATETIME     NOT NULL,
                location    VARCHAR(200) DEFAULT NULL,
                notes       TEXT         DEFAULT NULL,
                created_by  INT UNSIGNED DEFAULT NULL,
                shared_with INT UNSIGNED DEFAULT NULL,
                created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_starts (starts_at),
                CONSTRAINT fk_appt_creator FOREIGN KEY (created_by)  REFERENCES users(id) ON DELETE SET NULL,
                CONSTRAINT fk_appt_shared  FOREIGN KEY (shared_with) REFERENCES users(id) ON DELETE SET NULL
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
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
