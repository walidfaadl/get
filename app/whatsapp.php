<?php
declare(strict_types=1);

/*
 * إرسال رسائل واتساب عبر مزوّد قابل للتبديل (إعداد واحد WA_PROVIDER):
 *   - cloud   : WhatsApp Cloud API الرسمي (Meta)  [موصى به]
 *   - ultramsg: خدمة UltraMsg (ربط رقمك مباشرة)
 *   - twilio  : Twilio for WhatsApp
 * تُضبط البيانات الحسّاسة (التوكن) في app/config.local.php ولا تُرفع إلى Git.
 */

/** هل تكامل واتساب مُفعّل ومهيّأ (توكن + معرّف المُرسِل)؟ */
function wa_configured(): bool
{
    return WA_ENABLED && WA_TOKEN !== '' && WA_FROM !== '';
}

/** تطبيع رقم واتساب إلى أرقام فقط بصيغة دولية (بلا +). يُعيد null إن كان غير صالح. */
function wa_normalize_phone(string $raw): ?string
{
    $raw = trim($raw);
    // حوّل 00 البادئة إلى لا شيء، واحذف كل ما سوى الأرقام
    $raw = preg_replace('/^00/', '', $raw);
    $digits = preg_replace('/\D/', '', $raw);
    if ($digits === '' || strlen($digits) < 8 || strlen($digits) > 15) {
        return null;
    }
    return $digits;
}

/** طلب HTTP بسيط عبر cURL. يُعيد ['ok'=>bool,'code'=>int,'body'=>string,'error'=>string]. */
function wa_http(string $method, string $url, array $opts = []): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'code' => 0, 'body' => '', 'error' => 'cURL غير متاح على الخادم.'];
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
    ]);
    if (!empty($opts['headers'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);
    }
    if (isset($opts['body'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['body']);
    }
    if (!empty($opts['userpwd'])) {
        curl_setopt($ch, CURLOPT_USERPWD, $opts['userpwd']);
    }
    $body = curl_exec($ch);
    $err  = curl_error($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false) {
        return ['ok' => false, 'code' => $code, 'body' => '', 'error' => $err ?: 'فشل الاتصال'];
    }
    return ['ok' => $code >= 200 && $code < 300, 'code' => $code, 'body' => (string) $body, 'error' => ''];
}

/**
 * إرسال رسالة نصّية إلى رقم واتساب. يُعيد ['ok'=>bool,'error'=>string].
 * للمزوّد الرسمي (cloud) خارج نافذة 24 ساعة يلزم قالب معتمد (WA_TEMPLATE).
 */
function wa_send(string $phone, string $text): array
{
    if (!wa_configured()) {
        return ['ok' => false, 'error' => 'تكامل واتساب غير مُهيّأ على الخادم.'];
    }
    $to = wa_normalize_phone($phone);
    if ($to === null) {
        return ['ok' => false, 'error' => 'رقم واتساب غير صالح.'];
    }
    $provider = WA_PROVIDER;
    if ($provider === 'ultramsg') {
        $url = 'https://api.ultramsg.com/' . rawurlencode(WA_FROM) . '/messages/chat';
        $res = wa_http('POST', $url, [
            'headers' => ['Content-Type: application/x-www-form-urlencoded'],
            'body'    => http_build_query(['token' => WA_TOKEN, 'to' => $to, 'body' => $text]),
        ]);
        return ['ok' => $res['ok'], 'error' => $res['ok'] ? '' : ('UltraMsg: ' . $res['code'] . ' ' . mb_substr($res['body'], 0, 180))];
    }
    if ($provider === 'twilio') {
        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode(WA_SID) . '/Messages.json';
        $res = wa_http('POST', $url, [
            'userpwd' => WA_SID . ':' . WA_TOKEN,
            'headers' => ['Content-Type: application/x-www-form-urlencoded'],
            'body'    => http_build_query([
                'From' => 'whatsapp:' . WA_FROM,
                'To'   => 'whatsapp:+' . $to,
                'Body' => $text,
            ]),
        ]);
        return ['ok' => $res['ok'], 'error' => $res['ok'] ? '' : ('Twilio: ' . $res['code'] . ' ' . mb_substr($res['body'], 0, 180))];
    }
    // الافتراضي: WhatsApp Cloud API (Meta)
    $url = 'https://graph.facebook.com/v21.0/' . rawurlencode(WA_FROM) . '/messages';
    if (WA_TEMPLATE !== '') {
        // رسالة قالب (مطلوبة للرسائل المُبادَر بها خارج نافذة 24 ساعة) — متغيّر واحد {{1}}=النص
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'template',
            'template'          => [
                'name'     => WA_TEMPLATE,
                'language' => ['code' => WA_LANG],
                'components' => [[
                    'type'       => 'body',
                    'parameters' => [['type' => 'text', 'text' => $text]],
                ]],
            ],
        ];
    } else {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $to,
            'type'              => 'text',
            'text'              => ['preview_url' => false, 'body' => $text],
        ];
    }
    $res = wa_http('POST', $url, [
        'headers' => ['Authorization: Bearer ' . WA_TOKEN, 'Content-Type: application/json'],
        'body'    => json_encode($payload, JSON_UNESCAPED_UNICODE),
    ]);
    return ['ok' => $res['ok'], 'error' => $res['ok'] ? '' : ('Cloud API: ' . $res['code'] . ' ' . mb_substr($res['body'], 0, 180))];
}

/** نصّ تذكير الموعد. */
function wa_reminder_text(array $appt, array $user): string
{
    $when = fmt_dt($appt['starts_at']);
    $lines = [
        'تذكير بموعدك اليوم عبر ' . APP_NAME . ':',
        '',
        '📌 ' . $appt['subject'],
        '🕐 ' . $when,
    ];
    if (!empty($appt['with_whom'])) {
        $lines[] = '👤 مع: ' . $appt['with_whom'];
    }
    if (!empty($appt['location'])) {
        $lines[] = '📍 ' . $appt['location'];
    }
    return implode("\n", $lines);
}
