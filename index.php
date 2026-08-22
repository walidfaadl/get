<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';

// إن لم تُهيّأ قاعدة البيانات بعد، وجّه إلى المُثبِّت.
try {
    db();
    $__installed = q_one('SELECT id FROM users LIMIT 1') !== null || managers_count() >= 0;
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
            $scope = ['user_id' => $me['id'], 'department' => $me['department']];
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
            'task'  => $task,
            'heads' => users_heads(),
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
            task_update($id, $data);
            flash('تم تحديث المهمة.');
            redirect(url('task', ['id' => $id]));
        } else {
            $data['created_by'] = $me['id'];
            $newId = task_create($data);
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
        if (!in_array($status, REPLY_STATUSES, true)) {
            flash('اختر حالة المهمة (تمت / قيد التنفيذ / لم تتم).', 'err');
            redirect(url('task', ['id' => $id]));
        }
        task_reply($id, $status, $reply, $me['name']);
        flash('تم حفظ التعقيب.');
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
        if ($body !== '' && task_get($id)) {
            comment_add($id, $me, $body);
            flash('تمت إضافة الملاحظة.');
        }
        redirect(url('task', ['id' => $id]) . '#comments');
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
        $password = (string) ($_POST['password'] ?? '');
        $role     = pick((string) ($_POST['role'] ?? 'head'), ['manager', 'head'], 'head');
        $dept     = trim((string) ($_POST['department'] ?? ''));
        $active   = isset($_POST['active']) ? 1 : 0;

        if ($name === '' || $username === '') {
            flash('الاسم واسم المستخدم مطلوبان.', 'err');
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
        $d = compact('name', 'username', 'password', 'role', 'dept', 'active');
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
