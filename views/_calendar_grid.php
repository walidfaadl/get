<?php /** جزء التقويم — يتوقّع $y,$m,$byDay (يُضمَّن من agenda.php) */
$months = ['يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
$dows   = ['الأحد','الإثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'];
$first      = mktime(0, 0, 0, $m, 1, $y);
$daysInMon  = (int) date('t', $first);
$startW     = (int) date('w', $first);
$today      = date('Y-m-d');
$prevM = $m === 1 ? 12 : $m - 1; $prevY = $m === 1 ? $y - 1 : $y;
$nextM = $m === 12 ? 1 : $m + 1; $nextY = $m === 12 ? $y + 1 : $y;
$dayEvents = function (string $d) use ($byDay) {
    $list = $byDay[$d] ?? [];
    usort($list, fn($a, $b) => strtotime($a['starts_at']) <=> strtotime($b['starts_at']));
    return $list;
};
$totalCells = (int) (ceil(($startW + $daysInMon) / 7) * 7);
?>
<div class="cal-top">
  <div class="cal-nav">
    <a class="cal-arrow" href="<?= e(url('appointments', ['y' => $prevY, 'm' => $prevM])) ?>" aria-label="الشهر السابق">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
    </a>
    <h2 class="cal-title"><?= e($months[$m - 1]) ?> <?= (int) $y ?></h2>
    <a class="cal-arrow" href="<?= e(url('appointments', ['y' => $nextY, 'm' => $nextM])) ?>" aria-label="الشهر التالي">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
    </a>
    <a class="btn-soft sm" href="<?= e(url('appointments')) ?>">اليوم</a>
  </div>
</div>

<div class="cal-wrap">
  <div class="cal-grid">
    <?php foreach ($dows as $d): ?>
      <div class="cal-dow"><?= e($d) ?></div>
    <?php endforeach; ?>
    <?php for ($i = 0; $i < $totalCells; $i++):
      $dayNum = $i - $startW + 1;
      if ($dayNum < 1 || $dayNum > $daysInMon): ?>
      <div class="cal-cell out"></div>
    <?php else:
      $dateStr = sprintf('%04d-%02d-%02d', $y, $m, $dayNum);
      $isToday = $dateStr === $today;
      $events  = $dayEvents($dateStr); ?>
      <div class="cal-cell<?= $isToday ? ' today' : '' ?>">
        <div class="cal-cell-head">
          <span class="cal-dnum"><?= $dayNum ?></span>
          <a class="cal-add" href="<?= e(url('appt_new', ['date' => $dateStr])) ?>" aria-label="إضافة موعد في هذا اليوم" title="إضافة موعد">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          </a>
        </div>
        <div class="cal-events">
          <?php foreach (array_slice($events, 0, 3) as $a): $ts = strtotime($a['starts_at']); $ast = $a['status'] ?? 'مجدول'; ?>
            <a class="cal-ev ast-<?= e(appt_status_slug($ast)) ?>" href="<?= e(url('appt', ['id' => $a['id']])) ?>" title="<?= e($a['subject']) ?>">
              <span class="cal-ev-t"><?= $ts ? date('H:i', $ts) : '' ?></span>
              <span class="cal-ev-s"><?= e($a['subject']) ?></span>
            </a>
          <?php endforeach; ?>
          <?php if (count($events) > 3): ?>
            <a class="cal-more" href="<?= e(url('appt_new', ['date' => $dateStr])) ?>">+<?= count($events) - 3 ?> أخرى</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; endfor; ?>
  </div>
</div>
