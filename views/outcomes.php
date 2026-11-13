<?php /** @var array $outcomes @var bool $canCreate */
$months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
?>
<div class="toolbar">
  <div class="muted" style="flex:1;font-size:13px">مخرجات اللقاءات المكتوبة والمتابعة عليها.</div>
  <?php if ($canCreate): ?>
  <a class="btn-gold" href="<?= e(url('outcome_new')) ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    مخرجات جديدة
  </a>
  <?php endif; ?>
</div>

<?php if (!$outcomes): ?>
  <div class="empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>
    <p><?= $canCreate ? 'لا توجد مخرجات بعد — أضِف أول مخرجات لقاء.' : 'لا توجد مخرجات متاحة لك بعد.' ?></p>
  </div>
<?php else: ?>
  <div class="appt-list">
    <?php foreach ($outcomes as $o):
      $ts = strtotime($o['met_at']);
    ?>
      <a class="appt" href="<?= e(url('outcome', ['id' => $o['id']])) ?>">
        <div class="appt-date">
          <div class="d"><?= $ts ? (int) date('j', $ts) : '—' ?></div>
          <div class="mo"><?= $ts ? e($months[(int) date('n', $ts) - 1]) : '' ?></div>
          <div class="t"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg><?= $ts ? date('H:i', $ts) : '' ?></div>
        </div>
        <div class="appt-body">
          <div class="appt-subject"><?= e($o['title']) ?>
            <?php if (!empty($o['sent_at'])): ?><span class="ast ast-done">أُرسلت</span><?php endif; ?>
          </div>
          <div class="appt-meta">
            <span>👥 <?= (int) ($o['attendee_count'] ?? 0) ?> حاضر</span>
            <span>✍ <?= e($o['creator_name'] ?: '—') ?></span>
            <?php if (!empty($o['followup_agreed'])): ?>
              <span class="oc-follow yes">↻ متابعة<?= !empty($o['followup_at']) ? ': ' . e(fmt_date($o['followup_at'])) : '' ?></span>
            <?php else: ?>
              <span class="oc-follow no">بلا متابعة</span>
            <?php endif; ?>
          </div>
        </div>
        <div class="appt-go"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg></div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
