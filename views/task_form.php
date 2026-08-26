<?php /** @var ?array $task @var array $assignable */
$t = $task ?? [];
$val = fn(string $k, $d = '') => e((string) ($t[$k] ?? $d));
$assignable = $assignable ?? [];
$heads   = array_filter($assignable, fn($u) => $u['role'] === 'head');
$members = array_filter($assignable, fn($u) => $u['role'] === 'member');
$optRow = function ($u) use ($t) {
    $sel = (int) ($t['assigned_to'] ?? 0) === (int) $u['id'] ? 'selected' : '';
    $dep = $u['department'] ? ' (' . e($u['department']) . ')' : '';
    return '<option value="' . (int) $u['id'] . '" ' . $sel . '>' . e($u['name']) . $dep . '</option>';
};
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
        <label>التكليف إلى</label>
        <select name="assigned_to">
          <option value="">— بدون إسناد محدّد —</option>
          <?php if ($heads): ?>
            <optgroup label="رؤساء الأقسام">
              <?php foreach ($heads as $h) { echo $optRow($h); } ?>
            </optgroup>
          <?php endif; ?>
          <?php if ($members): ?>
            <optgroup label="أعضاء الأقسام">
              <?php foreach ($members as $mbr) { echo $optRow($mbr); } ?>
            </optgroup>
          <?php endif; ?>
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
