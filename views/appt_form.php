<?php /** @var ?array $appt @var array $heads */
$a = $appt ?? [];
$val = fn(string $k, $d = '') => e((string) ($a[$k] ?? $d));
// datetime-local يحتاج صيغة YYYY-MM-DDTHH:MM
$dtLocal = '';
if (!empty($a['starts_at'])) {
    $ts = strtotime($a['starts_at']);
    $dtLocal = $ts ? date('Y-m-d\TH:i', $ts) : '';
}
?>
<div class="card form-card">
  <form method="post" action="<?= e(url('appt_save')) ?>">
    <?= csrf_field() ?>
    <?php if (!empty($a['id'])): ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>"><?php endif; ?>

    <div class="field">
      <label>موضوع الموعد *</label>
      <input type="text" name="subject" value="<?= $val('subject') ?>" placeholder="مثال: اجتماع لجنة البرامج" required>
    </div>

    <div class="field-row">
      <div class="field">
        <label>التاريخ والوقت *</label>
        <input type="datetime-local" name="starts_at" value="<?= e($dtLocal) ?>" required>
      </div>
      <div class="field">
        <label>مع مَن</label>
        <input type="text" name="with_whom" value="<?= $val('with_whom') ?>" placeholder="اسم الشخص أو الجهة">
      </div>
    </div>

    <div class="field-row">
      <div class="field">
        <label>المكان</label>
        <input type="text" name="location" value="<?= $val('location') ?>" placeholder="اختياري">
      </div>
      <div class="field">
        <label>مشاركة رئيس قسم <span class="hint">(اختياري)</span></label>
        <select name="shared_with">
          <option value="">— بدون مشاركة —</option>
          <?php foreach ($heads as $h): ?>
            <option value="<?= (int) $h['id'] ?>" <?= (int) ($a['shared_with'] ?? 0) === (int) $h['id'] ? 'selected' : '' ?>>
              <?= e($h['name']) ?><?= $h['department'] ? ' (' . e($h['department']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="field">
      <label>ملاحظات</label>
      <textarea name="notes" rows="4" placeholder="تفاصيل إضافية (اختياري)"><?= $val('notes') ?></textarea>
    </div>

    <div class="form-foot">
      <a class="btn-soft" href="<?= e(url('appointments')) ?>">إلغاء</a>
      <button type="submit" class="btn-gold"><?= !empty($a['id']) ? 'حفظ التعديلات' : 'إضافة الموعد' ?></button>
    </div>
  </form>
</div>
