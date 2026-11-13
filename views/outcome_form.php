<?php /** @var ?array $o @var array $users @var array $selected */
$o = $o ?? [];
$selected = $selected ?? [];
$val = fn(string $k, $d = '') => e((string) ($o[$k] ?? $d));
$metLocal = !empty($o['met_at']) && ($ts = strtotime($o['met_at'])) ? date('Y-m-d\TH:i', $ts) : '';
$foLocal  = !empty($o['followup_at']) && ($ts2 = strtotime($o['followup_at'])) ? date('Y-m-d\TH:i', $ts2) : '';
$agreed   = !empty($o['followup_agreed']);
$bodyHtml = (string) ($o['body'] ?? ''); // HTML منقّى مسبقاً — آمن للعرض داخل المحرّر
$roleLabel = ['manager' => 'المدير', 'head' => 'مدير القسم', 'member' => 'عضو قسم'];
?>
<div class="card form-card">
  <form method="post" action="<?= e(url('outcome_save')) ?>" class="outcome-form">
    <?= csrf_field() ?>
    <?php if (!empty($o['id'])): ?><input type="hidden" name="id" value="<?= (int) $o['id'] ?>"><?php endif; ?>

    <div class="field">
      <label>عنوان اللقاء *</label>
      <input type="text" name="title" value="<?= $val('title') ?>" placeholder="مثال: اجتماع متابعة مشروع التطوّع" required>
    </div>

    <div class="field">
      <label>موعد اللقاء الذي تم *</label>
      <input type="datetime-local" name="met_at" value="<?= e($metLocal) ?>" required>
    </div>

    <div class="field">
      <label>الحضور <span class="hint">(اختَر من قائمة المستخدمين)</span></label>
      <?php if (!$users): ?>
        <p class="muted">لا يوجد مستخدمون فعّالون.</p>
      <?php else: ?>
        <div class="att-head">
          <button type="button" class="btn-soft sm" data-check-all="#attGrid">تحديد الكل</button>
          <span class="att-count"><span id="attCount">0</span> محدَّد</span>
        </div>
        <div class="att-grid" id="attGrid">
          <?php foreach ($users as $u): $ck = in_array((int) $u['id'], $selected, true); ?>
            <label class="att <?= $ck ? 'on' : '' ?>">
              <input type="checkbox" name="attendees[]" value="<?= (int) $u['id'] ?>" <?= $ck ? 'checked' : '' ?>>
              <span class="att-name"><?= e($u['name']) ?></span>
              <span class="att-role"><?= e($roleLabel[$u['role']] ?? $u['role']) ?><?= $u['department'] ? ' • ' . e($u['department']) : '' ?></span>
            </label>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="field">
      <label>نص المخرجات *</label>
      <div class="rte">
        <div class="rte-toolbar" role="toolbar" aria-label="تنسيق النص">
          <button type="button" class="rte-btn" data-cmd="bold" title="عريض"><b>ب</b></button>
          <button type="button" class="rte-btn" data-cmd="italic" title="مائل"><i>ت</i></button>
          <button type="button" class="rte-btn" data-cmd="underline" title="تسطير"><u>ت</u></button>
          <span class="rte-sep"></span>
          <button type="button" class="rte-btn" data-cmd="insertUnorderedList" title="قائمة نقطية">•≡</button>
          <button type="button" class="rte-btn" data-cmd="insertOrderedList" title="قائمة مرقّمة">1.</button>
          <button type="button" class="rte-btn" data-cmd="formatBlock" data-val="h3" title="عنوان فرعي">ع</button>
          <button type="button" class="rte-btn" data-cmd="formatBlock" data-val="blockquote" title="اقتباس">❝</button>
          <span class="rte-sep"></span>
          <button type="button" class="rte-btn" data-cmd="createLink" title="رابط">🔗</button>
          <button type="button" class="rte-btn" data-cmd="removeFormat" title="إزالة التنسيق">⌫</button>
        </div>
        <div class="rte-area" id="rteArea" contenteditable="true" data-target="#rteBody" aria-label="نص المخرجات"><?= $bodyHtml ?></div>
      </div>
      <textarea id="rteBody" name="body" class="rte-hidden" aria-hidden="true"><?= e($bodyHtml) ?></textarea>
    </div>

    <div class="field">
      <label>هل اتُّفق على موعد متابعة؟</label>
      <div class="status-choices" id="followChoices">
        <label class="<?= !$agreed ? 'sel' : '' ?>" data-fo="no">
          <input type="radio" name="followup" value="no" <?= !$agreed ? 'checked' : '' ?>> لا
        </label>
        <label class="<?= $agreed ? 'sel' : '' ?>" data-fo="yes">
          <input type="radio" name="followup" value="yes" <?= $agreed ? 'checked' : '' ?>> نعم
        </label>
      </div>
      <div class="field followup-field" id="followupField" style="<?= $agreed ? '' : 'display:none' ?>">
        <label>موعد المتابعة <span class="hint">(اختياري — اتركه فارغاً إن لم يُحدَّد بعد)</span></label>
        <input type="datetime-local" name="followup_at" value="<?= e($foLocal) ?>">
      </div>
    </div>

    <div class="form-foot">
      <a class="btn-soft" href="<?= e(!empty($o['id']) ? url('outcome', ['id' => $o['id']]) : url('outcomes')) ?>">إلغاء</a>
      <button type="submit" class="btn-primary"><?= !empty($o['id']) ? 'حفظ التعديلات' : 'حفظ المخرجات' ?></button>
    </div>
  </form>
</div>
