<?php /** @var array $appointments @var string $view @var array $counts */
$months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
?>
<div class="toolbar">
  <div class="chips">
    <a class="chip <?= $view === 'upcoming' ? 'active' : '' ?>" href="<?= e(url('appointments', ['view' => 'upcoming'])) ?>">القادمة <span class="chip-n"><?= (int) $counts['upcoming'] ?></span></a>
    <a class="chip <?= $view === 'past' ? 'active' : '' ?>" href="<?= e(url('appointments', ['view' => 'past'])) ?>">السابقة <span class="chip-n"><?= (int) $counts['past'] ?></span></a>
    <a class="chip <?= $view === 'all' ? 'active' : '' ?>" href="<?= e(url('appointments', ['view' => 'all'])) ?>">الكل <span class="chip-n"><?= (int) $counts['all'] ?></span></a>
  </div>
  <a class="btn-gold" href="<?= e(url('appt_new')) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    موعد جديد
  </a>
</div>

<?php if (!$appointments): ?>
  <div class="empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
    <p><?= $view === 'past' ? 'لا توجد مواعيد سابقة.' : ($view === 'upcoming' ? 'لا توجد مواعيد قادمة — أضِف موعداً.' : 'لا توجد مواعيد بعد.') ?></p>
  </div>
<?php else: ?>
  <div class="appt-list">
    <?php foreach ($appointments as $a):
      $ts = strtotime($a['starts_at']);
      $past = $ts !== false && $ts < time();
    ?>
      <a class="appt <?= $past ? 'past' : '' ?>" href="<?= e(url('appt', ['id' => $a['id']])) ?>">
        <div class="appt-date">
          <div class="d"><?= $ts ? (int) date('j', $ts) : '—' ?></div>
          <div class="mo"><?= $ts ? e($months[(int) date('n', $ts) - 1]) : '' ?></div>
          <div class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= $ts ? date('H:i', $ts) : '' ?></div>
        </div>
        <?php $ast = $a['status'] ?? 'مجدول'; ?>
        <div class="appt-body">
          <div class="appt-subject"><?= e($a['subject']) ?>
            <?php if ($ast !== 'مجدول'): ?>
              <span class="ast ast-<?= e(appt_status_slug($ast)) ?>"><?= e($ast) ?></span>
            <?php else: ?>
              <span class="appt-pill <?= $past ? 'past' : 'up' ?>"><?= $past ? 'منتهٍ' : 'قادم' ?></span>
            <?php endif; ?>
          </div>
          <div class="appt-meta">
            <?php if ($a['with_whom']): ?><span><b>مع:</b> <?= e($a['with_whom']) ?></span><?php endif; ?>
            <?php if ($a['location']): ?><span>📍 <?= e($a['location']) ?></span><?php endif; ?>
            <?php if ($ast === 'تأجّل' && !empty($a['postponed_to'])): ?><span>↪ أُجّل إلى <?= e(fmt_date($a['postponed_to'])) ?></span><?php endif; ?>
            <span>👤 <?= e($a['creator_name'] ?: '—') ?></span>
          </div>
          <?php if ($a['shared_name']): ?><span class="appt-share">🤝 بمشاركة: <?= e($a['shared_name']) ?></span><?php endif; ?>
        </div>
        <div class="appt-go"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
