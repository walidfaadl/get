<?php
declare(strict_types=1);
/*
 * أداة سطر أوامر لإنشاء/تحديث مستخدم (تُستعمل من سكربت النشر).
 *
 *   php bin/seed_user.php <role> <name> <username> <password> [department]
 *
 * role: manager | head
 * إن كان اسم المستخدم موجوداً يُحدَّث، وإلا يُنشأ.
 * تُنشئ الجداول تلقائياً إن لم تكن موجودة.
 */
require __DIR__ . '/../app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

$argvv = $argv ?? [];
if (count($argvv) < 5) {
    fwrite(STDERR, "الاستعمال: php bin/seed_user.php <role> <name> <username> <password> [department]\n");
    exit(2);
}

[$role, $name, $username, $password] = [$argvv[1], $argvv[2], $argvv[3], $argvv[4]];
$department = $argvv[5] ?? '';
$role = in_array($role, ['manager', 'head'], true) ? $role : 'head';

try {
    // ضمان وجود الجداول
    $schema = file_get_contents(__DIR__ . '/../schema.sql');
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
        if ($stmt !== '' && stripos($stmt, 'SET NAMES') !== 0) {
            db()->exec($stmt);
        }
    }

    $existing = q_one('SELECT id FROM users WHERE username = ? LIMIT 1', [$username]);
    if ($existing) {
        user_update((int) $existing['id'], [
            'name' => $name, 'username' => $username, 'password' => $password,
            'role' => $role, 'department' => $department, 'active' => 1,
        ]);
        fwrite(STDOUT, "updated:{$username}\n");
    } else {
        $id = user_create([
            'name' => $name, 'username' => $username, 'password' => $password,
            'role' => $role, 'department' => $department,
        ]);
        fwrite(STDOUT, "created:{$username}:{$id}\n");
    }
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'error: ' . $e->getMessage() . "\n");
    exit(1);
}
