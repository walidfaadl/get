<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

// إن لم تُهيّأ قاعدة البيانات بعد، وجّه إلى المُثبِّت.
try {
    db();
    run_migrations();
} catch (Throwable $ex) {
    if (is_file(__DIR__ . '/install.php')) {
        redirect('install.php');
    }
    http_response_code(500);
    exit('تعذّر الاتصال بقاعدة البيانات. ' . (APP_DEBUG ? e($ex->getMessage()) : 'راجع إعدادات app/config.local.php.'));
}

$r      = (string) ($_GET['r'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];
$isPost = $method === 'POST';

/** الصفحة الرئيسية بحسب الدور: المدير → الرئيسية (الإحصائيات)، غيره → المهام. */
$home_route = fn() => is_manager() ? 'stats' : 'tasks';

/* ---------- مسارات عامة ---------- */
if ($r === 'login') {
    // الوجهة بعد الدخول: الرابط المقصود المحفوظ (مشاركة مثلاً) أو الصفحة الرئيسية
    $afterLogin = function () use ($home_route) {
        $next = $_SESSION['after_login'] ?? '';
        unset($_SESSION['after_login']);
        return (is_string($next) && is_safe_return_path($next)) ? $next : url($home_route());
    };
    if (current_user()) {
        redirect($afterLogin());
    }
    $error = null;
    if ($isPost) {
        csrf_check();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $u = auth_verify_credentials($username, $password);
        if ($u) {
            if (user_needs_2fa($u)) {
                // خطوة ثانية: احفظ المستخدم المعلّق والوجهة، ووجّه لصفحة الرمز
                $_SESSION['pending_2fa']   = (int) $u['id'];
                $_SESSION['pending_after'] = $_SESSION['after_login'] ?? '';
                $_SESSION['twofa_tries']   = 0;
                unset($_SESSION['after_login']);
                redirect(url('2fa'));
            }
            auth_login_user($u);
            redirect($afterLogin());
        }
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
    render('login', ['error' => $error], 'تسجيل الدخول');
    exit;
}

/* خطوة التحقق بخطوتين بعد كلمة المرور (قبل تسجيل الدخول الكامل) */
if ($r === '2fa') {
    $pid = (int) ($_SESSION['pending_2fa'] ?? 0);
    if ($pid <= 0) {
        redirect(url('login'));
    }
    $row = user_get($pid);
    if (!$row || !user_needs_2fa($row)) {
        unset($_SESSION['pending_2fa'], $_SESSION['pending_after']);
        redirect(url('login'));
    }
    $error = null;
    if ($isPost) {
        csrf_check();
        $_SESSION['twofa_tries'] = (int) ($_SESSION['twofa_tries'] ?? 0) + 1;
        if ($_SESSION['twofa_tries'] > 8) {
            unset($_SESSION['pending_2fa'], $_SESSION['pending_after'], $_SESSION['twofa_tries']);
            flash('محاولات كثيرة. سجّل الدخول من جديد.', 'err');
            redirect(url('login'));
        }
        $code = trim((string) ($_POST['code'] ?? ''));
        $ok = false;
        if (preg_match('/^\d{6}$/', $code)) {
            $ok = totp_verify((string) $row['totp_secret'], $code);
        }
        if (!$ok && strpbrk($code, '-') !== false) {
            $ok = user_consume_recovery($pid, $code);
        }
        if ($ok) {
            $after = (string) ($_SESSION['pending_after'] ?? '');
            auth_login_user($row);
            redirect(is_safe_return_path($after) ? $after : url(is_manager() ? 'stats' : 'tasks'));
        }
        $error = 'الرمز غير صحيح. تحقّق من تطبيق المصادقة وحاول مجدداً.';
    }
    render('twofa', ['error' => $error], 'التحقق بخطوتين');
    exit;
}

if ($r === 'logout') {
    logout();
    redirect(url('login'));
}

/* صفحة موعد عامة عبر رابط المشاركة — بلا تسجيل دخول، للقراءة فقط */
if ($r === 'share') {
    $appt = appointment_get_by_token((string) ($_GET['t'] ?? ''));
    require __DIR__ . '/views/share.php';
    exit;
}

/* ملف تقويم .ics للموعد عبر رابط المشاركة — بلا تسجيل دخول */
if ($r === 'ics') {
    $appt = appointment_get_by_token((string) ($_GET['t'] ?? ''));
    if (!$appt) {
        http_response_code(404);
        exit('غير موجود');
    }
    $start = strtotime($appt['starts_at']) ?: time();
    $end   = $start + 3600;
    $esc = fn($s) => preg_replace('/([,;\\\\])/', '\\\\$1', str_replace(["\r\n", "\n", "\r"], '\\n', (string) $s));
    $host = $_SERVER['HTTP_HOST'] ?? 'tasktrak.co';
    $desc = trim(($appt['with_whom'] ? 'مع: ' . $appt['with_whom'] . '\\n' : '') . (string) ($appt['notes'] ?? ''));
    $lines = [
        'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//TaskTrak//AR//', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' . $appt['share_token'] . '@' . $host,
        'DTSTAMP:' . gmdate('Ymd\THis\Z'),
        'DTSTART:' . date('Ymd\THis', $start),
        'DTEND:' . date('Ymd\THis', $end),
        'SUMMARY:' . $esc($appt['subject']),
    ];
    if ($desc !== '') { $lines[] = 'DESCRIPTION:' . $esc($desc); }
    if (!empty($appt['location'])) { $lines[] = 'LOCATION:' . $esc($appt['location']); }
    $lines[] = 'END:VEVENT';
    $lines[] = 'END:VCALENDAR';
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="appointment.ics"');
    echo implode("\r\n", $lines) . "\r\n";
    exit;
}

/* ردّ صاحب الموعد من الصفحة العامة (تأكيد/طلب تأجيل) */
if ($r === 'share_respond') {
    $token = (string) ($_POST['t'] ?? '');
    $appt  = appointment_get_by_token($token);
    if ($appt && $isPost) {
        csrf_check();
        $action = (string) ($_POST['action'] ?? '');
        $status = $action === 'postpone' ? 'postpone' : 'confirmed';
        $note   = trim((string) ($_POST['note'] ?? ''));
        appointment_record_response((int) $appt['id'], $status, $note);
        if (!empty($appt['created_by'])) {
            $t = $status === 'confirmed' ? 'أكّد صاحب الموعد الاستلام' : 'طلب صاحب الموعد تأجيل الموعد';
            notif_add((int) $appt['created_by'], 'appointment', $t, $appt['subject'], 'appt', (int) $appt['id']);
        }
        notify_appointment_response((int) $appt['id']);
        flash($status === 'confirmed' ? 'شكراً — تم تأكيد استلامك للموعد.' : 'تم إرسال طلب التأجيل إلى منظّم الموعد.');
    }
    redirect(url('share', ['t' => $token]));
}

/* Webhook تيليجرام — عام، لاستقبال تحديثات البوت وربط الحسابات */
if ($r === 'tg_webhook') {
    header('Content-Type: application/json; charset=utf-8');
    // تأمين: سِرّ عبر ترويسة تيليجرام أو معامل s
    $hdr = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    $qs  = (string) ($_GET['s'] ?? '');
    if (TG_WEBHOOK_SECRET !== '' && !hash_equals(TG_WEBHOOK_SECRET, (string) $hdr) && !hash_equals(TG_WEBHOOK_SECRET, $qs)) {
        http_response_code(403);
        echo '{"ok":false}';
        exit;
    }
    $raw = file_get_contents('php://input') ?: '';
    $upd = json_decode($raw, true);
    $msg = is_array($upd) ? ($upd['message'] ?? null) : null;
    if ($msg && isset($msg['chat']['id'])) {
        $chatId = (string) $msg['chat']['id'];
        $text   = trim((string) ($msg['text'] ?? ''));
        if (preg_match('/^\/start\s+(\S+)/', $text, $m)) {
            $uid = user_link_telegram_by_token($m[1], $chatId);
            if ($uid > 0) {
                $u = user_get($uid);
                tg_send($chatId, 'تم ربط حسابك (' . ($u['name'] ?? '') . ') بنجاح ✅ — ستصلك تذكيرات المواعيد هنا.');
            } else {
                tg_send($chatId, 'تعذّر الربط: الرمز غير صالح أو منتهٍ. أعِد المحاولة من صفحة «حسابي».');
            }
        } elseif ($text === '/start') {
            tg_send($chatId, 'مرحباً بك في ' . APP_NAME . '. لربط حسابك، استخدم زرّ «ربط تيليجرام» من صفحة «حسابي».');
        }
    }
    echo '{"ok":true}';
    exit;
}

/* ---------- تتطلب تسجيل دخول ---------- */
require_login();
$me = current_user();
if ($r === '') {
    $r = $home_route();
}

switch ($r) {
    /* ===== قائمة المهام ===== */
    case 'tasks': {
        $rawStatus = (string) ($_GET['status'] ?? '');
        $late   = $rawStatus === 'late';
        $status = $late ? '' : pick($rawStatus, STATUSES, '');
        $search = trim((string) ($_GET['q'] ?? ''));
        $assignee = (int) ($_GET['assignee'] ?? 0);
        $sort   = pick((string) ($_GET['sort'] ?? 'recent'), ['recent', 'due', 'priority'], 'recent');

        $filter = ['sort' => $sort];
        if ($status !== '')   { $filter['status'] = $status; }
        if ($late)            { $filter['late'] = true; }
        if ($search !== '')   { $filter['search'] = $search; }
        if ($assignee > 0)    { $filter['assignee'] = $assignee; }

        $scope = null;
        if (!is_manager()) {
            $scope = scope_for_user($me);
            $filter['assignee_scope'] = $scope;
        }
        $counts = task_counts($scope);
        // عدّ المتأخرة ضمن النطاق
        $lateFilter = ['late' => true];
        if ($scope) { $lateFilter['assignee_scope'] = $scope; }
        $counts['late'] = count(tasks_list($lateFilter));

        render('tasks_list', [
            'tasks'      => tasks_list($filter),
            'counts'     => $counts,
            'status'     => $late ? 'late' : $status,
            'search'     => $search,
            'sort'       => $sort,
            'assignee'   => $assignee,
            'assignable' => is_manager() ? users_assignable() : [],
        ], 'المهام');
        break;
    }

    /* ===== تفاصيل مهمة ===== */
    case 'task': {
        $id   = (int) ($_GET['id'] ?? 0);
        $task = task_get($id);
        if (!$task) {
            http_response_code(404);
            render('message', ['heading' => 'غير موجودة', 'text' => 'المهمة غير موجودة.'], 'خطأ');
            break;
        }
        $token    = (string) ($_GET['t'] ?? '');
        $inScope  = task_in_scope($task, $me);
        $viaShare = $token !== '' && task_token_matches($task, $token);
        if (!$inScope && !$viaShare) {
            http_response_code(403);
            render('message', ['heading' => 'غير مصرّح', 'text' => 'هذه المهمة خارج نطاق قسمك.'], 'غير مصرّح');
            break;
        }
        // رابط المشاركة يظهر للمدير أو منشئ المهمة
        $canShare = is_manager() || (int) ($task['created_by'] ?? 0) === (int) $me['id'];
        render('task_detail', [
            'task'       => $task,
            'comments'   => comments_for($id),
            'canAct'     => $inScope,
            'viaShare'   => $viaShare && !$inScope,
            'shareToken' => $canShare ? task_ensure_token($id) : null,
        ], $task['title']);
        break;
    }

    /* ===== نموذج إنشاء/تعديل (المدير) ===== */
    case 'new':
    case 'edit': {
        require_manager();
        $task = null;
        if ($r === 'edit') {
            $task = task_get((int) ($_GET['id'] ?? 0));
            if (!$task) {
                redirect(url('tasks'));
            }
        }
        render('task_form', [
            'task'       => $task,
            'assignable' => users_assignable(),
        ], $task ? 'تعديل مهمة' : 'مهمة جديدة');
        break;
    }

    /* ===== حفظ مهمة (المدير) ===== */
    case 'save': {
        require_manager();
        if (!$isPost) {
            redirect(url('tasks'));
        }
        csrf_check();
        $id   = (int) ($_POST['id'] ?? 0);
        $data = [
            'title'        => trim((string) ($_POST['title'] ?? '')),
            'details'      => trim((string) ($_POST['details'] ?? '')),
            'department'   => trim((string) ($_POST['department'] ?? '')),
            'priority'     => (string) ($_POST['priority'] ?? 'متوسطة'),
            'due_date'     => trim((string) ($_POST['due_date'] ?? '')),
            'manager_note' => trim((string) ($_POST['manager_note'] ?? '')),
            'assigned_to'  => (int) ($_POST['assigned_to'] ?? 0) ?: null,
        ];
        if ($data['title'] === '' || $data['details'] === '') {
            flash('العنوان والتفاصيل مطلوبان.', 'err');
            redirect($id ? url('edit', ['id' => $id]) : url('new'));
        }
        if ($id) {
            $before = task_get($id);
            task_update($id, $data);
            // إشعار عند إسناد المهمة إلى مكلَّف جديد
            if ($data['assigned_to'] && (int) ($before['assigned_to'] ?? 0) !== (int) $data['assigned_to']) {
                notify_task_assigned($id, (int) $data['assigned_to']);
                notif_add((int) $data['assigned_to'], 'task', 'مهمة مسندة إليك', $data['title'], 'task', $id);
            }
            flash('تم تحديث المهمة.');
            redirect(url('task', ['id' => $id]));
        } else {
            $data['created_by'] = $me['id'];
            $newId = task_create($data);
            if ($data['assigned_to']) {
                notify_task_assigned($newId, (int) $data['assigned_to']);
                notif_add((int) $data['assigned_to'], 'task', 'مهمة جديدة مسندة إليك', $data['title'], 'task', $newId);
            }
            flash('تمت إضافة المهمة.');
            redirect(url('task', ['id' => $newId]));
        }
        break;
    }

    /* ===== حذف مهمة (المدير) ===== */
    case 'delete': {
        require_manager();
        if ($isPost) {
            csrf_check();
            task_delete((int) ($_POST['id'] ?? 0));
            flash('تم حذف المهمة.');
        }
        redirect(url('tasks'));
        break;
    }

    /* ===== تعقيب مدير القسم ===== */
    case 'reply': {
        if (!$isPost) {
            redirect(url('tasks'));
        }
        csrf_check();
        if (is_manager()) {
            http_response_code(403);
            exit('التعقيب مخصّص لمدير القسم.');
        }
        $id     = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        $reply  = trim((string) ($_POST['reply'] ?? ''));
        $task   = task_get($id);
        if (!$task || !task_in_scope($task, $me)) {
            http_response_code(403);
            exit('هذه المهمة خارج نطاق قسمك.');
        }
        if (!in_array($status, REPLY_STATUSES, true)) {
            flash('اختر حالة المهمة (تمت / قيد التنفيذ / لم تتم).', 'err');
            redirect(url('task', ['id' => $id]));
        }
        task_reply($id, $status, $reply, $me['name']);
        notify_task_replied($id);
        flash('تم حفظ التعقيب.');
        redirect(url('task', ['id' => $id]));
        break;
    }

    /* ===== المدير يعدّل حالة المهمة مباشرةً ===== */
    case 'task_status': {
        require_manager();
        if (!$isPost) {
            redirect(url('tasks'));
        }
        csrf_check();
        $id     = (int) ($_POST['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');
        if (!task_get($id) || !in_array($status, STATUSES, true)) {
            flash('حالة غير صالحة.', 'err');
            redirect(url('task', ['id' => $id]));
        }
        task_set_status($id, $status);
        flash('تم تحديث حالة المهمة.');
        redirect(url('task', ['id' => $id]));
        break;
    }

    /* ===== إضافة تعليق (الطرفان) ===== */
    case 'comment': {
        if (!$isPost) {
            redirect(url('tasks'));
        }
        csrf_check();
        $id   = (int) ($_POST['id'] ?? 0);
        $body = trim((string) ($_POST['body'] ?? ''));
        $task = task_get($id);
        if (!$task || !task_in_scope($task, $me)) {
            http_response_code(403);
            exit('هذه المهمة خارج نطاق قسمك.');
        }
        if ($body !== '') {
            comment_add($id, $me, $body);
            flash('تمت إضافة الملاحظة.');
        }
        redirect(url('task', ['id' => $id]) . '#comments');
        break;
    }

    /* ===== الرئيسية / لوحة الإحصائيات (المدير) ===== */
    case 'stats': {
        require_manager();
        render('stats', [
            'counts'       => task_counts(),
            'byDept'       => stats_by_department(),
            'byAssignee'   => stats_by_assignee(),
            'overdue'      => overdue_tasks(),
            'recentTasks'  => recent_tasks(3),
            'upcomingAppts' => upcoming_scheduled_appointments(3),
        ], 'الرئيسية');
        break;
    }

    /* ===== المواعيد + التقويم (صفحة واحدة) ===== */
    case 'appointments': {
        if (!can_manage_appointments()) {
            redirect(url('tasks'));
        }
        $now = time();
        $y = (int) ($_GET['y'] ?? 0);
        $m = (int) ($_GET['m'] ?? 0);
        if ($m < 1 || $m > 12) { $m = (int) date('n', $now); }
        if ($y < 2000 || $y > 2100) { $y = (int) date('Y', $now); }

        $all = appointments_for($me);
        $upcoming = array_values(array_filter($all, fn($a) => strtotime($a['starts_at']) >= $now));
        // تجميع المواعيد حسب اليوم للتقويم
        $byDay = [];
        foreach ($all as $a) {
            $ts = strtotime($a['starts_at']);
            if ($ts) { $byDay[date('Y-m-d', $ts)][] = $a; }
        }
        render('agenda', [
            'upcoming' => $upcoming,
            'y'        => $y,
            'm'        => $m,
            'byDay'    => $byDay,
        ], 'المواعيد');
        break;
    }

    /* توافق خلفي: التقويم أصبح جزءاً من صفحة المواعيد */
    case 'calendar': {
        $p = [];
        if (isset($_GET['y'])) { $p['y'] = (int) $_GET['y']; }
        if (isset($_GET['m'])) { $p['m'] = (int) $_GET['m']; }
        redirect(url('appointments', $p));
        break;
    }

    case 'appt': {
        if (!can_manage_appointments()) {
            redirect(url('tasks'));
        }
        $appt = appointment_get((int) ($_GET['id'] ?? 0));
        if (!$appt || !appointment_in_scope($appt, $me)) {
            http_response_code($appt ? 403 : 404);
            render('message', ['heading' => 'غير متاح', 'text' => 'الموعد غير موجود أو خارج نطاقك.'], 'خطأ');
            break;
        }
        // ضمان مُعرّف مشاركة لعرض رابط المشاركة (لمن يملك تعديل الموعد)
        $shareToken = appointment_can_edit($appt, $me) ? appointment_ensure_token((int) $appt['id']) : null;
        render('appt_detail', ['appt' => $appt, 'shareToken' => $shareToken], $appt['subject']);
        break;
    }

    case 'appt_new':
    case 'appt_edit': {
        if (!can_manage_appointments()) {
            redirect(url('tasks'));
        }
        $appt = null;
        $prefill = '';
        if ($r === 'appt_edit') {
            $appt = appointment_get((int) ($_GET['id'] ?? 0));
            if (!$appt || !appointment_can_edit($appt, $me)) {
                redirect(url('appointments'));
            }
        } else {
            // تاريخ ممرّر من التقويم (YYYY-MM-DD) → يُعبّأ مسبقاً بوقت افتراضي
            $d = trim((string) ($_GET['date'] ?? ''));
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false) {
                $prefill = $d . 'T09:00';
            }
        }
        render('appt_form', [
            'appt'    => $appt,
            'heads'   => users_heads(),
            'prefill' => $prefill,
        ], $appt ? 'تعديل موعد' : 'موعد جديد');
        break;
    }

    case 'appt_save': {
        if (!can_manage_appointments() || !$isPost) {
            redirect(url('appointments'));
        }
        csrf_check();
        $id   = (int) ($_POST['id'] ?? 0);
        $data = [
            'subject'     => trim((string) ($_POST['subject'] ?? '')),
            'with_whom'   => trim((string) ($_POST['with_whom'] ?? '')),
            'starts_at'   => trim((string) ($_POST['starts_at'] ?? '')),
            'location'    => trim((string) ($_POST['location'] ?? '')),
            'notes'       => trim((string) ($_POST['notes'] ?? '')),
            'shared_with' => (int) ($_POST['shared_with'] ?? 0) ?: null,
        ];
        // datetime-local يصل بصيغة 2026-01-01T09:00 — نحوّلها إلى صيغة SQL
        $data['starts_at'] = str_replace('T', ' ', $data['starts_at']);
        if ($data['subject'] === '' || $data['starts_at'] === '') {
            flash('الموضوع والتاريخ/الوقت مطلوبان.', 'err');
            redirect($id ? url('appt_edit', ['id' => $id]) : url('appt_new'));
        }
        if (strlen($data['starts_at']) === 16) {
            $data['starts_at'] .= ':00';
        }
        if ($id) {
            $appt = appointment_get($id);
            if (!$appt || !appointment_can_edit($appt, $me)) {
                redirect(url('appointments'));
            }
            $before = (int) ($appt['shared_with'] ?? 0);
            appointment_update($id, $data);
            if ($data['shared_with'] && $data['shared_with'] !== $before) {
                notify_appointment_shared($id, (int) $data['shared_with']);
                notif_add((int) $data['shared_with'], 'appointment', 'موعد بمشاركتك', $data['subject'], 'appt', $id);
            }
            flash('تم تحديث الموعد.');
        } else {
            $data['created_by'] = $me['id'];
            $id = appointment_create($data);
            if ($data['shared_with']) {
                notify_appointment_shared($id, (int) $data['shared_with']);
                notif_add((int) $data['shared_with'], 'appointment', 'موعد جديد بمشاركتك', $data['subject'], 'appt', $id);
            }
            flash('تمت إضافة الموعد.');
        }
        redirect(url('appointments'));
        break;
    }

    case 'appt_delete': {
        if (can_manage_appointments() && $isPost) {
            csrf_check();
            $appt = appointment_get((int) ($_POST['id'] ?? 0));
            if ($appt && appointment_can_edit($appt, $me)) {
                appointment_delete((int) $appt['id']);
                flash('تم حذف الموعد.');
            }
        }
        redirect(url('appointments'));
        break;
    }

    case 'appt_status': {
        if (!can_manage_appointments() || !$isPost) {
            redirect(url('appointments'));
        }
        csrf_check();
        $id   = (int) ($_POST['id'] ?? 0);
        $appt = appointment_get($id);
        if (!$appt || !appointment_can_edit($appt, $me)) {
            redirect(url('appointments'));
        }
        $status = (string) ($_POST['status'] ?? '');
        if (!in_array($status, APPT_STATUSES, true)) {
            flash('حالة غير صالحة.', 'err');
            redirect(url('appt', ['id' => $id]));
        }
        $pt = trim((string) ($_POST['postponed_to'] ?? ''));
        $pt = $pt !== '' ? str_replace('T', ' ', $pt) : '';
        if ($pt !== '' && strlen($pt) === 16) {
            $pt .= ':00';
        }
        appointment_set_status($id, $status, $pt ?: null);
        flash('تم تحديث حالة الموعد.');
        redirect(url('appt', ['id' => $id]));
        break;
    }

    /* ===== مخرجات اللقاءات ===== */
    case 'outcomes': {
        render('outcomes', [
            'outcomes' => outcomes_for($me),
            'canCreate' => can_manage_appointments(),
        ], 'المخرجات');
        break;
    }

    case 'outcome': {
        $id = (int) ($_GET['id'] ?? 0);
        $o  = outcome_get($id);
        if (!$o) {
            http_response_code(404);
            render('message', ['heading' => 'غير متاح', 'text' => 'المخرجات غير موجودة.'], 'خطأ');
            break;
        }
        // رابط المشاركة: يتطلّب تسجيل دخول (المستخدم مُسجَّل أصلاً هنا) ويمنح الاطّلاع
        $token    = (string) ($_GET['t'] ?? '');
        $viaShare = $token !== '' && outcome_token_matches($o, $token);
        if (!$viaShare && !outcome_in_scope($o, $me)) {
            http_response_code(403);
            render('message', ['heading' => 'غير متاح', 'text' => 'المخرجات خارج نطاقك.'], 'خطأ');
            break;
        }
        $canEdit = outcome_can_edit($o, $me);
        render('outcome_detail', [
            'o'          => $o,
            'attendees'  => outcome_attendees($id),
            'canEdit'    => $canEdit,
            'viaShare'   => $viaShare && !outcome_in_scope($o, $me),
            'shareToken' => $canEdit ? outcome_ensure_token($id) : null,
        ], $o['title']);
        break;
    }

    case 'outcome_new':
    case 'outcome_edit': {
        if (!can_manage_appointments()) {
            redirect(url('outcomes'));
        }
        $o = null;
        $selected = [];
        if ($r === 'outcome_edit') {
            $o = outcome_get((int) ($_GET['id'] ?? 0));
            if (!$o || !outcome_can_edit($o, $me)) {
                redirect(url('outcomes'));
            }
            $selected = outcome_attendee_ids((int) $o['id']);
        }
        render('outcome_form', [
            'o'        => $o,
            'users'    => users_active(),
            'selected' => $selected,
        ], $o ? 'تعديل مخرجات' : 'مخرجات جديدة');
        break;
    }

    case 'outcome_save': {
        if (!can_manage_appointments() || !$isPost) {
            redirect(url('outcomes'));
        }
        csrf_check();
        $id = (int) ($_POST['id'] ?? 0);
        $metAt = trim((string) ($_POST['met_at'] ?? ''));
        $metAt = str_replace('T', ' ', $metAt);
        if ($metAt !== '' && strlen($metAt) === 16) {
            $metAt .= ':00';
        }
        $foAt = trim((string) ($_POST['followup_at'] ?? ''));
        $foAt = str_replace('T', ' ', $foAt);
        if ($foAt !== '' && strlen($foAt) === 16) {
            $foAt .= ':00';
        }
        $bodyClean = sanitize_html((string) ($_POST['body'] ?? ''));
        $data = [
            'title'           => trim((string) ($_POST['title'] ?? '')),
            'met_at'          => $metAt,
            'body'            => $bodyClean,
            'followup_agreed' => ((string) ($_POST['followup'] ?? '')) === 'yes',
            'followup_at'     => $foAt,
        ];
        $attendeeIds = array_map('intval', (array) ($_POST['attendees'] ?? []));

        if ($data['title'] === '' || $data['met_at'] === '') {
            flash('العنوان وموعد اللقاء مطلوبان.', 'err');
            redirect($id ? url('outcome_edit', ['id' => $id]) : url('outcome_new'));
        }
        if (html_is_blank($bodyClean)) {
            flash('نص المخرجات مطلوب.', 'err');
            redirect($id ? url('outcome_edit', ['id' => $id]) : url('outcome_new'));
        }

        if ($id) {
            $o = outcome_get($id);
            if (!$o || !outcome_can_edit($o, $me)) {
                redirect(url('outcomes'));
            }
            outcome_update($id, $data, $attendeeIds);
            flash('تم تحديث المخرجات.');
        } else {
            $data['created_by'] = $me['id'];
            $id = outcome_create($data, $attendeeIds);
            flash('تمت إضافة المخرجات.');
        }
        redirect(url('outcome', ['id' => $id]));
        break;
    }

    case 'outcome_delete': {
        if (can_manage_appointments() && $isPost) {
            csrf_check();
            $o = outcome_get((int) ($_POST['id'] ?? 0));
            if ($o && outcome_can_edit($o, $me)) {
                outcome_delete((int) $o['id']);
                flash('تم حذف المخرجات.');
            }
        }
        redirect(url('outcomes'));
        break;
    }

    /* إرسال المخرجات إلى الحاضرين للمتابعة */
    case 'outcome_send': {
        if (!can_manage_appointments() || !$isPost) {
            redirect(url('outcomes'));
        }
        csrf_check();
        $id = (int) ($_POST['id'] ?? 0);
        $o  = outcome_get($id);
        if (!$o || !outcome_can_edit($o, $me)) {
            redirect(url('outcomes'));
        }
        $attendees = outcome_attendees($id);
        if (!$attendees) {
            flash('أضِف الحاضرين أولاً قبل الإرسال.', 'err');
            redirect(url('outcome', ['id' => $id]));
        }
        $sentMail = notify_outcome_sent($id);
        outcome_mark_sent($id);
        flash('تم إرسال المخرجات إلى ' . count($attendees) . ' من الحاضرين' . ($sentMail ? " (بريد: $sentMail)" : '') . '.');
        redirect(url('outcome', ['id' => $id]));
        break;
    }

    /* ===== التنبيهات (لكل المستخدمين) ===== */
    case 'notifications': {
        $list = notifs_for((int) $me['id']);
        notifs_mark_all_read((int) $me['id']);
        render('notifications', ['items' => $list], 'التنبيهات');
        break;
    }

    /* ===== إدارة المستخدمين (المدير) ===== */
    case 'users': {
        require_manager();
        render('users', ['users' => users_all()], 'المستخدمون');
        break;
    }

    case 'user_save': {
        require_manager();
        if (!$isPost) {
            redirect(url('users'));
        }
        csrf_check();
        $id       = (int) ($_POST['id'] ?? 0);
        $name     = trim((string) ($_POST['name'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $email    = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $role     = pick((string) ($_POST['role'] ?? 'head'), ['manager', 'head'], 'head');
        $dept     = trim((string) ($_POST['department'] ?? ''));
        $active   = isset($_POST['active']) ? 1 : 0;

        if ($name === '' || $username === '') {
            flash('الاسم واسم المستخدم مطلوبان.', 'err');
            redirect(url('users'));
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('صيغة البريد الإلكتروني غير صحيحة.', 'err');
            redirect(url('users'));
        }
        if (username_exists($username, $id)) {
            flash('اسم المستخدم مستخدَم بالفعل.', 'err');
            redirect(url('users'));
        }
        if (!$id && $password === '') {
            flash('كلمة المرور مطلوبة للحساب الجديد.', 'err');
            redirect(url('users'));
        }
        $d = compact('name', 'username', 'email', 'password', 'role', 'active');
        $d['department'] = $dept;
        if ($id) {
            // منع تعطيل آخر مدير
            $target = user_get($id);
            if ($target && $target['role'] === 'manager' && ($role !== 'manager' || !$active) && managers_count() <= 1) {
                flash('لا يمكن تعطيل أو تنزيل آخر حساب مدير.', 'err');
                redirect(url('users'));
            }
            user_update($id, $d);
            flash('تم تحديث المستخدم.');
        } else {
            user_create($d);
            flash('تمت إضافة المستخدم.');
        }
        redirect(url('users'));
        break;
    }

    case 'user_delete': {
        require_manager();
        if ($isPost) {
            csrf_check();
            $id     = (int) ($_POST['id'] ?? 0);
            $target = user_get($id);
            if ($id === (int) $me['id']) {
                flash('لا يمكنك حذف حسابك الحالي.', 'err');
            } elseif ($target && $target['role'] === 'manager' && managers_count() <= 1) {
                flash('لا يمكن حذف آخر حساب مدير.', 'err');
            } else {
                user_delete($id);
                flash('تم حذف المستخدم.');
            }
        }
        redirect(url('users'));
        break;
    }

    /* ===== حسابي / تغيير كلمة المرور ===== */
    case 'account': {
        if ($isPost) {
            csrf_check();
            $cur = (string) ($_POST['current'] ?? '');
            $new = (string) ($_POST['new'] ?? '');
            $cf  = (string) ($_POST['confirm'] ?? '');
            $row = user_get((int) $me['id']);
            if (!$row || !password_verify($cur, $row['password_hash'])) {
                flash('كلمة المرور الحالية غير صحيحة.', 'err');
            } elseif (strlen($new) < 6) {
                flash('كلمة المرور الجديدة قصيرة (٦ أحرف على الأقل).', 'err');
            } elseif ($new !== $cf) {
                flash('كلمتا المرور غير متطابقتين.', 'err');
            } else {
                user_set_password((int) $me['id'], $new);
                flash('تم تغيير كلمة المرور.');
            }
            redirect(url('account'));
        }
        $row = user_get((int) $me['id']);
        $tgConfigured = tg_configured();
        $tgLinked = !empty($row['tg_chat_id']);
        $tgLinkUrl = '';
        if ($tgConfigured && !$tgLinked && TG_BOT_USERNAME !== '') {
            $tgLinkUrl = tg_link_url(user_tg_link_token((int) $me['id']));
        }
        render('account', [
            'row'          => $row,
            'twoFaOn'      => (int) ($row['totp_enabled'] ?? 0) === 1,
            'recoveryLeft' => recovery_remaining($row['totp_recovery'] ?? null),
            'waConfigured' => wa_configured(),
            'tgConfigured' => $tgConfigured,
            'tgLinked'     => $tgLinked,
            'tgLinkUrl'    => $tgLinkUrl,
        ], 'حسابي');
        break;
    }

    /* ===== بدء إعداد التحقق بخطوتين ===== */
    case 'tfa_setup': {
        $row = user_get((int) $me['id']);
        if ($row && (int) $row['totp_enabled'] === 1) {
            redirect(url('account'));
        }
        // سرّ مؤقّت في الجلسة حتى التأكيد
        if (empty($_SESSION['tfa_setup_secret'])) {
            $_SESSION['tfa_setup_secret'] = totp_new_secret();
        }
        $secret = $_SESSION['tfa_setup_secret'];
        render('tfa_setup', [
            'secret' => $secret,
            'uri'    => totp_uri($secret, $me['username'], APP_ORG),
        ], 'تفعيل التحقق بخطوتين');
        break;
    }

    /* تأكيد وتفعيل التحقق بخطوتين */
    case 'tfa_enable': {
        if (!$isPost) {
            redirect(url('tfa_setup'));
        }
        csrf_check();
        $secret = (string) ($_SESSION['tfa_setup_secret'] ?? '');
        $code   = trim((string) ($_POST['code'] ?? ''));
        if ($secret === '') {
            redirect(url('tfa_setup'));
        }
        if (!totp_verify($secret, $code)) {
            flash('الرمز غير صحيح. تأكّد من المسح الصحيح وحاول مجدداً.', 'err');
            redirect(url('tfa_setup'));
        }
        $codes = recovery_codes_new();
        user_enable_totp((int) $me['id'], $secret, recovery_codes_hash($codes));
        unset($_SESSION['tfa_setup_secret']);
        flash('تم تفعيل التحقق بخطوتين. احفظ رموز الاسترداد في مكان آمن.');
        render('tfa_recovery', ['codes' => $codes, 'heading' => 'تم التفعيل — رموز الاسترداد'], 'رموز الاسترداد');
        break;
    }

    /* تعطيل التحقق بخطوتين (يتطلّب كلمة المرور) */
    case 'tfa_disable': {
        if (!$isPost) {
            redirect(url('account'));
        }
        csrf_check();
        $pw  = (string) ($_POST['password'] ?? '');
        $row = user_get((int) $me['id']);
        if (!$row || !password_verify($pw, $row['password_hash'])) {
            flash('كلمة المرور غير صحيحة.', 'err');
            redirect(url('account'));
        }
        user_disable_totp((int) $me['id']);
        flash('تم تعطيل التحقق بخطوتين.');
        redirect(url('account'));
        break;
    }

    /* إعادة توليد رموز الاسترداد (يتطلّب كلمة المرور) */
    case 'tfa_recovery': {
        if (!$isPost) {
            redirect(url('account'));
        }
        csrf_check();
        $pw  = (string) ($_POST['password'] ?? '');
        $row = user_get((int) $me['id']);
        if (!$row || (int) $row['totp_enabled'] !== 1) {
            redirect(url('account'));
        }
        if (!password_verify($pw, $row['password_hash'])) {
            flash('كلمة المرور غير صحيحة.', 'err');
            redirect(url('account'));
        }
        $codes = recovery_codes_new();
        user_set_recovery((int) $me['id'], recovery_codes_hash($codes));
        flash('تم توليد رموز استرداد جديدة. أُلغيت الرموز السابقة.');
        render('tfa_recovery', ['codes' => $codes, 'heading' => 'رموز استرداد جديدة'], 'رموز الاسترداد');
        break;
    }

    /* ===== حفظ رقم واتساب والتذكير ===== */
    case 'wa_save': {
        if (!$isPost) {
            redirect(url('account'));
        }
        csrf_check();
        $phoneRaw = trim((string) ($_POST['wa_phone'] ?? ''));
        $enabled  = isset($_POST['wa_enabled']);
        $phone = $phoneRaw !== '' ? wa_normalize_phone($phoneRaw) : null;
        if ($phoneRaw !== '' && $phone === null) {
            flash('رقم واتساب غير صالح. اكتبه بالصيغة الدولية، مثال: 9665XXXXXXXX.', 'err');
            redirect(url('account'));
        }
        if ($enabled && $phone === null) {
            flash('أدخِل رقم واتساب لتفعيل التذكير.', 'err');
            redirect(url('account'));
        }
        user_set_whatsapp((int) $me['id'], $phone, $enabled);
        flash('تم حفظ إعدادات واتساب.');
        redirect(url('account'));
        break;
    }

    /* إرسال رسالة واتساب تجريبية */
    case 'wa_test': {
        if (!$isPost) {
            redirect(url('account'));
        }
        csrf_check();
        $row = user_get((int) $me['id']);
        $phone = $row['wa_phone'] ?? '';
        if (!$phone) {
            flash('احفظ رقم واتساب أولاً.', 'err');
            redirect(url('account'));
        }
        if (!wa_configured()) {
            flash('تكامل واتساب غير مُهيّأ على الخادم بعد (التوكن). راجع إعدادات الخادم.', 'err');
            redirect(url('account'));
        }
        $res = wa_send((string) $phone, 'رسالة تجريبية من ' . APP_NAME . ' ✅ — تم ربط رقمك بنجاح.');
        flash($res['ok'] ? 'تم إرسال رسالة تجريبية إلى واتساب.' : ('تعذّر الإرسال: ' . $res['error']), $res['ok'] ? 'ok' : 'err');
        redirect(url('account'));
        break;
    }

    /* ===== تيليجرام: تفعيل/تعطيل، فكّ الربط، تجربة ===== */
    case 'tg_save': {
        if (!$isPost) {
            redirect(url('account'));
        }
        csrf_check();
        user_set_tg_enabled((int) $me['id'], isset($_POST['tg_enabled']));
        flash('تم حفظ إعدادات تيليجرام.');
        redirect(url('account'));
        break;
    }

    case 'tg_unlink': {
        if ($isPost) {
            csrf_check();
            user_unlink_telegram((int) $me['id']);
            flash('تم فكّ ربط تيليجرام.');
        }
        redirect(url('account'));
        break;
    }

    case 'tg_test': {
        if (!$isPost) {
            redirect(url('account'));
        }
        csrf_check();
        $row = user_get((int) $me['id']);
        if (empty($row['tg_chat_id'])) {
            flash('اربط تيليجرام أولاً.', 'err');
            redirect(url('account'));
        }
        if (!tg_configured()) {
            flash('تكامل تيليجرام غير مُهيّأ على الخادم بعد.', 'err');
            redirect(url('account'));
        }
        $res = tg_send((string) $row['tg_chat_id'], 'رسالة تجريبية من ' . APP_NAME . ' ✅');
        flash($res['ok'] ? 'تم إرسال رسالة تجريبية إلى تيليجرام.' : ('تعذّر الإرسال: ' . $res['error']), $res['ok'] ? 'ok' : 'err');
        redirect(url('account'));
        break;
    }

    /* ضبط webhook تيليجرام (المدير) */
    case 'tg_setwebhook': {
        require_manager();
        if (!$isPost) {
            redirect(url('account'));
        }
        csrf_check();
        if (!tg_configured()) {
            flash('اضبط توكن البوت على الخادم أولاً.', 'err');
            redirect(url('account'));
        }
        $hookUrl = absolute_url('tg_webhook', TG_WEBHOOK_SECRET !== '' ? ['s' => TG_WEBHOOK_SECRET] : []);
        $res = tg_set_webhook($hookUrl, TG_WEBHOOK_SECRET);
        flash($res['ok'] ? 'تم ضبط webhook تيليجرام بنجاح.' : ('تعذّر الضبط: ' . $res['error']), $res['ok'] ? 'ok' : 'err');
        redirect(url('account'));
        break;
    }

    default:
        redirect(url('tasks'));
}
