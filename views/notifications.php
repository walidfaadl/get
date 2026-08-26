<?php /** @var array $items */ ?>
<div class="card">
  <h3 class="card-h">التنبيهات</h3>
  <?php if (!$items): ?>
    <p class="muted">لا توجد تنبيهات.</p>
  <?php else: ?>
    <div class="notif-list">
      <?php foreach ($items as $n):
        $link = url($n['route'], $n['ref_id'] ? ['id' => (int) $n['ref_id']] : []);
        $icon = $n['type'] === 'appointment'
          ? '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'
          : '<path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>';
      ?>
        <a class="notif <?= $n['is_read'] ? '' : 'unread' ?> nt-<?= $n['type'] === 'appointment' ? 'appt' : 'task' ?>" href="<?= e($link) ?>">
          <span class="notif-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><?= $icon ?></svg></span>
          <span class="notif-body">
            <span class="notif-title"><?= e($n['title']) ?></span>
            <?php if ($n['body']): ?><span class="notif-text"><?= e($n['body']) ?></span><?php endif; ?>
            <span class="notif-when"><?= e(fmt_dt($n['created_at'])) ?></span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
