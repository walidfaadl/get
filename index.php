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

$r      = (string) ($_GET['r'] ?? 'tasks');
$method = $_SERVER['REQUEST_METHOD'];
$isPost = $method === 'POST';

/* ---------- مسارات عامة ---------- */
if ($r === 'login') {
    if (current_user()) {
        redirect(url('tasks'));
    }
    $error = null;
    if ($isPost) {
        csrf_check();
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        if (attempt_login($username, $password)) {
            redirect(url('tasks'));
        }
        $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
    }
    render('login', ['error' => $error], 'تسجيل الدخول');
    exit;
}

if ($r === 'logout') {
    logout();
    redirect(url('login'));
}

/* ---------- تتطلب تسجيل دخول ---------- */
require_login();
$me = current_user();

switch ($r) {
    /* ===== قائمة المهام ===== */
    case 'tasks': {
        $status = pick((string) ($_GET['status'] ?? ''), STATUSES, '');
        $search = trim((string) ($_GET['q'] ?? ''));
        $filter = [];
        if ($status !== '') {
            $filter['status'] = $status;
        }
        if ($search !== '') {
            $filter['search'] = $search;
        }
        $scope = null;
        if (!is_manager()) {
            $scope = scope_for_user($me);
            $filter['assignee_scope'] = $scope;
        }
        render('tasks_list', [
            'tasks'   => tasks_list($filter),
            'counts'  => task_counts($scope),
            'status'  => $status,
            'search'  => $search,
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
        if (!task_in_scope($task, $me)) {
            http_response_code(403);
            render('message', ['heading' => 'غير مصرّح', 'text' => 'هذه المهمة خارج نطاق قسمك.'], 'غير مصرّح');
            break;
        }
        render('task_detail', [
            'task'     => $task,
            'comments' => comments_for($id),
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

    /* ===== لوحة الإحصائيات (المدير) ===== */
    case 'stats': {
        require_manager();
        render('stats', [
            'counts'     => task_counts(),
            'byDept'     => stats_by_department(),
            'byAssignee' => stats_by_assignee(),
            'overdue'    => overdue_tasks(),
        ], 'الإحصائيات');
        break;
    }

    /* ===== المواعيد (المدير + رؤساء الأقسام) ===== */
    case 'appointments': {
        if (!can_manage_appointments()) {
            redirect(url('tasks'));
        }
        $view = pick((string) ($_GET['view'] ?? 'upcoming'), ['upcoming', 'past', 'all'], 'upcoming');
        $all  = appointments_for($me);
        $now  = time();
        $upcoming = array_values(array_filter($all, fn($a) => strtotime($a['starts_at']) >= $now));
        $past     = array_values(array_filter($all, fn($a) => strtotime($a['starts_at']) < $now));
        // السابقة تُعرض الأحدث أولاً
        $past = array_reverse($past);
        $items = $view === 'past' ? $past : ($view === 'all' ? $all : $upcoming);
        render('appointments', [
            'appointments' => $items,
            'view'         => $view,
            'counts'       => ['all' => count($all), 'upcoming' => count($upcoming), 'past' => count($past)],
        ], 'المواعيد');
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
        render('appt_detail', ['appt' => $appt], $appt['subject']);
        break;
    }

    case 'appt_new':
    case 'appt_edit': {
        if (!can_manage_appointments()) {
            redirect(url('tasks'));
        }
        $appt = null;
        if ($r === 'appt_edit') {
            $appt = appointment_get((int) ($_GET['id'] ?? 0));
            if (!$appt || !appointment_can_edit($appt, $me)) {
                redirect(url('appointments'));
            }
        }
        render('appt_form', [
            'appt'  => $appt,
            'heads' => users_heads(),
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
        render('account', [], 'حسابي');
        break;
    }

    default:
        redirect(url('tasks'));
}
