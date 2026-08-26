<?php /** @var array $task @var array $comments */
$answered = $task['replied_at'] && in_array($task['status'], ['تمت', 'لم تتم', 'قيد التنفيذ'], true);
?>
<div class="detail-head">
  <a class="back" href="<?= e(url('tasks')) ?>">→ رجوع للمهام</a>
  <?php if (is_manager()): ?>
  <div class="detail-actions">
    <a class="btn-soft" href="<?= e(url('edit', ['id' => $task['id']])) ?>">تعديل</a>
    <form method="post" action="<?= e(url('delete')) ?>" onsubmit="return confirm('حذف هذه المهمة نهائياً؟')">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
      <button type="submit" class="btn-danger">حذف</button>
    </form>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="detail-title-row">
    <h2><?= e($task['title']) ?></h2>
    <span class="badge s-<?= e(status_slug($task['status'])) ?>"><?= e($task['status']) ?></span>
  </div>

  <div class="detail-grid">
    <div class="cell"><div class="k">الأولوية</div><div class="val"><span class="prio prio-<?= e(prio_slug($task['priority'])) ?>"><?= e($task['priority']) ?></span></div></div>
    <?php if ($task['department']): ?><div class="cell"><div class="k">القسم</div><div class="val"><?= e($task['department']) ?></div></div><?php endif; ?>
    <?php if ($task['assignee_name']): ?><div class="cell"><div class="k">المكلَّف</div><div class="val"><?= e($task['assignee_name']) ?></div></div><?php endif; ?>
    <?php if ($task['due_date']): ?><div class="cell"><div class="k">تاريخ الاستحقاق</div><div class="val"><?= e(fmt_date($task['due_date'])) ?></div></div><?php endif; ?>
    <div class="cell"><div class="k">أنشأها</div><div class="val"><?= e($task['creator_name'] ?: 'المدير') ?></div></div>
    <div class="cell"><div class="k">تاريخ الإنشاء</div><div class="val"><?= e(fmt_date($task['created_at'])) ?></div></div>
  </div>

  <div class="block">
    <h3>تفاصيل المطلوب</h3>
    <div class="txt"><?= nl2br(e($task['details'])) ?></div>
  </div>
  <?php if ($task['manager_note']): ?>
  <div class="block">
    <h3>ملاحظة المدير</h3>
    <div class="txt note"><?= nl2br(e($task['manager_note'])) ?></div>
  </div>
  <?php endif; ?>
</div>

<!-- تعقيب مدير القسم -->
<div class="card">
  <h3 class="card-h">التعقيب على المهمة</h3>
  <?php if (is_manager()): ?>
    <?php if ($answered): ?>
      <div class="reply-box answered <?= $task['status'] === 'لم تتم' ? 'neg' : '' ?>">
        <span class="badge s-<?= e(status_slug($task['status'])) ?>"><?= e($task['status']) ?></span>
        <div class="txt"><?= $task['reply'] ? nl2br(e($task['reply'])) : '<span class="muted">— بدون ملاحظات —</span>' ?></div>
        <div class="reply-meta">⏱ <?= e($task['replied_by'] ?: 'المُكلَّف') ?> • <?= e(fmt_dt($task['replied_at'])) ?></div>
      </div>
    <?php else: ?>
      <div class="reply-box"><span class="muted">بانتظار تعقيب المُكلَّف على هذه المهمة.</span></div>
    <?php endif; ?>

    <form method="post" action="<?= e(url('task_status')) ?>" class="status-set">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
      <div class="status-set-label">تعديل الحالة (المدير):</div>
      <div class="status-choices">
        <?php foreach (STATUSES as $s): ?>
          <label class="<?= $task['status'] === $s ? 'sel' : '' ?>">
            <input type="radio" name="status" value="<?= e($s) ?>" <?= $task['status'] === $s ? 'checked' : '' ?>><?= e($s) ?>
          </label>
        <?php endforeach; ?>
      </div>
      <button type="submit" class="btn-primary">حفظ الحالة</button>
    </form>
  <?php else: ?>
    <form method="post" action="<?= e(url('reply')) ?>" class="reply-form">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
      <div class="status-choices">
        <?php foreach (REPLY_STATUSES as $s): ?>
          <label class="<?= $task['status'] === $s ? 'sel' : '' ?>">
            <input type="radio" name="status" value="<?= e($s) ?>" <?= $task['status'] === $s ? 'checked' : '' ?>><?= e($s) ?>
          </label>
        <?php endforeach; ?>
      </div>
      <textarea name="reply" placeholder="اكتب ملاحظاتك: ما تم إنجازه، أو سبب عدم الإنجاز..."><?= e($task['reply'] ?? '') ?></textarea>
      <?php if ($task['replied_at']): ?><div class="reply-meta">آخر تعقيب: <?= e(fmt_dt($task['replied_at'])) ?></div><?php endif; ?>
      <button type="submit" class="btn-primary">حفظ التعقيب</button>
    </form>
  <?php endif; ?>
</div>

<!-- نقاش المهمة -->
<div class="card" id="comments">
  <h3 class="card-h">النقاش والملاحظات</h3>
  <?php if (!$comments): ?>
    <p class="muted">لا توجد ملاحظات بعد.</p>
  <?php else: ?>
    <div class="comments">
      <?php foreach ($comments as $c): ?>
        <div class="comment <?= $c['role'] === 'manager' ? 'from-manager' : 'from-head' ?>">
          <div class="comment-head"><strong><?= e($c['author']) ?></strong> <span class="tag"><?= e(ROLES[$c['role']] ?? '') ?></span> <span class="when"><?= e(fmt_dt($c['created_at'])) ?></span></div>
          <div class="comment-body"><?= nl2br(e($c['body'])) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <form method="post" action="<?= e(url('comment')) ?>" class="comment-form">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
    <textarea name="body" placeholder="أضف ملاحظة أو رداً..." required></textarea>
    <button type="submit" class="btn-soft">إرسال</button>
  </form>
</div>
