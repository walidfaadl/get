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
    $sql .= ' ORDER BY t.created_at DESC';

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
            pick($d['role'] ?? 'head', ['manager', 'head'], 'head'),
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
            $d['name'], $d['username'], $email, pick($d['role'] ?? 'head', ['manager', 'head'], 'head'),
            $d['department'] ?: null, (int) ($d['active'] ?? 1),
            password_hash($d['password'], PASSWORD_DEFAULT), $id,
        ]);
    } else {
        q('UPDATE users SET name = ?, username = ?, email = ?, role = ?, department = ?, active = ? WHERE id = ?', [
            $d['name'], $d['username'], $email, pick($d['role'] ?? 'head', ['manager', 'head'], 'head'),
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
    if ((int) ($task['assigned_to'] ?? 0) === (int) ($user['id'] ?? -1)) {
        return true;
    }
    $dep = trim((string) ($user['department'] ?? ''));
    return $dep !== '' && trim((string) ($task['department'] ?? '')) === $dep;
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
        "SELECT u.name, u.department,
                COUNT(t.id) total,
                SUM(t.status='تمت')  AS done,
                SUM(t.status='لم تتم') AS fail,
                SUM(t.status IN ('جديدة','قيد التنفيذ')) AS open_
         FROM users u
         LEFT JOIN tasks t ON t.assigned_to = u.id
         WHERE u.role = 'head' AND u.active = 1
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
