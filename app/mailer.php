<?php
declare(strict_types=1);

/*
 * إشعارات بريد بسيطة عبر دالة mail() (MTA المحلي على cPanel).
 * تنبيه: حدّ الخادم 50 رسالة/ساعة — مناسب لهذا الاستخدام الخفيف.
 * أي فشل في الإرسال لا يُعطِّل الطلب (يُتجاهَل بصمت).
 */

/** بناء رابط مطلق إلى مسار في التطبيق (للاستخدام داخل البريد). */
function absolute_url(string $route = 'tasks', array $params = []): string
{
    $scheme = is_https() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'tasktrak.co';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $dir = rtrim(str_replace('\\', '/', dirname($script)), '/');
    return $scheme . '://' . $host . $dir . '/' . url($route, $params);
}

/** إرسال بريد نصّي بترميز UTF-8. يُعيد true عند النجاح. */
function send_mail(string $to, string $subject, string $body): bool
{
    if (!MAIL_ENABLED) {
        return false;
    }
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    // ترميز العنوان والاسم يتم عبر mbstring (يتجنّب استدعاء دوال قد تثير Imunify)
    $subjectEnc = mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n");
    $fromEnc = mb_encode_mimeheader(MAIL_FROM_NAME, 'UTF-8', 'B', "\r\n") . ' <' . MAIL_FROM . '>';

    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: ' . $fromEnc,
        'Reply-To: ' . MAIL_FROM,
        'X-Mailer: TaskTrak',
    ]);

    try {
        return @mail($to, $subjectEnc, $body, $headers, '-f' . MAIL_FROM);
    } catch (Throwable $e) {
        return false;
    }
}

/** إشعار مدير القسم بإسناد مهمة إليه. */
function notify_task_assigned(int $taskId, int $headUserId): void
{
    $t = task_get($taskId);
    $h = user_get($headUserId);
    if (!$t || !$h || empty($h['email'])) {
        return;
    }
    $link = absolute_url('task', ['id' => $taskId]);
    $lines = [
        'مرحباً ' . $h['name'] . '،',
        '',
        'أُسندت إليك مهمة جديدة عبر ' . APP_NAME . ':',
        '',
        'العنوان: ' . $t['title'],
        'الأولوية: ' . $t['priority'],
    ];
    if (!empty($t['due_date'])) {
        $lines[] = 'تاريخ الاستحقاق: ' . fmt_date($t['due_date']);
    }
    $lines[] = '';
    $lines[] = 'التفاصيل:';
    $lines[] = $t['details'];
    if (!empty($t['manager_note'])) {
        $lines[] = '';
        $lines[] = 'ملاحظة المدير: ' . $t['manager_note'];
    }
    $lines[] = '';
    $lines[] = 'لعرض المهمة والتعقيب عليها:';
    $lines[] = $link;
    $lines[] = '';
    $lines[] = '— ' . APP_NAME;

    send_mail($h['email'], 'مهمة جديدة: ' . $t['title'], implode("\n", $lines));
}

/** إشعار المدير (منشئ المهمة) بتعقيب مدير القسم. */
function notify_task_replied(int $taskId): void
{
    $t = task_get($taskId);
    if (!$t || empty($t['created_by'])) {
        return;
    }
    $mgr = user_get((int) $t['created_by']);
    if (!$mgr || empty($mgr['email'])) {
        return;
    }
    $link = absolute_url('task', ['id' => $taskId]);
    $lines = [
        'تم تحديث حالة مهمة على ' . APP_NAME . ':',
        '',
        'العنوان: ' . $t['title'],
        'الحالة الجديدة: ' . $t['status'],
        'بواسطة: ' . ($t['replied_by'] ?: 'مدير القسم'),
    ];
    if (!empty($t['reply'])) {
        $lines[] = '';
        $lines[] = 'الملاحظات:';
        $lines[] = $t['reply'];
    }
    $lines[] = '';
    $lines[] = 'لعرض المهمة:';
    $lines[] = $link;
    $lines[] = '';
    $lines[] = '— ' . APP_NAME;

    send_mail($mgr['email'], 'تعقيب على مهمة: ' . $t['title'] . ' (' . $t['status'] . ')', implode("\n", $lines));
}

/** إشعار رئيس القسم بمشاركته في موعد. */
function notify_appointment_shared(int $apptId, int $headUserId): void
{
    $a = appointment_get($apptId);
    $h = user_get($headUserId);
    if (!$a || !$h || empty($h['email'])) {
        return;
    }
    $link = absolute_url('appointments');
    $lines = [
        'مرحباً ' . $h['name'] . '،',
        '',
        'تمت دعوتك للمشاركة في موعد عبر ' . APP_NAME . ':',
        '',
        'الموضوع: ' . $a['subject'],
        'التاريخ والوقت: ' . fmt_dt($a['starts_at']),
    ];
    if (!empty($a['with_whom'])) {
        $lines[] = 'مع: ' . $a['with_whom'];
    }
    if (!empty($a['location'])) {
        $lines[] = 'المكان: ' . $a['location'];
    }
    if (!empty($a['notes'])) {
        $lines[] = '';
        $lines[] = 'ملاحظات: ' . $a['notes'];
    }
    $lines[] = '';
    $lines[] = 'لعرض المواعيد:';
    $lines[] = $link;
    $lines[] = '';
    $lines[] = '— ' . APP_NAME;

    send_mail($h['email'], 'موعد جديد: ' . $a['subject'], implode("\n", $lines));
}
