<?php
declare(strict_types=1);

/*
 * تكامل تيليجرام عبر بوت (مجاني، بلا قوالب).
 * التوكن يُضبط في config.local.php/config.secret.php ولا يُرفع إلى Git.
 */

/** هل تكامل تيليجرام مُفعّل ومهيّأ؟ */
function tg_configured(): bool
{
    return TG_ENABLED && TG_BOT_TOKEN !== '';
}

/** استدعاء Bot API. يُعيد المصفوفة المُفكّكة أو ['ok'=>false,...]. */
function tg_api(string $method, array $params = []): array
{
    if (TG_BOT_TOKEN === '') {
        return ['ok' => false, 'error' => 'لا يوجد توكن بوت'];
    }
    $url = 'https://api.telegram.org/bot' . TG_BOT_TOKEN . '/' . $method;
    $res = wa_http('POST', $url, [
        'headers' => ['Content-Type: application/json'],
        'body'    => json_encode($params, JSON_UNESCAPED_UNICODE),
    ]);
    $data = json_decode($res['body'], true);
    if (is_array($data)) {
        return $data; // يحوي ok وresult/description
    }
    return ['ok' => false, 'error' => $res['error'] ?: ('HTTP ' . $res['code'])];
}

/** إرسال رسالة نصّية إلى محادثة. يُعيد ['ok'=>bool,'error'=>string]. */
function tg_send(string $chatId, string $text): array
{
    if (!tg_configured()) {
        return ['ok' => false, 'error' => 'تكامل تيليجرام غير مُهيّأ.'];
    }
    if ($chatId === '') {
        return ['ok' => false, 'error' => 'لا يوجد معرّف محادثة.'];
    }
    $r = tg_api('sendMessage', ['chat_id' => $chatId, 'text' => $text, 'disable_web_page_preview' => true]);
    return ['ok' => !empty($r['ok']), 'error' => !empty($r['ok']) ? '' : ('تيليجرام: ' . ($r['description'] ?? $r['error'] ?? 'فشل'))];
}

/** ضبط الـ webhook لاستقبال تحديثات البوت. */
function tg_set_webhook(string $url, string $secret = ''): array
{
    $params = ['url' => $url, 'allowed_updates' => ['message']];
    if ($secret !== '') {
        $params['secret_token'] = $secret;
    }
    $r = tg_api('setWebhook', $params);
    return ['ok' => !empty($r['ok']), 'error' => !empty($r['ok']) ? '' : ($r['description'] ?? $r['error'] ?? 'فشل')];
}

/** رابط ربط حساب تيليجرام عبر البوت (deep link). */
function tg_link_url(string $token): string
{
    $user = ltrim(TG_BOT_USERNAME, '@');
    return $user !== '' ? ('https://t.me/' . rawurlencode($user) . '?start=' . rawurlencode($token)) : '';
}
