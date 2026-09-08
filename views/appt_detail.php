<?php /** @var array $appt @var ?string $shareToken */
$me = current_user();
$shareToken = $shareToken ?? null;
$ts = strtotime($appt['starts_at']);
$past = $ts !== false && $ts < time();
$canEdit = appointment_can_edit($appt, $me);
$months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
$dayName = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
$full = $ts ? ($dayName[(int) date('w', $ts)] . ' ' . (int) date('j', $ts) . ' ' . $months[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts) . ' — ' . date('H:i', $ts)) : $appt['starts_at'];
?>
<div class="detail-head">
  <a class="back" href="<?= e(url('appointments')) ?>">→ رجوع للمواعيد</a>
  <?php if ($canEdit): ?>
  <div class="detail-actions">
    <a class="btn-soft" href="<?= e(url('appt_edit', ['id' => $appt['id']])) ?>">تعديل</a>
    <form method="post" action="<?= e(url('appt_delete')) ?>" onsubmit="return confirm('حذف هذا الموعد؟')">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $appt['id'] ?>">
      <button type="submit" class="btn-danger">حذف</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<div class="card appt-hero">
  <div class="appt-hero-date">
    <div class="d"><?= $ts ? (int) date('j', $ts) : '—' ?></div>
    <div class="mo"><?= $ts ? e($months[(int) date('n', $ts) - 1]) : '' ?></div>
  </div>
  <div class="appt-hero-body">
    <div class="appt-hero-top">
      <h2><?= e($appt['subject']) ?></h2>
      <span class="ast ast-<?= e(appt_status_slug($appt['status'] ?? 'مجدول')) ?>"><?= e($appt['status'] ?? 'مجدول') ?></span>
    </div>
    <div class="appt-hero-when"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= e($full) ?></div>
    <?php if (($appt['status'] ?? '') === 'تأجّل' && !empty($appt['postponed_to'])): ?>
      <div class="appt-hero-postponed">↪ أُجّل إلى: <?= e(fmt_dt($appt['postponed_to'])) ?></div>
    <?php endif; ?>
  </div>
</div>

<?php if ($shareToken):
  $shareUrl = absolute_url('share', ['t' => $shareToken]);
  $shareText = 'موعد: ' . $appt['subject'] . ' — ' . $full;
  $waHref = 'https://wa.me/?text=' . rawurlencode($shareText . "\n" . $shareUrl);
  $tgHref = 'https://t.me/share/url?url=' . rawurlencode($shareUrl) . '&text=' . rawurlencode($shareText);
?>
<div class="card">
  <h3 class="card-h">مشاركة الموعد مع صاحبه</h3>
  <p class="muted" style="margin:-6px 0 12px;font-size:13px">أرسِل هذا الرابط لصاحب الموعد عبر الرسائل — يفتح صفحة الموعد للقراءة دون تسجيل دخول.</p>
  <div class="share-link">
    <input type="text" id="shareUrl" readonly value="<?= e($shareUrl) ?>">
    <button type="button" class="btn-soft" data-copy="#shareUrl">نسخ الرابط</button>
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
    <button type="button" class="share-btn native" data-share-url="<?= e($shareUrl) ?>" data-share-text="<?= e($shareText) ?>" data-share-title="<?= e($appt['subject']) ?>" hidden>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
      مشاركة
    </button>
  </div>
</div>
<?php endif; ?>

<?php if ($canEdit): ?>
<div class="card">
  <h3 class="card-h">حالة الموعد</h3>
  <form method="post" action="<?= e(url('appt_status')) ?>" class="status-set">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $appt['id'] ?>">
    <div class="status-choices" id="apptStatusChoices">
      <?php foreach (APPT_STATUSES as $s): ?>
        <label class="<?= ($appt['status'] ?? 'مجدول') === $s ? 'sel' : '' ?>" data-s="<?= e($s) ?>">
          <input type="radio" name="status" value="<?= e($s) ?>" <?= ($appt['status'] ?? 'مجدول') === $s ? 'checked' : '' ?>><?= e($s) ?>
        </label>
      <?php endforeach; ?>
    </div>
    <div class="field postpone-field" id="postponeField" style="<?= ($appt['status'] ?? '') === 'تأجّل' ? '' : 'display:none' ?>">
      <label>تاريخ التأجيل <span class="hint">(اختياري — اتركه فارغاً إن لم يُحدَّد بعد)</span></label>
      <input type="datetime-local" name="postponed_to" value="<?= e(!empty($appt['postponed_to']) ? date('Y-m-d\TH:i', strtotime($appt['postponed_to'])) : '') ?>">
    </div>
    <button type="submit" class="btn-primary">حفظ الحالة</button>
  </form>
</div>
<script>
(function(){
  var box = document.getElementById('apptStatusChoices');
  var field = document.getElementById('postponeField');
  if (!box || !field) return;
  box.addEventListener('click', function(e){
    var lbl = e.target.closest('label'); if(!lbl) return;
    field.style.display = (lbl.getAttribute('data-s') === 'تأجّل') ? '' : 'none';
  });
})();
</script>
<?php endif; ?>

<div class="card">
  <div class="detail-grid">
    <?php if ($appt['with_whom']): ?><div class="cell"><div class="k">مع مَن</div><div class="val"><?= e($appt['with_whom']) ?></div></div><?php endif; ?>
    <?php if ($appt['location']): ?><div class="cell"><div class="k">المكان</div><div class="val"><?= e($appt['location']) ?></div></div><?php endif; ?>
    <div class="cell"><div class="k">أنشأه</div><div class="val"><?= e($appt['creator_name'] ?: '—') ?></div></div>
    <?php if ($appt['shared_name']): ?><div class="cell"><div class="k">بمشاركة</div><div class="val">🤝 <?= e($appt['shared_name']) ?></div></div><?php endif; ?>
  </div>
  <?php if ($appt['notes']): ?>
  <div class="block">
    <h3>ملاحظات</h3>
    <div class="txt"><?= nl2br(e($appt['notes'])) ?></div>
  </div>
  <?php endif; ?>
</div>
