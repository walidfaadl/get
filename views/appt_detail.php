<?php /** @var array $appt */
$me = current_user();
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
      <span class="appt-pill <?= $past ? 'past' : 'up' ?>"><?= $past ? 'منتهٍ' : 'قادم' ?></span>
    </div>
    <div class="appt-hero-when"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= e($full) ?></div>
  </div>
</div>

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
