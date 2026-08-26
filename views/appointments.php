<?php /** @var array $appointments */
$me = current_user();
$months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
?>
<div class="toolbar">
  <div class="muted"><?= count($appointments) ?> موعد</div>
  <a class="btn-gold" href="<?= e(url('appt_new')) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    موعد جديد
  </a>
</div>

<?php if (!$appointments): ?>
  <div class="empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    <p>لا توجد مواعيد بعد — أضِف أول موعد.</p>
  </div>
<?php else: ?>
  <div class="appt-list">
    <?php foreach ($appointments as $a):
      $ts = strtotime($a['starts_at']);
      $past = $ts !== false && $ts < time();
      $canEdit = appointment_can_edit($a, $me);
    ?>
      <div class="appt <?= $past ? 'past' : '' ?>">
        <div class="appt-date">
          <div class="d"><?= $ts ? (int) date('j', $ts) : '—' ?></div>
          <div class="mo"><?= $ts ? e($months[(int) date('n', $ts) - 1]) : '' ?></div>
          <div class="t"><?= $ts ? date('H:i', $ts) : '' ?></div>
        </div>
        <div class="appt-body">
          <div class="appt-subject"><?= e($a['subject']) ?></div>
          <div class="appt-meta">
            <?php if ($a['with_whom']): ?><span><b>مع:</b> <?= e($a['with_whom']) ?></span><?php endif; ?>
            <?php if ($a['location']): ?><span>📍 <?= e($a['location']) ?></span><?php endif; ?>
            <span>👤 <?= e($a['creator_name'] ?: '—') ?></span>
          </div>
          <?php if ($a['notes']): ?><div class="appt-notes"><?= nl2br(e($a['notes'])) ?></div><?php endif; ?>
          <?php if ($a['shared_name']): ?><span class="appt-share">🤝 بمشاركة: <?= e($a['shared_name']) ?></span><?php endif; ?>
        </div>
        <?php if ($canEdit): ?>
        <div class="appt-actions">
          <a class="btn-soft sm" href="<?= e(url('appt_edit', ['id' => $a['id']])) ?>">تعديل</a>
          <form method="post" action="<?= e(url('appt_delete')) ?>" onsubmit="return confirm('حذف هذا الموعد؟')">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
            <button type="submit" class="btn-danger sm">حذف</button>
          </form>
        </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
