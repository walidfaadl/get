<?php
declare(strict_types=1);

/** بدء جلسة آمنة. */
function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'secure'   => is_https(),
        'samesite' => 'Lax',
    ]);
    session_name('ttsid');
    session_start();
}

/** المستخدم الحالي أو null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_manager(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'manager';
}

function is_head(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'head';
}

function is_member(): bool
{
    $u = current_user();
    return $u !== null && $u['role'] === 'member';
}

/** من يستطيع إنشاء المواعيد: المدير ورؤساء الأقسام. */
function can_manage_appointments(): bool
{
    return is_manager() || is_head();
}

/** يتطلب تسجيل دخول، وإلا يحوّل لصفحة الدخول. */
function require_login(): void
{
    if (current_user() === null) {
        redirect(url('login'));
    }
}

/** يتطلب دور المدير. */
function require_manager(): void
{
    require_login();
    if (!is_manager()) {
        http_response_code(403);
        render('message', [
            'heading' => 'غير مصرّح',
            'text'    => 'هذه الصفحة مخصّصة للمدير فقط.',
        ], 'غير مصرّح');
        exit;
    }
}

/** محاولة تسجيل الدخول. */
function attempt_login(string $username, string $password): bool
{
    $u = q_one(
        'SELECT * FROM users WHERE username = ? AND active = 1 LIMIT 1',
        [$username]
    );
    if (!$u || !password_verify($password, $u['password_hash'])) {
        return false;
    }
    // ترقية التجزئة عند الحاجة
    if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
        q('UPDATE users SET password_hash = ? WHERE id = ?', [
            password_hash($password, PASSWORD_DEFAULT),
            $u['id'],
        ]);
    }
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id'         => (int) $u['id'],
        'name'       => $u['name'],
        'username'   => $u['username'],
        'role'       => $u['role'],
        'department' => $u['department'],
    ];
    return true;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ===== حماية CSRF ===== */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

/** يتحقق من رمز CSRF على الطلبات المتغيّرة، وإلا يوقف التنفيذ. */
function csrf_check(): void
{
    $sent = $_POST['_csrf'] ?? '';
    if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
        http_response_code(400);
        exit('رمز الحماية غير صالح، حدِّث الصفحة وحاول مجدداً.');
    }
}
