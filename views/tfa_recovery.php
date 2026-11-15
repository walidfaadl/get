<?php /** @var array $codes @var string $heading */ ?>
<div class="detail-head">
  <a class="back" href="<?= e(url('account')) ?>">→ رجوع للحساب</a>
</div>

<div class="card">
  <h3 class="card-h"><?= e($heading ?? 'رموز الاسترداد') ?></h3>
  <div class="rec-warn">
    احفظ هذه الرموز في مكان آمن. كل رمز يُستخدَم <b>مرة واحدة</b> للدخول إذا فقدت تطبيق المصادقة.
    لن تظهر هذه الرموز مرة أخرى.
  </div>
  <div class="rec-grid" id="recGrid">
    <?php foreach ($codes as $c): ?>
      <code class="rec-code"><?= e($c) ?></code>
    <?php endforeach; ?>
  </div>
  <div class="form-foot" style="justify-content:flex-start;gap:8px">
    <button type="button" class="btn-soft" data-copy="#recGrid" data-copy-text="<?= e(implode("\n", $codes)) ?>">نسخ كل الرموز</button>
    <a class="btn-primary" href="<?= e(url('account')) ?>">تم، حفظتها</a>
  </div>
</div>
