<?php
/** @var array $tasks @var array $counts @var string $status @var string $search
 *  @var string $sort @var int $assignee @var array $assignable */
$sort = $sort ?? 'recent';
$assignee = (int) ($assignee ?? 0);
$assignable = $assignable ?? [];

// روابط الشرائح مع الحفاظ على البحث/الترتيب/المكلَّف
$pillUrl = function (string $st) use ($search, $sort, $assignee) {
    $p = [];
    if ($st !== '')       { $p['status'] = $st; }
    if ($search !== '')   { $p['q'] = $search; }
    if ($sort !== 'recent'){ $p['sort'] = $sort; }
    if ($assignee > 0)    { $p['assignee'] = $assignee; }
    return url('tasks', $p);
};

$pills = [
    ['', 'الكل', (int) $counts['total'], ''],
    ['جديدة', 'جديدة', (int) $counts['جديدة'], ''],
    ['قيد التنفيذ', 'قيد التنفيذ', (int) $counts['قيد التنفيذ'], ''],
    ['تمت', 'تمت', (int) $counts['تمت'], ''],
    ['لم تتم', 'لم تتم', (int) $counts['لم تتم'], ''],
    ['late', 'متأخرة', (int) ($counts['late'] ?? 0), 'p-late'],
];

// عرض صف مهمة
$renderRow = function (array $t) {
    $done = $t['status'] === 'تمت';
    $late = task_is_late($t);
    $slug = status_slug($t['status']);
    $mark = $done
        ? '<span class="row-dot"><span class="chk"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></span></span>'
        : '<span class="row-dot"><span class="d s-' . e($slug) . '"></span></span>';
    $metaParts = [];
    if (!empty($t['department']))    { $metaParts[] = e($t['department']); }
    if (!empty($t['assignee_name'])) { $metaParts[] = e($t['assignee_name']); }
    if (!$metaParts && !empty($t['details'])) { $metaParts[] = e(mb_substr($t['details'], 0, 80)); }
    $meta = implode(' • ', $metaParts);
    $due = fmt_due_rel($t);
    $av = mb_substr(trim((string) ($t['assignee_name'] ?? '؟')) ?: '؟', 0, 1);
    $html  = '<a class="row ' . ($done ? 'is-done' : '') . '" href="' . e(url('task', ['id' => $t['id']])) . '">';
    $html .= $mark;
    $html .= '<span class="row-body"><p class="row-title">' . e($t['title']) . '</p>'
           . ($meta ? '<p class="row-meta">' . $meta . '</p>' : '') . '</span>';
    if (!$done) { $html .= '<span class="prio prio-' . e(prio_slug($t['priority'])) . '">' . e($t['priority']) . '</span>'; }
    if ($due)   { $html .= '<span class="due ' . ($late ? 'late' : '') . '">' . e($due) . '</span>'; }
    $html .= '<span class="avatar" title="' . e($t['assignee_name'] ?? '') . '">' . e($av) . '</span>';
    $html .= '<span class="row-go"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></span>';
    $html .= '</a>';
    return $html;
};
?>
<div class="pills">
  <?php foreach ($pills as [$st, $label, $n, $cls]): ?>
    <a class="pill <?= $cls ?> <?= $status === $st ? 'active' : '' ?>" href="<?= e($pillUrl($st)) ?>"><?= e($label) ?> <span class="n"><?= $n ?></span></a>
  <?php endforeach; ?>
</div>

<div class="toolbar">
  <form class="search" method="get" action="index.php" id="filterForm">
    <input type="hidden" name="r" value="tasks">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="ابحث في المهام" aria-label="بحث">
  </form>
  <?php if (is_manager() && $assignable): ?>
  <select aria-label="تصفية حسب المكلَّف" onchange="document.getElementById('assignee_h').value=this.value;document.getElementById('filterForm').submit()">
    <option value="0">كل المكلَّفين</option>
    <?php foreach ($assignable as $a): ?>
      <option value="<?= (int) $a['id'] ?>" <?= $assignee === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="hidden" name="assignee" id="assignee_h" value="<?= (int) $assignee ?>" form="filterForm">
  <?php endif; ?>
  <select aria-label="ترتيب" onchange="document.getElementById('sort_h').value=this.value;document.getElementById('filterForm').submit()">
    <?php foreach (['recent' => 'الأحدث أولًا', 'due' => 'حسب الموعد', 'priority' => 'حسب الأولوية'] as $k => $lbl): ?>
      <option value="<?= $k ?>" <?= $sort === $k ? 'selected' : '' ?>><?= e($lbl) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="hidden" name="sort" id="sort_h" value="<?= e($sort) ?>" form="filterForm">

  <div class="toolbar-actions">
    <?php if (is_manager()): ?>
    <a class="btn-primary" href="<?= e(url('new')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>مهمة جديدة</a>
    <?php endif; ?>
    <?php if (can_manage_appointments()): ?>
    <a class="btn-gold" href="<?= e(url('appt_new')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>موعد</a>
    <?php endif; ?>
  </div>
</div>

<?php if (!$tasks): ?>
  <div class="empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/></svg>
    <p>لا مهام تطابق هذه الفلترة</p>
    <span><?= is_manager() ? 'غيّر الفلترة أو أضف مهمة جديدة.' : 'لا توجد مهام مسندة إليك هنا.' ?></span>
  </div>
<?php elseif ($sort === 'recent' && !in_array($status, ['تمت', 'late'], true)): ?>
  <?php
  $buckets = [['late', 'متأخرة'], ['today', 'اليوم'], ['week', 'هذا الأسبوع'], ['later', 'لاحقًا'], ['none', 'بلا موعد']];
  foreach ($buckets as [$key, $label]):
    $group = array_filter($tasks, fn($t) => task_bucket($t) === $key);
    if (!$group) continue;
  ?>
    <div class="group">
      <p class="group-label <?= $key === 'late' ? 'late' : '' ?>"><?= e($label) ?></p>
      <div class="rows"><?php foreach ($group as $t) { echo $renderRow($t); } ?></div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div class="rows"><?php foreach ($tasks as $t) { echo $renderRow($t); } ?></div>
<?php endif; ?>
