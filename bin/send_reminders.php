<?php
declare(strict_types=1);
/*
 * تذكير بمواعيد اليوم (صباح يوم الموعد) عبر عدّة قنوات:
 *   - داخل النظام (الجرس): دائماً، بلا أي إعداد.
 *   - واتساب: إن فعّل المستخدم رقمه وكان المزوّد مهيّأً على الخادم.
 *   - تيليجرام: إن ربط المستخدم حسابه وكان البوت مهيّأً.
 *
 * يُجدوَل عبر GitHub Actions (.github/workflows/reminders.yml) أو cron على cPanel:
 *   0 7 * * *  /usr/local/bin/php /home/USER/tasktrak/bin/send_reminders.php
 *
 * لا يكرّر الإرسال لنفس (الموعد/المستخدم/القناة) بفضل جدول appointment_reminders.
 */
require __DIR__ . '/../app/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

$log = function (string $m): void { fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . '] ' . $m . "\n"); };

try {
    run_migrations();
    $today = date('Y-m-d');
    $appts = appointments_on_date($today);
    $counters = ['inapp' => 0, 'wa' => 0, 'tg' => 0, 'skip' => 0, 'fail' => 0];

    foreach ($appts as $a) {
        $apptId = (int) $a['id'];
        $text = appt_reminder_text($a);
        $recipients = array_filter(array_unique([
            (int) ($a['created_by'] ?? 0),
            (int) ($a['shared_with'] ?? 0),
        ]));
        foreach ($recipients as $uid) {
            if ($uid <= 0) { continue; }
            $u = user_get($uid);
            if (!$u) { continue; }

            // 1) داخل النظام (الجرس) — دائماً
            if (!reminder_already_sent($apptId, $uid, 'm_inapp')) {
                notif_add($uid, 'appointment', 'تذكير بموعد اليوم', (string) $a['subject'], 'appt', $apptId);
                reminder_mark_sent($apptId, $uid, 'm_inapp');
                $counters['inapp']++;
            }

            // 2) واتساب
            if (wa_configured() && !empty($u['wa_enabled']) && !empty($u['wa_phone']) && !reminder_already_sent($apptId, $uid, 'm_wa')) {
                $res = wa_send((string) $u['wa_phone'], $text);
                if ($res['ok']) { reminder_mark_sent($apptId, $uid, 'm_wa'); $counters['wa']++; }
                else { $counters['fail']++; $log("فشل واتساب: موعد #$apptId → #$uid — " . $res['error']); }
            }

            // 3) تيليجرام
            if (tg_configured() && !empty($u['tg_enabled']) && !empty($u['tg_chat_id']) && !reminder_already_sent($apptId, $uid, 'm_tg')) {
                $res = tg_send((string) $u['tg_chat_id'], $text);
                if ($res['ok']) { reminder_mark_sent($apptId, $uid, 'm_tg'); $counters['tg']++; }
                else { $counters['fail']++; $log("فشل تيليجرام: موعد #$apptId → #$uid — " . $res['error']); }
            }
        }
    }
    $log("تم. مواعيد اليوم=" . count($appts)
        . " · داخل النظام={$counters['inapp']} · واتساب={$counters['wa']} · تيليجرام={$counters['tg']} · فشل={$counters['fail']}");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'reminders error: ' . $e->getMessage() . "\n");
    exit(1);
}
