<?php /** @var array $counts @var array $byDept @var array $byAssignee @var array $overdue */
$total = (int) $counts['total'];
$done  = (int) $counts['تمت'];
$rate  = $total > 0 ? round($done * 100 / $total) : 0;
$pct = fn($n, $d) => $d > 0 ? round($n * 100 / $d) : 0;
?>
<!-- ملخص علوي -->
<div class="stats">
  <div class="stat all"><div class="v"><?= $total ?></div><div class="l">إجمالي المهام</div></div>
  <div class="stat new"><div class="v"><?= (int) $counts['جديدة'] ?></div><div class="l">جديدة</div></div>
  <div class="stat prog"><div class="v"><?= (int) $counts['قيد التنفيذ'] ?></div><div class="l">قيد التنفيذ</div></div>
  <div class="stat done"><div class="v"><?= $done ?></div><div class="l">تمت</div></div>
  <div class="stat fail"><div class="v"><?= (int) $counts['لم تتم'] ?></div><div class="l">لم تتم</div></div>
</div>

<!-- نسبة الإنجاز + المتأخرة -->
<div class="two-col">
  <div class="card">
    <h3 class="card-h">نسبة الإنجاز العامة</h3>
    <div class="rate-row">
      <div class="rate-num"><?= $rate ?>%</div>
      <div class="rate-bar"><span style="width:<?= $rate ?>%"></span></div>
    </div>
    <p class="muted"><?= $done ?> مهمة منجزة من أصل <?= $total ?>.</p>
  </div>
  <div class="card">
    <h3 class="card-h">المهام المتأخرة <span class="count-pill <?= count($overdue) ? 'danger' : '' ?>"><?= count($overdue) ?></span></h3>
    <?php if (!$overdue): ?>
      <p class="muted">لا توجد مهام متأخرة عن موعدها. 👍</p>
    <?php else: ?>
      <div class="overdue-list">
        <?php foreach ($overdue as $t): ?>
          <a class="overdue-row" href="<?= e(url('task', ['id' => $t['id']])) ?>">
            <span class="ov-title"><?= e($t['title']) ?></span>
            <span class="ov-meta">
              <?php if ($t['assignee_name']): ?><span>👤 <?= e($t['assignee_name']) ?></span><?php endif; ?>
              <span class="ov-date">📅 <?= e(fmt_date($t['due_date'])) ?></span>
              <span class="badge s-<?= e(status_slug($t['status'])) ?>"><?= e($t['status']) ?></span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- حسب القسم -->
<div class="card">
  <h3 class="card-h">التوزيع حسب القسم</h3>
  <?php if (!$byDept): ?><p class="muted">لا بيانات.</p><?php else: ?>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>القسم</th><th>الإجمالي</th><th>جديدة</th><th>قيد التنفيذ</th><th>تمت</th><th>لم تتم</th><th>الإنجاز</th></tr></thead>
    <tbody>
      <?php foreach ($byDept as $d): $t = (int) $d['total']; $dn = (int) $d['done']; ?>
      <tr>
        <td class="strong"><?= e($d['dep']) ?></td>
        <td><?= $t ?></td>
        <td><?= (int) $d['new_'] ?></td>
        <td><?= (int) $d['prog'] ?></td>
        <td class="c-done"><?= $dn ?></td>
        <td class="c-fail"><?= (int) $d['fail'] ?></td>
        <td class="rate-cell">
          <div class="mini-bar"><span style="width:<?= $pct($dn, $t) ?>%"></span></div>
          <span class="mini-pct"><?= $pct($dn, $t) ?>%</span>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>

<!-- حسب مدير القسم -->
<div class="card">
  <h3 class="card-h">أداء مدراء الأقسام</h3>
  <?php if (!$byAssignee): ?><p class="muted">لا يوجد مدراء أقسام بعد.</p><?php else: ?>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>مدير القسم</th><th>القسم</th><th>المسندة</th><th>مفتوحة</th><th>تمت</th><th>لم تتم</th><th>الإنجاز</th></tr></thead>
    <tbody>
      <?php foreach ($byAssignee as $a): $t = (int) $a['total']; $dn = (int) $a['done']; ?>
      <tr>
        <td class="strong"><?= e($a['name']) ?></td>
        <td><?= e($a['department'] ?: '—') ?></td>
        <td><?= $t ?></td>
        <td><?= (int) $a['open_'] ?></td>
        <td class="c-done"><?= $dn ?></td>
        <td class="c-fail"><?= (int) $a['fail'] ?></td>
        <td class="rate-cell">
          <div class="mini-bar"><span style="width:<?= $pct($dn, $t) ?>%"></span></div>
          <span class="mini-pct"><?= $pct($dn, $t) ?>%</span>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
