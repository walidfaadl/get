<?php /** @var array $task @var array $comments @var bool $canAct @var bool $viaShare @var ?string $shareToken */
$me = current_user();
$canAct     = $canAct ?? true;
$viaShare   = $viaShare ?? false;
$shareToken = $shareToken ?? null;
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

<?php if ($viaShare): ?>
<div class="flash flash-ok" style="margin-bottom:16px">تعرض هذه المهمة عبر رابط مشاركة.</div>
<?php endif; ?>

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

<?php if ($shareToken):
  $shareUrl = absolute_url('task', ['id' => $task['id'], 't' => $shareToken]);
  $who = $task['assignee_name'] ? ('مع ' . $task['assignee_name']) : 'مع المكلَّف';
  $shareText = 'مهمة: ' . $task['title'];
  $waHref = 'https://wa.me/?text=' . rawurlencode($shareText . "\n" . $shareUrl);
  $tgHref = 'https://t.me/share/url?url=' . rawurlencode($shareUrl) . '&text=' . rawurlencode($shareText);
?>
<div class="card">
  <h3 class="card-h">مشاركة المهمة <?= e($who) ?></h3>
  <p class="muted" style="margin:-6px 0 12px;font-size:13px">أرسِل الرابط للمكلَّف عبر واتساب أو تيليجرام — يفتحه من حسابه في الموقع ويطّلع على المهمة ويعقّب عليها.</p>
  <div class="share-link">
    <input type="text" id="taskShareUrl" readonly value="<?= e($shareUrl) ?>">
    <button type="button" class="btn-soft" data-copy="#taskShareUrl">نسخ الرابط</button>
  </div>
  <div class="share-btns">
    <a class="share-btn wa" href="<?= e($waHref) ?>" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15l-1.4 5 5.2-1.4A10 10 0 1 0 12 2zm0 18a8 8 0 0 1-4.1-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8 8 0 1 1 12 20zm4.6-6c-.2-.1-1.5-.7-1.7-.8s-.4-.1-.6.1-.6.8-.8 1-.3.2-.5.1a6.6 6.6 0 0 1-3.3-2.9c-.2-.4.2-.4.6-1.2a.5.5 0 0 0 0-.5l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3A3 3 0 0 0 6.5 9c0 1.8 1.3 3.5 1.5 3.7s2.6 4 6.3 5.4c2.3.8 2.3.5 2.7.5s1.5-.6 1.7-1.2.2-1.1.1-1.2-.2-.2-.5-.3z"/></svg>
      واتساب
    </a>
    <a class="share-btn tg" href="<?= e($tgHref) ?>" target="_blank" rel="noopener">
      <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.9 4.3 18.7 19c-.2 1-.9 1.3-1.8.8l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2L6.4 13 1.6 11.5c-1-.3-1-1 .2-1.5l19-7.3c.9-.3 1.6.2 1.3 1.6z"/></svg>
      تيليجرام
    </a>
    <button type="button" class="share-btn native" data-share-url="<?= e($shareUrl) ?>" data-share-text="<?= e($shareText) ?>" data-share-title="<?= e($task['title']) ?>" hidden>
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
      مشاركة
    </button>
  </div>
</div>
<?php endif; ?>

<!-- تعقيب المُكلَّف -->
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
  <?php elseif ($canAct): ?>
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
  <?php else: ?>
    <?php if ($answered): ?>
      <div class="reply-box answered <?= $task['status'] === 'لم تتم' ? 'neg' : '' ?>">
        <span class="badge s-<?= e(status_slug($task['status'])) ?>"><?= e($task['status']) ?></span>
        <div class="txt"><?= $task['reply'] ? nl2br(e($task['reply'])) : '<span class="muted">— بدون ملاحظات —</span>' ?></div>
        <div class="reply-meta">⏱ <?= e($task['replied_by'] ?: 'المُكلَّف') ?> • <?= e(fmt_dt($task['replied_at'])) ?></div>
      </div>
    <?php else: ?>
      <div class="reply-box"><span class="muted">لم يُعقّب على هذه المهمة بعد.</span></div>
    <?php endif; ?>
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
  <?php if ($canAct): ?>
  <form method="post" action="<?= e(url('comment')) ?>" class="comment-form">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $task['id'] ?>">
    <textarea name="body" placeholder="أضف ملاحظة أو رداً..." required></textarea>
    <button type="submit" class="btn-soft">إرسال</button>
  </form>
  <?php endif; ?>
</div>
