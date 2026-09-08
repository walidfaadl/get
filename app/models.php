<?php
declare(strict_types=1);

/* ==========================================================
   استعلامات المهام والمستخدمين
   ========================================================== */

/* ---------- المهام ---------- */

/**
 * قائمة المهام مع تصفية اختيارية.
 * مدير القسم يرى فقط المهام المسندة إليه أو إلى قسمه.
 */
function tasks_list(array $filter = []): array
{
    $where = [];
    $args  = [];

    if (!empty($filter['status'])) {
        $where[] = 't.status = ?';
        $args[]  = $filter['status'];
    }
    if (!empty($filter['assignee_scope'])) {
        // scope = ['user_id'=>x, 'department'=>y]
        $sc = $filter['assignee_scope'];
        $where[] = '(t.assigned_to = ? OR (t.department IS NOT NULL AND t.department <> "" AND t.department = ?))';
        $args[]  = $sc['user_id'];
        $args[]  = (string) ($sc['department'] ?? '');
    }
    if (!empty($filter['late'])) {
        $where[] = "t.due_date IS NOT NULL AND t.due_date < CURDATE() AND t.status NOT IN ('تمت','لم تتم')";
    }
    if (!empty($filter['assignee'])) {
        $where[] = 't.assigned_to = ?';
        $args[]  = (int) $filter['assignee'];
    }
    if (isset($filter['search']) && $filter['search'] !== '') {
        $where[] = '(t.title LIKE ? OR t.details LIKE ?)';
        $like    = '%' . $filter['search'] . '%';
        $args[]  = $like;
        $args[]  = $like;
    }

    $sql = 'SELECT t.*, u.name AS assignee_name, c.name AS creator_name
            FROM tasks t
            LEFT JOIN users u ON u.id = t.assigned_to
            LEFT JOIN users c ON c.id = t.created_by';
    if ($where) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sort = $filter['sort'] ?? 'recent';
    if ($sort === 'due') {
        $sql .= ' ORDER BY (t.due_date IS NULL), t.due_date ASC, t.created_at DESC';
    } elseif ($sort === 'priority') {
        $sql .= " ORDER BY FIELD(t.priority,'عالية','متوسطة','منخفضة'), t.created_at DESC";
    } else {
        $sql .= ' ORDER BY t.created_at DESC';
    }

    return q_all($sql, $args);
}

function task_get(int $id): ?array
{
    return q_one(
        'SELECT t.*, u.name AS assignee_name, c.name AS creator_name
         FROM tasks t
         LEFT JOIN users u ON u.id = t.assigned_to
         LEFT JOIN users c ON c.id = t.created_by
         WHERE t.id = ? LIMIT 1',
        [$id]
    );
}

function task_create(array $d): int
{
    q(
        'INSERT INTO tasks
            (title, details, department, priority, status, due_date, manager_note, created_by, assigned_to)
         VALUES (?,?,?,?,?,?,?,?,?)',
        [
            $d['title'],
            $d['details'],
            $d['department'] ?: null,
            pick($d['priority'] ?? '', PRIORITIES, 'متوسطة'),
            'جديدة',
            $d['due_date'] ?: null,
            $d['manager_note'] ?: null,
            $d['created_by'] ?? null,
            $d['assigned_to'] ?: null,
        ]
    );
    return (int) db()->lastInsertId();
}

function task_update(int $id, array $d): void
{
    q(
        'UPDATE tasks SET
            title = ?, details = ?, department = ?, priority = ?,
            due_date = ?, manager_note = ?, assigned_to = ?
         WHERE id = ?',
        [
            $d['title'],
            $d['details'],
            $d['department'] ?: null,
            pick($d['priority'] ?? '', PRIORITIES, 'متوسطة'),
            $d['due_date'] ?: null,
            $d['manager_note'] ?: null,
            $d['assigned_to'] ?: null,
            $id,
        ]
    );
}

/** تعقيب مدير القسم: تحديث الحالة والملاحظة. */
function task_reply(int $id, string $status, string $reply, string $repliedBy): void
{
    q(
        'UPDATE tasks SET status = ?, reply = ?, replied_by = ?, replied_at = NOW()
         WHERE id = ?',
        [pick($status, REPLY_STATUSES, 'قيد التنفيذ'), $reply, $repliedBy, $id]
    );
}

/** تعديل المدير لحالة المهمة مباشرةً (أي من الحالات الأربع). */
function task_set_status(int $id, string $status): void
{
    q('UPDATE tasks SET status = ? WHERE id = ?', [pick($status, STATUSES, 'جديدة'), $id]);
}

function task_delete(int $id): void
{
    q('DELETE FROM tasks WHERE id = ?', [$id]);
}

function task_counts(?array $scope = null): array
{
    $where = '';
    $args  = [];
    if ($scope) {
        $where = 'WHERE assigned_to = ? OR (department IS NOT NULL AND department <> "" AND department = ?)';
        $args  = [$scope['user_id'], (string) ($scope['department'] ?? '')];
    }
    $rows = q_all("SELECT status, COUNT(*) c FROM tasks $where GROUP BY status", $args);
    $out  = ['total' => 0, 'جديدة' => 0, 'قيد التنفيذ' => 0, 'تمت' => 0, 'لم تتم' => 0];
    foreach ($rows as $r) {
        $out[$r['status']] = (int) $r['c'];
        $out['total']     += (int) $r['c'];
    }
    return $out;
}

/* ---------- التعليقات (نقاش المهمة) ---------- */

function comments_for(int $taskId): array
{
    return q_all(
        'SELECT * FROM task_comments WHERE task_id = ? ORDER BY created_at ASC',
        [$taskId]
    );
}

function comment_add(int $taskId, array $user, string $body): void
{
    q(
        'INSERT INTO task_comments (task_id, user_id, author, role, body)
         VALUES (?,?,?,?,?)',
        [$taskId, $user['id'], $user['name'], $user['role'], $body]
    );
}

/* ---------- المستخدمون ---------- */

function users_all(): array
{
    return q_all('SELECT * FROM users ORDER BY role DESC, name ASC');
}

function users_heads(): array
{
    return q_all("SELECT * FROM users WHERE role = 'head' AND active = 1 ORDER BY name ASC");
}

/** المستخدمون القابلون لتكليفهم بمهمة: رؤساء الأقسام وأعضاء الأقسام. */
function users_assignable(): array
{
    return q_all(
        "SELECT * FROM users WHERE role IN ('head','member') AND active = 1
         ORDER BY (role = 'head') DESC, name ASC"
    );
}

function user_get(int $id): ?array
{
    return q_one('SELECT * FROM users WHERE id = ? LIMIT 1', [$id]);
}

function username_exists(string $username, int $exceptId = 0): bool
{
    return q_one('SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1', [$username, $exceptId]) !== null;
}

function user_create(array $d): int
{
    q(
        'INSERT INTO users (name, username, email, password_hash, role, department, active)
         VALUES (?,?,?,?,?,?,1)',
        [
            $d['name'],
            $d['username'],
            ($d['email'] ?? '') ?: null,
            password_hash($d['password'], PASSWORD_DEFAULT),
            pick($d['role'] ?? 'head', ['manager', 'head', 'member'], 'head'),
            $d['department'] ?: null,
        ]
    );
    return (int) db()->lastInsertId();
}

function user_update(int $id, array $d): void
{
    $email = ($d['email'] ?? '') ?: null;
    if (!empty($d['password'])) {
        q('UPDATE users SET name = ?, username = ?, email = ?, role = ?, department = ?, active = ?, password_hash = ? WHERE id = ?', [
            $d['name'], $d['username'], $email, pick($d['role'] ?? 'head', ['manager', 'head', 'member'], 'head'),
            $d['department'] ?: null, (int) ($d['active'] ?? 1),
            password_hash($d['password'], PASSWORD_DEFAULT), $id,
        ]);
    } else {
        q('UPDATE users SET name = ?, username = ?, email = ?, role = ?, department = ?, active = ? WHERE id = ?', [
            $d['name'], $d['username'], $email, pick($d['role'] ?? 'head', ['manager', 'head', 'member'], 'head'),
            $d['department'] ?: null, (int) ($d['active'] ?? 1), $id,
        ]);
    }
}

function user_set_password(int $id, string $password): void
{
    q('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
}

function user_delete(int $id): void
{
    q('DELETE FROM users WHERE id = ?', [$id]);
}

function managers_count(): int
{
    $r = q_one("SELECT COUNT(*) c FROM users WHERE role = 'manager' AND active = 1");
    return (int) ($r['c'] ?? 0);
}

/* ---------- الصلاحيات: نطاق رؤية مدير القسم ---------- */

/**
 * هل يحقّ للمستخدم رؤية/التعامل مع هذه المهمة؟
 * المدير يرى الكل؛ مدير القسم يرى المسندة إليه أو لقسمه فقط.
 */
function task_in_scope(array $task, array $user): bool
{
    if (($user['role'] ?? '') === 'manager') {
        return true;
    }
    // المسندة إليه شخصياً: يراها مدير القسم وعضو القسم
    if ((int) ($task['assigned_to'] ?? 0) === (int) ($user['id'] ?? -1)) {
        return true;
    }
    // مدير القسم فقط يرى مهام قسمه؛ عضو القسم يرى المسندة إليه فقط
    if (($user['role'] ?? '') === 'head') {
        $dep = trim((string) ($user['department'] ?? ''));
        return $dep !== '' && trim((string) ($task['department'] ?? '')) === $dep;
    }
    return false;
}

/**
 * بناء نطاق تصفية المهام حسب دور المستخدم (للقائمة والإحصائيات).
 * مدير القسم: قسمه + المسندة إليه. عضو القسم: المسندة إليه فقط.
 */
function scope_for_user(array $user): array
{
    $dep = ($user['role'] ?? '') === 'head' ? trim((string) ($user['department'] ?? '')) : '';
    return ['user_id' => (int) $user['id'], 'department' => $dep];
}

/* ---------- إحصائيات (للمدير) ---------- */

function stats_by_department(): array
{
    return q_all(
        "SELECT COALESCE(NULLIF(department,''),'— بدون قسم —') dep,
                COUNT(*) total,
                SUM(status='جديدة')      AS new_,
                SUM(status='قيد التنفيذ') AS prog,
                SUM(status='تمت')        AS done,
                SUM(status='لم تتم')      AS fail
         FROM tasks
         GROUP BY dep
         ORDER BY total DESC"
    );
}

function stats_by_assignee(): array
{
    return q_all(
        "SELECT u.name, u.department, u.role,
                COUNT(t.id) total,
                SUM(t.status='تمت')  AS done,
                SUM(t.status='لم تتم') AS fail,
                SUM(t.status IN ('جديدة','قيد التنفيذ')) AS open_
         FROM users u
         LEFT JOIN tasks t ON t.assigned_to = u.id
         WHERE u.role IN ('head','member') AND u.active = 1
         GROUP BY u.id
         ORDER BY total DESC, u.name ASC"
    );
}

/** المهام المتأخرة: تجاوزت الاستحقاق ولمّا تُنجَز أو تُغلَق. */
function overdue_tasks(): array
{
    return q_all(
        "SELECT t.*, u.name AS assignee_name
         FROM tasks t
         LEFT JOIN users u ON u.id = t.assigned_to
         WHERE t.due_date IS NOT NULL
           AND t.due_date < CURDATE()
           AND t.status NOT IN ('تمت','لم تتم')
         ORDER BY t.due_date ASC"
    );
}

/* ---------- المواعيد ---------- */

/**
 * قائمة المواعيد حسب المستخدم.
 * المدير يرى الكل؛ رئيس القسم يرى ما أنشأه أو ما شُورك فيه.
 */
function appointments_for(array $user): array
{
    $base = "SELECT a.*, c.name AS creator_name, s.name AS shared_name
             FROM appointments a
             LEFT JOIN users c ON c.id = a.created_by
             LEFT JOIN users s ON s.id = a.shared_with";
    if (($user['role'] ?? '') === 'manager') {
        return q_all("$base ORDER BY a.starts_at ASC");
    }
    return q_all(
        "$base WHERE a.created_by = ? OR a.shared_with = ? ORDER BY a.starts_at ASC",
        [(int) $user['id'], (int) $user['id']]
    );
}

function appointment_get(int $id): ?array
{
    return q_one(
        "SELECT a.*, c.name AS creator_name, s.name AS shared_name
         FROM appointments a
         LEFT JOIN users c ON c.id = a.created_by
         LEFT JOIN users s ON s.id = a.shared_with
         WHERE a.id = ? LIMIT 1",
        [$id]
    );
}

function appointment_create(array $d): int
{
    q(
        'INSERT INTO appointments (subject, with_whom, starts_at, location, notes, created_by, shared_with)
         VALUES (?,?,?,?,?,?,?)',
        [
            $d['subject'],
            $d['with_whom'] ?: null,
            $d['starts_at'],
            $d['location'] ?: null,
            $d['notes'] ?: null,
            $d['created_by'] ?? null,
            $d['shared_with'] ?: null,
        ]
    );
    return (int) db()->lastInsertId();
}

function appointment_update(int $id, array $d): void
{
    q(
        'UPDATE appointments SET subject = ?, with_whom = ?, starts_at = ?, location = ?, notes = ?, shared_with = ?
         WHERE id = ?',
        [
            $d['subject'],
            $d['with_whom'] ?: null,
            $d['starts_at'],
            $d['location'] ?: null,
            $d['notes'] ?: null,
            $d['shared_with'] ?: null,
            $id,
        ]
    );
}

/** تحديث حالة الموعد (تم/تأجّل/لم يُعقد/مجدول) مع تاريخ تأجيل اختياري. */
function appointment_set_status(int $id, string $status, ?string $postponedTo): void
{
    $status = pick($status, APPT_STATUSES, 'مجدول');
    $pt = ($status === 'تأجّل' && $postponedTo) ? $postponedTo : null;
    q('UPDATE appointments SET status = ?, postponed_to = ? WHERE id = ?', [$status, $pt, $id]);
}

function appointment_delete(int $id): void
{
    q('DELETE FROM appointments WHERE id = ?', [$id]);
}

/** هل يستطيع المستخدم تعديل/حذف الموعد؟ المدير أو منشئ الموعد. */
function appointment_can_edit(array $appt, array $user): bool
{
    return ($user['role'] ?? '') === 'manager' || (int) ($appt['created_by'] ?? 0) === (int) $user['id'];
}

/** هل يستطيع المستخدم رؤية الموعد؟ */
function appointment_in_scope(array $appt, array $user): bool
{
    if (($user['role'] ?? '') === 'manager') {
        return true;
    }
    $uid = (int) $user['id'];
    return (int) ($appt['created_by'] ?? 0) === $uid || (int) ($appt['shared_with'] ?? 0) === $uid;
}

/* ---------- التنبيهات داخل النظام ---------- */

function notif_add(int $userId, string $type, string $title, string $body, string $route, ?int $refId): void
{
    if ($userId <= 0) {
        return;
    }
    q(
        'INSERT INTO notifications (user_id, type, title, body, route, ref_id) VALUES (?,?,?,?,?,?)',
        [$userId, $type, $title, ($body !== '' ? mb_substr($body, 0, 290) : null), $route, $refId]
    );
}

function notifs_for(int $userId, int $limit = 30): array
{
    $limit = max(1, min(100, $limit));
    return q_all(
        "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit",
        [$userId]
    );
}

function notifs_unread(int $userId): int
{
    $r = q_one('SELECT COUNT(*) c FROM notifications WHERE user_id = ? AND is_read = 0', [$userId]);
    return (int) ($r['c'] ?? 0);
}

function notifs_mark_all_read(int $userId): void
{
    q('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0', [$userId]);
}
