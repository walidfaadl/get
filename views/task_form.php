<?php /** @var ?array $task @var array $heads */
$t = $task ?? [];
$val = fn(string $k, $d = '') => e((string) ($t[$k] ?? $d));
?>
<div class="card form-card">
  <form method="post" action="<?= e(url('save')) ?>">
    <?= csrf_field() ?>
    <?php if (!empty($t['id'])): ?><input type="hidden" name="id" value="<?= (int) $t['id'] ?>"><?php endif; ?>

    <div class="field">
      <label>عنوان المهمة *</label>
      <input type="text" name="title" value="<?= $val('title') ?>" placeholder="مثال: إعداد تقرير المستفيدين الشهري" required>
    </div>

    <div class="field">
      <label>تفاصيل المهمة *</label>
      <textarea name="details" rows="6" placeholder="اشرح المطلوب والمخرجات المتوقعة..." required><?= $val('details') ?></textarea>
    </div>

    <div class="field-row">
      <div class="field">
        <label>القسم / الجهة</label>
        <input type="text" name="department" value="<?= $val('department') ?>" placeholder="مثال: قسم البرامج">
      </div>
      <div class="field">
        <label>إسناد إلى مدير قسم</label>
        <select name="assigned_to">
          <option value="">— بدون إسناد محدّد —</option>
          <?php foreach ($heads as $h): ?>
            <option value="<?= (int) $h['id'] ?>" <?= (int) ($t['assigned_to'] ?? 0) === (int) $h['id'] ? 'selected' : '' ?>>
              <?= e($h['name']) ?><?= $h['department'] ? ' (' . e($h['department']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field-row">
      <div class="field">
        <label>الأولوية</label>
        <select name="priority">
          <?php foreach (PRIORITIES as $p): ?>
            <option value="<?= e($p) ?>" <?= ($t['priority'] ?? 'متوسطة') === $p ? 'selected' : '' ?>><?= e($p) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>تاريخ الاستحقاق</label>
        <input type="date" name="due_date" value="<?= $val('due_date') ?>">
      </div>
    </div>

    <div class="field">
      <label>ملاحظة المدير (اختياري)</label>
      <input type="text" name="manager_note" value="<?= $val('manager_note') ?>" placeholder="أي توجيه إضافي لمدير القسم">
    </div>

    <div class="form-foot">
      <a class="btn-soft" href="<?= e(!empty($t['id']) ? url('task', ['id' => $t['id']]) : url('tasks')) ?>">إلغاء</a>
      <button type="submit" class="btn-primary"><?= !empty($t['id']) ? 'حفظ التعديلات' : 'إنشاء المهمة' ?></button>
    </div>
  </form>
</div>
