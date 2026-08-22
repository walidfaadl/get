<?php /** @var array $tasks @var array $counts @var string $status @var string $search */ ?>
<div class="stats">
  <a class="stat all <?= $status === '' ? 'sel' : '' ?>"  href="<?= e(url('tasks')) ?>"><div class="v"><?= (int) $counts['total'] ?></div><div class="l">الكل</div></a>
  <a class="stat new <?= $status === 'جديدة' ? 'sel' : '' ?>" href="<?= e(url('tasks', ['status' => 'جديدة'])) ?>"><div class="v"><?= (int) $counts['جديدة'] ?></div><div class="l">جديدة</div></a>
  <a class="stat prog <?= $status === 'قيد التنفيذ' ? 'sel' : '' ?>" href="<?= e(url('tasks', ['status' => 'قيد التنفيذ'])) ?>"><div class="v"><?= (int) $counts['قيد التنفيذ'] ?></div><div class="l">قيد التنفيذ</div></a>
  <a class="stat done <?= $status === 'تمت' ? 'sel' : '' ?>" href="<?= e(url('tasks', ['status' => 'تمت'])) ?>"><div class="v"><?= (int) $counts['تمت'] ?></div><div class="l">تمت</div></a>
  <a class="stat fail <?= $status === 'لم تتم' ? 'sel' : '' ?>" href="<?= e(url('tasks', ['status' => 'لم تتم'])) ?>"><div class="v"><?= (int) $counts['لم تتم'] ?></div><div class="l">لم تتم</div></a>
</div>

<div class="toolbar">
  <form class="search" method="get" action="index.php">
    <input type="hidden" name="r" value="tasks">
    <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($search) ?>" placeholder="ابحث في المهام...">
    <button type="submit">بحث</button>
  </form>
  <?php if (is_manager()): ?>
  <a class="btn-primary" href="<?= e(url('new')) ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>مهمة جديدة</a>
  <?php endif; ?>
</div>

<?php if (!$tasks): ?>
  <div class="empty">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
    <p><?= is_manager() ? 'لا توجد مهام بعد — أنشئ أول مهمة.' : 'لا توجد مهام مسندة إليك في هذا التصنيف.' ?></p>
  </div>
<?php else: ?>
  <div class="task-list">
    <?php foreach ($tasks as $t): ?>
      <a class="task p-<?= e(prio_slug($t['priority'])) ?>" href="<?= e(url('task', ['id' => $t['id']])) ?>">
        <div class="task-top">
          <div class="task-title"><?= e($t['title']) ?></div>
          <span class="badge s-<?= e(status_slug($t['status'])) ?>"><?= e($t['status']) ?></span>
        </div>
        <div class="task-meta">
          <span class="prio prio-<?= e(prio_slug($t['priority'])) ?>"><?= e($t['priority']) ?></span>
          <?php if ($t['department']): ?><span>🏢 <?= e($t['department']) ?></span><?php endif; ?>
          <?php if ($t['assignee_name']): ?><span>👤 <?= e($t['assignee_name']) ?></span><?php endif; ?>
          <?php if ($t['due_date']): ?><span>📅 <?= e(fmt_date($t['due_date'])) ?></span><?php endif; ?>
        </div>
        <div class="task-excerpt"><?= e(mb_substr($t['details'], 0, 160)) ?><?= mb_strlen($t['details']) > 160 ? '…' : '' ?></div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
