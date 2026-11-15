<?php
declare(strict_types=1);

/*
 * التحقق بخطوتين (TOTP — RFC 6238) متوافق مع تطبيقات المصادقة
 * (Google Authenticator / Authy / Microsoft Authenticator...).
 * بلا أي اعتماديات خارجية.
 */

/** ترميز Base32 (RFC 4648) لبايتات ثنائية. */
function base32_encode(string $bin): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $out = '';
    $bits = 0;
    $val = 0;
    $len = strlen($bin);
    for ($i = 0; $i < $len; $i++) {
        $val = ($val << 8) | ord($bin[$i]);
        $bits += 8;
        while ($bits >= 5) {
            $out .= $alphabet[($val >> ($bits - 5)) & 31];
            $bits -= 5;
        }
    }
    if ($bits > 0) {
        $out .= $alphabet[($val << (5 - $bits)) & 31];
    }
    return $out;
}

/** فكّ ترميز Base32 إلى بايتات. */
function base32_decode(string $b32): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $b32 = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $b32));
    $bits = 0;
    $val = 0;
    $out = '';
    $len = strlen($b32);
    for ($i = 0; $i < $len; $i++) {
        $pos = strpos($alphabet, $b32[$i]);
        if ($pos === false) {
            continue;
        }
        $val = ($val << 5) | $pos;
        $bits += 5;
        if ($bits >= 8) {
            $out .= chr(($val >> ($bits - 8)) & 0xFF);
            $bits -= 8;
        }
    }
    return $out;
}

/** توليد سرّ جديد (20 بايت عشوائية) بصيغة Base32. */
function totp_new_secret(): string
{
    return base32_encode(random_bytes(20));
}

/** حساب رمز TOTP لشريحة زمنية معيّنة (أو الحالية). */
function totp_code(string $secretB32, ?int $timeSlice = null, int $digits = 6, int $period = 30): string
{
    $key = base32_decode($secretB32);
    if ($key === '') {
        return str_repeat('0', $digits);
    }
    if ($timeSlice === null) {
        $timeSlice = (int) floor(time() / $period);
    }
    // عدّاد 8 بايت big-endian
    $bin = pack('N', 0) . pack('N', $timeSlice);
    $hash = hash_hmac('sha1', $bin, $key, true);
    $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
    $part = ((ord($hash[$offset]) & 0x7F) << 24)
          | ((ord($hash[$offset + 1]) & 0xFF) << 16)
          | ((ord($hash[$offset + 2]) & 0xFF) << 8)
          | (ord($hash[$offset + 3]) & 0xFF);
    $code = $part % (10 ** $digits);
    return str_pad((string) $code, $digits, '0', STR_PAD_LEFT);
}

/** التحقق من رمز TOTP ضمن نافذة تسامح (±window شرائح 30ث). */
function totp_verify(string $secretB32, string $code, int $window = 1, int $period = 30): bool
{
    $code = preg_replace('/\D/', '', $code);
    if ($secretB32 === '' || strlen($code) !== 6) {
        return false;
    }
    $cur = (int) floor(time() / $period);
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totp_code($secretB32, $cur + $i), $code)) {
            return true;
        }
    }
    return false;
}

/** رابط otpauth:// لإضافته إلى تطبيق المصادقة (QR أو يدوي). */
function totp_uri(string $secretB32, string $account, string $issuer): string
{
    $label = rawurlencode($issuer) . ':' . rawurlencode($account);
    $q = http_build_query([
        'secret'    => $secretB32,
        'issuer'    => $issuer,
        'algorithm' => 'SHA1',
        'digits'    => 6,
        'period'    => 30,
    ]);
    return 'otpauth://totp/' . $label . '?' . $q;
}

/** تجزئة السرّ لعرضه بمجموعات من 4 أحرف (أسهل للإدخال اليدوي). */
function totp_format_secret(string $secretB32): string
{
    return trim(chunk_split($secretB32, 4, ' '));
}

/** توليد مجموعة رموز استرداد نصّية (XXXX-XXXX). */
function recovery_codes_new(int $n = 8): array
{
    $codes = [];
    for ($i = 0; $i < $n; $i++) {
        $hex = bin2hex(random_bytes(4)); // 8 hex chars
        $codes[] = substr($hex, 0, 4) . '-' . substr($hex, 4, 4);
    }
    return $codes;
}

/** تحويل رموز الاسترداد إلى JSON من تجزئات bcrypt (للتخزين). */
function recovery_codes_hash(array $codes): string
{
    $out = [];
    foreach ($codes as $c) {
        $out[] = ['h' => password_hash($c, PASSWORD_DEFAULT), 'used' => 0];
    }
    return json_encode($out, JSON_UNESCAPED_UNICODE);
}
