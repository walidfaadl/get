<?php
declare(strict_types=1);
/*
 * إرسال تذكيرات واتساب بمواعيد اليوم (صباح يوم الموعد).
 * يُجدوَل عبر cron على cPanel، مثلاً كل صباح الساعة 7:
 *   0 7 * * *  /usr/local/bin/php /home/USER/tasktrak/bin/send_reminders.php >> /home/USER/tasktrak/reminders.log 2>&1
 *
 * يُرسِل لمنشئ الموعد ولمن شُورك معه (إن فعّلا واتساب وحفظا رقماً)،
 * ولا يكرّر الإرسال لنفس (الموعد/المستخدم) بفضل جدول appointment_reminders.
 */
require __DIR__ . '/../app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

$log = function (string $m): void { fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n"); };

if (!wa_configured()) {
    $log('تكامل واتساب غير مُهيّأ (WA_ENABLED/التوكن). لا شيء للإرسال.');
    exit(0);
}

try {
    run_migrations();
    $today = date('Y-m-d');
    $appts = appointments_on_date($today);
    $sent = 0; $skipped = 0; $failed = 0;

    foreach ($appts as $a) {
        $apptId = (int) $a['id'];
        // المستلمون المحتملون: المنشئ + من شُورك معه
        $recipients = array_filter(array_unique([
            (int) ($a['created_by'] ?? 0),
            (int) ($a['shared_with'] ?? 0),
        ]));
        foreach ($recipients as $uid) {
            if ($uid <= 0) { continue; }
            $u = user_get($uid);
            if (!$u || empty($u['wa_enabled']) || empty($u['wa_phone'])) { continue; }
            if (reminder_already_sent($apptId, $uid, 'morning')) { $skipped++; continue; }
            $res = wa_send((string) $u['wa_phone'], wa_reminder_text($a, $u));
            if ($res['ok']) {
                reminder_mark_sent($apptId, $uid, 'morning');
                $sent++;
                $log("أُرسل: موعد #$apptId → مستخدم #$uid");
            } else {
                $failed++;
                $log("فشل: موعد #$apptId → مستخدم #$uid — " . $res['error']);
            }
        }
    }
    $log("تم. مواعيد اليوم=" . count($appts) . " · أُرسل=$sent · مكرّر=$skipped · فشل=$failed");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'reminders error: ' . $e->getMessage() . "\n");
    exit(1);
}
