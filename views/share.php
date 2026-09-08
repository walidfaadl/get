<?php
/** صفحة موعد عامة — للقراءة فقط عبر رابط المشاركة. @var ?array $appt */
$months  = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
$dayName = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
$fmtFull = function (?string $dt) use ($months, $dayName) {
    if (!$dt) return '';
    $ts = strtotime($dt);
    if ($ts === false) return $dt;
    return $dayName[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1]
         . ' ' . date('Y', $ts) . ' — ' . date('H:i', $ts);
};

if (!$appt) {
    http_response_code(404);
}
$ok = (bool) $appt;
$ts = $ok ? strtotime($appt['starts_at']) : false;
$status = $ok ? ($appt['status'] ?? 'مجدول') : '';
$ogTitle = $ok ? $appt['subject'] : 'رابط غير صالح';
$ogDesc  = $ok ? trim(($fmtFull($appt['starts_at'])) . ($appt['with_whom'] ? ' • مع ' . $appt['with_whom'] : '')) : 'هذا الموعد غير موجود.';
$canonical = $ok ? absolute_url('share', ['t' => $appt['share_token']]) : '';
$origin = (is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'tasktrak.co');
$rstatus = $ok ? ($appt['recipient_status'] ?? '') : '';
$shareFlash = flash();
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($ogTitle) ?> — <?= e(APP_NAME) ?></title>
<meta name="robots" content="noindex">
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e(APP_NAME) ?>">
<meta property="og:title" content="<?= e($ogTitle) ?>">
<?php if ($ogDesc): ?><meta property="og:description" content="<?= e($ogDesc) ?>"><?php endif; ?>
<?php if ($canonical): ?><meta property="og:url" content="<?= e($canonical) ?>"><?php endif; ?>
<meta property="og:locale" content="ar_AR">
<meta property="og:image" content="<?= e($origin) ?>/assets/icons/icon-512.png">
<meta property="og:image:width" content="512">
<meta property="og:image:height" content="512">
<meta property="og:image:alt" content="<?= e(APP_NAME) ?>">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="<?= e($ogTitle) ?>">
<meta name="twitter:description" content="<?= e($ogDesc) ?>">
<meta name="theme-color" content="#7B2338">
<link rel="icon" type="image/png" sizes="192x192" href="/assets/icons/icon-192.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
  :root{--bg:#FAF9F5;--surface:#fff;--sunk:#F3F1EC;--ink:#1C1B19;--soft:#5F5D57;--mute:#8B8880;
    --line:rgba(28,27,25,.10);--brand:#7B2338;--brand-ink:#fff;
    --done:#2E7D57;--doing:#B07310;--late:#B3261E;--info:#2857A6;
    --done-bg:#E8F3EC;--doing-bg:#FBF2E1;--late-bg:#FBECEA;--info-bg:#E8EFF9;}
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--ink);font-family:"IBM Plex Sans Arabic",system-ui,sans-serif;
    min-height:100vh;display:flex;flex-direction:column;align-items:center;padding:28px 18px;line-height:1.7}
  .brand{display:flex;align-items:center;gap:10px;margin-bottom:20px;color:var(--brand)}
  .brand .mk{width:34px;height:34px;border-radius:9px;background:var(--brand);color:var(--brand-ink);display:grid;place-items:center}
  .brand .mk svg{width:18px;height:18px}
  .brand b{font-size:16px;font-weight:600}
  .card{background:var(--surface);border:.5px solid var(--line);border-radius:16px;max-width:440px;width:100%;
    box-shadow:0 8px 30px rgba(28,27,25,.06);overflow:hidden}
  .head{display:flex;gap:16px;align-items:center;padding:22px;background:linear-gradient(120deg,#fff,#FBECEF);border-bottom:.5px solid var(--line)}
  .datebox{flex:none;width:82px;text-align:center;color:var(--brand-ink);background:var(--brand);border-radius:12px;padding:12px 8px}
  .datebox .d{font-size:32px;font-weight:700;line-height:1}
  .datebox .mo{font-size:13px;opacity:.9;margin-top:3px}
  .head h1{margin:0 0 6px;font-size:19px;font-weight:600}
  .when{font-size:14px;font-weight:500;color:var(--soft);display:flex;align-items:center;gap:7px}
  .when svg{width:16px;height:16px}
  .body{padding:20px 22px}
  .rowline{display:flex;gap:10px;padding:9px 0;border-bottom:.5px solid var(--line);font-size:15px}
  .rowline:last-child{border-bottom:0}
  .rowline .k{color:var(--mute);min-width:76px;flex:none;font-size:13px;padding-top:2px}
  .rowline .v{font-weight:500}
  .ast{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;border-radius:20px;padding:3px 11px;margin-top:8px}
  .ast::before{content:'';width:6px;height:6px;border-radius:50%;background:currentColor}
  .ast-sched{background:var(--info-bg);color:var(--info)}
  .ast-done{background:var(--done-bg);color:var(--done)}
  .ast-post{background:var(--doing-bg);color:var(--doing)}
  .ast-noshow{background:var(--late-bg);color:var(--late)}
  .postponed{margin-top:10px;font-weight:600;color:var(--doing);font-size:14px}
  .foot{padding:14px 22px;border-top:.5px solid var(--line);color:var(--mute);font-size:12px;text-align:center;background:var(--sunk)}
  .bad{max-width:440px;text-align:center;background:var(--surface);border:.5px solid var(--line);border-radius:16px;padding:40px 26px}
  .bad h1{color:var(--brand);font-size:20px;margin:0 0 8px}
  .bad p{color:var(--soft);margin:0}
  .flashmsg{max-width:440px;width:100%;background:var(--done-bg);color:var(--done);border-radius:12px;padding:11px 16px;font-size:14px;font-weight:600;text-align:center;margin-bottom:14px}
  .resp{max-width:440px;width:100%;background:var(--surface);border:.5px solid var(--line);border-radius:16px;padding:18px 20px;margin-top:14px;box-shadow:0 8px 30px rgba(28,27,25,.06)}
  .resp h2{font-size:15px;font-weight:600;margin:0 0 12px}
  .resp-state{border-radius:10px;padding:12px 14px;font-size:14px;margin-bottom:12px}
  .resp-state.ok{background:var(--done-bg);color:var(--done)}
  .resp-state.pp{background:var(--doing-bg);color:var(--doing)}
  .resp-state .note{color:var(--ink);margin-top:6px;font-weight:400}
  .resp-btns{display:flex;gap:8px;flex-wrap:wrap}
  .rbtn{flex:1;min-width:150px;display:inline-flex;align-items:center;justify-content:center;gap:8px;height:46px;border-radius:10px;font-size:14.5px;font-weight:600;border:.5px solid var(--line-strong);background:var(--surface);color:var(--ink);cursor:pointer}
  .rbtn svg{width:18px;height:18px}
  .rbtn.confirm{background:var(--brand);border-color:var(--brand);color:var(--brand-ink)}
  .rbtn.postpone{color:var(--doing);border-color:color-mix(in srgb,var(--doing) 45%,transparent)}
  .rbtn.postpone:hover{background:var(--doing-bg)}
  .pp-form{margin-top:12px}
  .pp-form textarea{width:100%;border:.5px solid var(--line-strong);border-radius:10px;background:var(--bg);padding:10px 12px;font-family:inherit;font-size:14px;min-height:80px;resize:vertical;box-sizing:border-box}
  .pp-form .send{margin-top:8px;width:100%;height:44px;border:0;border-radius:10px;background:var(--doing);color:#fff;font-weight:600;font-size:14.5px;cursor:pointer}
</style>
</head>
<body>
  <div class="brand">
    <span class="mk"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></span>
    <b><?= e(APP_NAME) ?></b>
  </div>

  <?php if ($shareFlash): ?><div class="flashmsg"><?= e($shareFlash['msg']) ?></div><?php endif; ?>
  <?php if (!$ok): ?>
    <div class="bad">
      <h1>الرابط غير صالح</h1>
      <p>هذا الموعد غير موجود أو أُلغيت مشاركته.</p>
    </div>
  <?php else: ?>
    <div class="card">
      <div class="head">
        <div class="datebox">
          <div class="d"><?= $ts ? (int) date('j', $ts) : '—' ?></div>
          <div class="mo"><?= $ts ? e($months[(int) date('n', $ts) - 1]) : '' ?></div>
        </div>
        <div>
          <h1><?= e($appt['subject']) ?></h1>
          <div class="when"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= e($fmtFull($appt['starts_at'])) ?></div>
          <?php if ($status !== 'مجدول'): ?><span class="ast ast-<?= e(appt_status_slug($status)) ?>"><?= e($status) ?></span><?php endif; ?>
        </div>
      </div>
      <div class="body">
        <?php if ($status === 'تأجّل' && !empty($appt['postponed_to'])): ?>
          <div class="postponed">↪ أُجّل إلى: <?= e($fmtFull($appt['postponed_to'])) ?></div>
        <?php endif; ?>
        <?php if (!empty($appt['with_whom'])): ?><div class="rowline"><span class="k">مع</span><span class="v"><?= e($appt['with_whom']) ?></span></div><?php endif; ?>
        <?php if (!empty($appt['location'])): ?><div class="rowline"><span class="k">المكان</span><span class="v"><?= e($appt['location']) ?></span></div><?php endif; ?>
        <?php if (!empty($appt['creator_name'])): ?><div class="rowline"><span class="k">بتنظيم</span><span class="v"><?= e($appt['creator_name']) ?></span></div><?php endif; ?>
        <?php if (!empty($appt['notes'])): ?><div class="rowline"><span class="k">ملاحظات</span><span class="v"><?= nl2br(e($appt['notes'])) ?></span></div><?php endif; ?>
      </div>
      <div class="foot">دعوة موعد — <?= e(APP_NAME) ?></div>
    </div>

    <div class="resp">
      <h2>ردّك على الموعد</h2>
      <?php if ($rstatus === 'confirmed'): ?>
        <div class="resp-state ok">✓ أكّدتَ استلام هذا الموعد.<?php if (!empty($appt['recipient_at'])): ?> <span style="opacity:.7">(<?= e(fmt_dt($appt['recipient_at'])) ?>)</span><?php endif; ?></div>
      <?php elseif ($rstatus === 'postpone'): ?>
        <div class="resp-state pp">↪ طلبتَ تأجيل الموعد.<?php if (!empty($appt['recipient_note'])): ?><div class="note"><?= nl2br(e($appt['recipient_note'])) ?></div><?php endif; ?></div>
      <?php endif; ?>

      <div class="resp-btns">
        <form method="post" action="index.php?r=share_respond" style="flex:1;min-width:150px">
          <?= csrf_field() ?>
          <input type="hidden" name="t" value="<?= e($appt['share_token']) ?>">
          <input type="hidden" name="action" value="confirm">
          <button type="submit" class="rbtn confirm">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
            تأكيد استلام الموعد
          </button>
        </form>
        <button type="button" class="rbtn postpone" onclick="document.getElementById('ppForm').hidden=!document.getElementById('ppForm').hidden">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
          طلب تأجيل
        </button>
      </div>

      <form class="pp-form" id="ppForm" method="post" action="index.php?r=share_respond" <?= $rstatus === 'postpone' ? '' : 'hidden' ?>>
        <?= csrf_field() ?>
        <input type="hidden" name="t" value="<?= e($appt['share_token']) ?>">
        <input type="hidden" name="action" value="postpone">
        <textarea name="note" placeholder="سبب التأجيل أو ملاحظة (اختياري)"><?= e($rstatus === 'postpone' ? ($appt['recipient_note'] ?? '') : '') ?></textarea>
        <button type="submit" class="send">إرسال طلب التأجيل</button>
      </form>
    </div>
  <?php endif; ?>
</body>
</html>
