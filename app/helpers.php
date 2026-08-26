<?php
declare(strict_types=1);

/* ===== الثوابت المرجعية ===== */
const PRIORITIES = ['عالية', 'متوسطة', 'منخفضة'];
const STATUSES   = ['جديدة', 'قيد التنفيذ', 'تمت', 'لم تتم'];
// حالات يستطيع مدير القسم ضبطها كتعقيب
const REPLY_STATUSES = ['قيد التنفيذ', 'تمت', 'لم تتم'];
const ROLES = ['manager' => 'المدير', 'head' => 'مدير القسم', 'member' => 'عضو قسم'];
// حالات الموعد
const APPT_STATUSES = ['مجدول', 'تم', 'تأجّل', 'لم يُعقد'];

/** ترميز آمن للإخراج داخل HTML. */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** هل الطلب عبر HTTPS (يراعي وجود Cloudflare أمام الخادم). */
function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    return strtolower((string) $proto) === 'https';
}

/** عنوان الزائر الحقيقي خلف Cloudflare. */
function client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
        if (!empty($_SERVER[$k])) {
            return explode(',', (string) $_SERVER[$k])[0];
        }
    }
    return '';
}

/** رابط داخلي إلى مسار في التطبيق. */
function url(string $route = 'tasks', array $params = []): string
{
    $q = array_merge(['r' => $route], $params);
    return 'index.php?' . http_build_query($q);
}

/** إعادة توجيه ثم إنهاء. */
function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

/** رسالة فلاش تُعرض مرة واحدة بعد إعادة التوجيه. */
function flash(?string $msg = null, string $type = 'ok'): ?array
{
    if ($msg !== null) {
        $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
        return null;
    }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** تنسيق تاريخ ميلادي عربي مبسّط. */
function fmt_date(?string $d): string
{
    if (!$d) {
        return '—';
    }
    $ts = strtotime($d);
    if ($ts === false) {
        return $d;
    }
    $months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

/** تنسيق تاريخ ووقت. */
function fmt_dt(?string $d): string
{
    if (!$d) {
        return '—';
    }
    $ts = strtotime($d);
    return $ts === false ? $d : date('Y-m-d H:i', $ts);
}

/** قيمة من مصفوفة السماح أو الافتراضي. */
function pick(string $val, array $allowed, string $default): string
{
    return in_array($val, $allowed, true) ? $val : $default;
}

/** اسم CSS لاتيني للحالة (تجنّب المسافات في أسماء الأصناف). */
function status_slug(string $s): string
{
    return ['جديدة' => 'new', 'قيد التنفيذ' => 'prog', 'تمت' => 'done', 'لم تتم' => 'fail'][$s] ?? 'new';
}

/** اسم CSS لاتيني للأولوية. */
function prio_slug(string $s): string
{
    return ['عالية' => 'high', 'متوسطة' => 'mid', 'منخفضة' => 'low'][$s] ?? 'mid';
}

/** اسم CSS لاتيني لحالة الموعد. */
function appt_status_slug(string $s): string
{
    return ['مجدول' => 'sched', 'تم' => 'done', 'تأجّل' => 'post', 'لم يُعقد' => 'noshow'][$s] ?? 'sched';
}

/** عرض قالب داخل التخطيط العام. */
function render(string $view, array $data = [], ?string $title = null): void
{
    $data['__title'] = $title ?? APP_NAME;
    extract($data, EXTR_SKIP);
    $__view_file = dirname(__DIR__) . '/views/' . $view . '.php';
    ob_start();
    require $__view_file;
    $content = ob_get_clean();
    require dirname(__DIR__) . '/views/layout.php';
}
