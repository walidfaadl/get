<?php
declare(strict_types=1);
/*
 * مُثبِّت لمرة واحدة: ينشئ الجداول ويضيف حساب المدير الأول.
 * بعد نجاح التثبيت احذف هذا الملف من الخادم:
 *     rm ~/public_html/install.php
 */
require __DIR__ . '/app/bootstrap.php';

$step = '';
$error = null;
$done = false;

function db_ok(): bool
{
    try { db(); return true; } catch (Throwable $e) { return false; }
}

function tables_ready(): bool
{
    try { q_one('SELECT 1 FROM users LIMIT 1'); return true; }
    catch (Throwable $e) { return false; }
}

$connected = db_ok();
$hasManager = $connected && tables_ready() ? managers_count() > 0 : false;

if ($hasManager) {
    $step = 'locked';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && $connected) {
    csrf_check();
    $name = trim((string) ($_POST['name'] ?? ''));
    $username = trim((string) ($_POST['username'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if ($name === '' || $username === '' || strlen($password) < 6) {
        $error = 'الاسم واسم المستخدم مطلوبان، وكلمة المرور ٦ أحرف على الأقل.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'صيغة البريد الإلكتروني غير صحيحة.';
    } else {
        try {
            // إنشاء الجداول من schema.sql
            $sql = file_get_contents(__DIR__ . '/schema.sql');
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                if ($stmt !== '' && stripos($stmt, 'SET NAMES') !== 0) {
                    db()->exec($stmt);
                }
            }
            run_migrations();
            if (managers_count() === 0) {
                user_create([
                    'name' => $name, 'username' => $username, 'email' => $email,
                    'password' => $password, 'role' => 'manager', 'department' => '',
                ]);
            }
            $done = true;
        } catch (Throwable $e) {
            $error = 'فشل التثبيت: ' . $e->getMessage();
        }
    }
}
?><!DOCTYPE html>
<html lang="ar" dir="rtl"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تثبيت نظام المهام</title>
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;700;900&display=swap" rel="stylesheet">
<style>
body{font-family:'Tajawal',sans-serif;direction:rtl;background:#6a1530;color:#1f2937;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:20px}
.box{background:#fff;border-radius:20px;padding:36px;max-width:440px;width:100%;box-shadow:0 20px 50px rgba(0,0,0,.3)}
h1{color:#8b1e3f;font-size:23px;margin-bottom:6px}
p.sub{color:#7a8597;font-size:14px;margin-bottom:22px}
label{display:block;font-size:13px;font-weight:700;margin:12px 0 6px}
input{width:100%;padding:11px 13px;border:1.5px solid #e5d9c8;border-radius:10px;background:#faf5ee;font-family:inherit;font-size:14px;box-sizing:border-box}
button{width:100%;margin-top:20px;padding:13px;border:none;border-radius:10px;color:#fff;font-weight:700;font-size:15px;background:linear-gradient(135deg,#8b1e3f,#b54a6a);cursor:pointer}
.msg{padding:12px;border-radius:10px;font-size:14px;margin-bottom:14px}
.err{background:#fee2e2;color:#991b1b}
.ok{background:#dcfce7;color:#166534}
.warn{background:#fef3e2;color:#92400e}
code{background:#f5f1ea;padding:2px 7px;border-radius:6px;font-size:13px}
a.btn{display:block;text-align:center;margin-top:16px;padding:12px;border-radius:10px;background:#8b1e3f;color:#fff;font-weight:700;text-decoration:none}
</style></head><body>
<div class="box">
<h1>تثبيت نظام المهام</h1>
<p class="sub">إعداد قاعدة البيانات وحساب المدير الأول.</p>

<?php if (!$connected): ?>
  <div class="msg err">تعذّر الاتصال بقاعدة البيانات. راجع <code>app/config.local.php</code> وتأكد من إنشاء القاعدة والمستخدم.</div>
<?php elseif ($step === 'locked'): ?>
  <div class="msg warn">النظام مُثبَّت مسبقاً. من فضلك احذف هذا الملف من الخادم:<br><code>rm ~/public_html/install.php</code></div>
  <a class="btn" href="index.php">الذهاب إلى النظام</a>
<?php elseif ($done): ?>
  <div class="msg ok">تم التثبيت بنجاح! لأمان النظام احذف الآن ملف التثبيت:<br><code>rm ~/public_html/install.php</code></div>
  <a class="btn" href="index.php">تسجيل الدخول</a>
<?php else: ?>
  <?php if ($error): ?><div class="msg err"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <label>اسم المدير</label>
    <input type="text" name="name" required>
    <label>اسم المستخدم للدخول</label>
    <input type="text" name="username" autocomplete="off" required>
    <label>البريد الإلكتروني (اختياري — لاستقبال إشعارات التعقيب)</label>
    <input type="email" name="email" autocomplete="off" placeholder="name@example.com">
    <label>كلمة المرور (٦ أحرف على الأقل)</label>
    <input type="password" name="password" minlength="6" required>
    <button type="submit">تثبيت وإنشاء حساب المدير</button>
  </form>
<?php endif; ?>
</div></body></html>
