<?php /** @var array $o @var array $attendees @var bool $canEdit @var ?string $shareToken @var bool $viaShare */
$me = current_user();
$shareToken = $shareToken ?? null;
$viaShare = $viaShare ?? false;
$ts = strtotime($o['met_at']);
$months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
$dayName = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
$full = $ts ? ($dayName[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts) . ' — ' . date('H:i', $ts)) : $o['met_at'];
$roleLabel = ['manager' => 'المدير', 'head' => 'مدير القسم', 'member' => 'عضو قسم'];
?>
<div class="detail-head">
  <a class="back" href="<?= e(url('outcomes')) ?>">→ رجوع للمخرجات</a>
  <?php if ($canEdit): ?>
  <div class="detail-actions">
    <a class="btn-soft" href="<?= e(url('outcome_edit', ['id' => $o['id']])) ?>">تعديل</a>
    <form method="post" action="<?= e(url('outcome_delete')) ?>" onsubmit="return confirm('حذف هذه المخرجات؟')">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
      <button type="submit" class="btn-danger">حذف</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<?php if ($viaShare): ?>
<div class="flash flash-ok" style="margin-bottom:16px">تعرض هذه المخرجات عبر رابط مشاركة.</div>
<?php endif; ?>

<div class="card appt-hero">
  <div class="appt-hero-date">
    <div class="d"><?= $ts ? (int) date('j', $ts) : '—' ?></div>
    <div class="mo"><?= $ts ? e($months[(int) date('n', $ts) - 1]) : '' ?></div>
  </div>
  <div class="appt-hero-body">
    <div class="appt-hero-top">
      <h2><?= e($o['title']) ?></h2>
      <?php if (!empty($o['sent_at'])): ?><span class="ast ast-done">أُرسلت للحاضرين</span><?php endif; ?>
    </div>
    <div class="appt-hero-when"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= e($full) ?></div>
  </div>
</div>

<div class="card">
  <h3 class="card-h">الحضور <span class="count-pill"><?= count($attendees) ?></span></h3>
  <?php if (!$attendees): ?>
    <p class="muted">لم يُحدَّد الحاضرون.</p>
  <?php else: ?>
    <div class="att-chips">
      <?php foreach ($attendees as $u): ?>
        <span class="att-chip">
          <span class="ac-av"><?= e(mb_substr($u['name'], 0, 1)) ?></span>
          <span class="ac-main"><span class="ac-name"><?= e($u['name']) ?></span><span class="ac-role"><?= e($roleLabel[$u['role']] ?? $u['role']) ?></span></span>
        </span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <h3 class="card-h">المخرجات</h3>
  <div class="rte-content"><?= $o['body'] ?></div>
</div>

<div class="card">
  <h3 class="card-h">المتابعة</h3>
  <?php if (!empty($o['followup_agreed'])): ?>
    <div class="followup-box yes">
      <span class="ast ast-sched">↻ اتُّفق على موعد متابعة</span>
      <?php if (!empty($o['followup_at'])): ?>
        <div class="txt">الموعد: <b><?= e(fmt_dt($o['followup_at'])) ?></b></div>
      <?php else: ?>
        <div class="txt muted">الموعد يُحدَّد لاحقاً.</div>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="followup-box no">
      <span class="ast ast-noshow">لم يُتّفق على موعد متابعة</span>
    </div>
  <?php endif; ?>
</div>

<?php if ($canEdit && $shareToken):
  $shareUrl = absolute_url('outcome', ['id' => $o['id'], 't' => $shareToken]);
  $shareText = 'مخرجات لقاء: ' . $o['title'];
  $waHref = 'https://wa.me/?text=' . rawurlencode($shareText . "\n" . $shareUrl);
  $tgHref = 'https://t.me/share/url?url=' . rawurlencode($shareUrl) . '&text=' . rawurlencode($shareText);
?>
<div class="card">
  <h3 class="card-h">مشاركة المخرجات عبر رابط</h3>
  <p class="muted" style="margin:-6px 0 12px;font-size:13px">أرسِل هذا الرابط للمعنيّ — يفتحه من حسابه في الموقع (يتطلّب تسجيل الدخول) ويطّلع على المخرجات.</p>
  <div class="share-link">
    <input type="text" id="ocShareUrl" readonly value="<?= e($shareUrl) ?>">
    <button type="button" class="btn-soft" data-copy="#ocShareUrl">نسخ الرابط</button>
  </div>
  <div class="share-btns">
    <a class="share-btn wa" href="<?= e($waHref) ?>" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15l-1.4 5 5.2-1.4A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20zm4.6-6c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8 1-.3.2-.5.1a6.6 6.6 0 0 1-3.3-2.9c-.2-.4.2-.4.6-1.2a.5.5 0 0 0 0-.5l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3A3 3 0 0 0 6.5 9c0 1.8 1.3 3.5 1.5 3.7s2.6 4 6.3 5.4c2.3.8 2.3.5 2.7.5s1.5-.6 1.7-1.2.2-1.1.1-1.2-.2-.2-.5-.3z"/></svg>
      واتساب
    </a>
    <a class="share-btn tg" href="<?= e($tgHref) ?>" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.9 4.3 18.7 19c-.2 1-.9 1.3-1.8.8l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2L6.4 13 1.6 11.5c-1-.3-1-1 .2-1.5l19-7.3c.9-.3 1.6.2 1.3 1.6z"/></svg>
      تيليجرام
    </a>
    <button type="button" class="share-btn native" data-share-url="<?= e($shareUrl) ?>" data-share-text="<?= e($shareText) ?>" data-share-title="<?= e($o['title']) ?>" hidden>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
      مشاركة
    </button>
  </div>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<div class="card">
  <h3 class="card-h">إرسال المخرجات للمتابعة</h3>
  <p class="muted" style="margin:-6px 0 14px;font-size:13px">يُرسَل نصّ المخرجات إلى الحاضرين (تنبيه داخل النظام + بريد لمن له بريد) للمتابعة.</p>
  <?php if (!empty($o['sent_at'])): ?>
    <div class="sent-note">✓ أُرسلت آخر مرة: <?= e(fmt_dt($o['sent_at'])) ?></div>
  <?php endif; ?>
  <form method="post" action="<?= e(url('outcome_send')) ?>" onsubmit="return confirm('إرسال المخرجات إلى <?= count($attendees) ?> من الحاضرين؟')">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
    <button type="submit" class="btn-primary" <?= $attendees ? '' : 'disabled' ?>>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      <?= !empty($o['sent_at']) ? 'إعادة الإرسال للحاضرين' : 'إرسال إلى الحاضرين' ?>
    </button>
    <?php if (!$attendees): ?><span class="hint" style="margin-inline-start:10px">أضِف الحاضرين أولاً.</span><?php endif; ?>
  </form>
</div>
<?php endif; ?>
