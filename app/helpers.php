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

/** هل المهمة متأخرة؟ (تجاوزت الاستحقاق ولمّا تُنجَز أو تُغلَق) */
function task_is_late(array $t): bool
{
    if (empty($t['due_date']) || in_array($t['status'] ?? '', ['تمت', 'لم تتم'], true)) {
        return false;
    }
    $due = strtotime($t['due_date']);
    return $due !== false && $due < strtotime('today');
}

/** مجموعة المهمة الزمنية: late|today|week|later|none */
function task_bucket(array $t): string
{
    if (task_is_late($t)) {
        return 'late';
    }
    if (empty($t['due_date'])) {
        return 'none';
    }
    $diff = (int) floor((strtotime($t['due_date']) - strtotime('today')) / 86400);
    if ($diff <= 0) {
        return 'today';
    }
    return $diff <= 7 ? 'week' : 'later';
}

/** تسمية الاستحقاق النسبية (اليوم/غدًا/أمس/تاريخ). */
function fmt_due_rel(array $t): string
{
    if (($t['status'] ?? '') === 'تمت') {
        return 'منجزة';
    }
    if (empty($t['due_date'])) {
        return '';
    }
    $months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    $ts = strtotime($t['due_date']);
    $diff = (int) floor(($ts - strtotime('today')) / 86400);
    if ($diff === 0) return 'اليوم';
    if ($diff === 1) return 'غدًا';
    if ($diff === -1) return 'أمس';
    return (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1];
}

/* ===== تنقية/تحويل نص المخرجات المنسّق (HTML) ===== */

/** الوسوم المسموح بها داخل نص المخرجات المنسّق. */
const OUTCOME_HTML_TAGS = ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 's', 'ul', 'ol', 'li', 'h3', 'h4', 'blockquote', 'a'];

/**
 * تنقية HTML قادم من محرّر النص الغني إلى مجموعة وسوم آمنة فقط،
 * مع إزالة كل السمات (عدا href آمن على الروابط) ومنع XSS.
 */
function sanitize_html(string $html): string
{
    $html = trim($html);
    if ($html === '') {
        return '';
    }
    if (strlen($html) > 80000) {
        $html = substr($html, 0, 80000);
    }
    // بديل آمن إن غابت إضافة DOM
    if (!class_exists('DOMDocument')) {
        $allowed = '<' . implode('><', OUTCOME_HTML_TAGS) . '>';
        return trim(strip_tags($html, $allowed));
    }
    $doc = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    // ميتا لضبط ترميز UTF-8 أثناء التحميل
    $doc->loadHTML(
        '<?xml encoding="UTF-8"><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><body>' . $html . '</body>',
        LIBXML_NOERROR | LIBXML_NOWARNING
    );
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    $body = $doc->getElementsByTagName('body')->item(0);
    if (!$body) {
        return '';
    }
    $out = '';
    foreach (iterator_to_array($body->childNodes) as $child) {
        $out .= tt_clean_node($child);
    }
    return trim($out);
}

/** تنقية عقدة DOM واحدة وإرجاع HTML نصّي آمن. */
function tt_clean_node(DOMNode $node): string
{
    if ($node->nodeType === XML_TEXT_NODE) {
        return e($node->nodeValue);
    }
    if ($node->nodeType !== XML_ELEMENT_NODE) {
        return ''; // تعليقات/عقد أخرى تُحذف
    }
    $tag = strtolower($node->nodeName);
    $inner = '';
    foreach (iterator_to_array($node->childNodes) as $child) {
        $inner .= tt_clean_node($child);
    }
    // تطبيع بعض الوسوم إلى المسموح
    if ($tag === 'div') { $tag = 'p'; }
    if ($tag === 'h1' || $tag === 'h2') { $tag = 'h3'; }
    if (!in_array($tag, OUTCOME_HTML_TAGS, true)) {
        return $inner; // وسم غير مسموح → فكّ التغليف مع إبقاء المحتوى
    }
    if ($tag === 'br') {
        return '<br>';
    }
    if ($tag === 'a') {
        $href = trim($node->getAttribute('href'));
        if ($href !== '' && preg_match('#^(https?:|mailto:)#i', $href)) {
            return '<a href="' . e($href) . '" target="_blank" rel="noopener nofollow">' . $inner . '</a>';
        }
        return $inner;
    }
    return '<' . $tag . '>' . $inner . '</' . $tag . '>';
}

/** هل نص المخرجات المنقّى فارغ فعلياً (بلا محتوى مرئي)؟ */
function html_is_blank(string $html): bool
{
    $t = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
    return $t === '' || preg_replace('/[\s\x{00A0}]+/u', '', $t) === '';
}

/** تحويل نص المخرجات المنسّق إلى نص عادي (للبريد). */
function html_to_text(string $html): string
{
    $html = preg_replace('#<\s*(br|/p|/li|/h3|/h4|/blockquote|/div)\s*/?>#i', "\n", $html);
    $html = preg_replace('#<\s*li[^>]*>#i', '• ', $html);
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    return trim((string) $text);
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
