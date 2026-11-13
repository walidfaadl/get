<?php /** @var array $o @var array $attendees @var bool $canEdit */
$me = current_user();
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
