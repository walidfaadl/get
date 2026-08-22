<?php $u = current_user(); ?>
<div class="card">
  <h3 class="card-h">بيانات الحساب</h3>
  <div class="detail-grid">
    <div class="cell"><div class="k">الاسم</div><div class="val"><?= e($u['name']) ?></div></div>
    <div class="cell"><div class="k">اسم المستخدم</div><div class="val">@<?= e($u['username']) ?></div></div>
    <div class="cell"><div class="k">الدور</div><div class="val"><?= e(ROLES[$u['role']] ?? $u['role']) ?></div></div>
    <?php if ($u['department']): ?><div class="cell"><div class="k">القسم</div><div class="val"><?= e($u['department']) ?></div></div><?php endif; ?>
  </div>
</div>

<div class="card form-card">
  <h3 class="card-h">تغيير كلمة المرور</h3>
  <form method="post" action="<?= e(url('account')) ?>">
    <?= csrf_field() ?>
    <div class="field"><label>كلمة المرور الحالية</label><input type="password" name="current" autocomplete="current-password" required></div>
    <div class="field-row">
      <div class="field"><label>كلمة المرور الجديدة</label><input type="password" name="new" autocomplete="new-password" minlength="6" required></div>
      <div class="field"><label>تأكيد الجديدة</label><input type="password" name="confirm" autocomplete="new-password" minlength="6" required></div>
    </div>
    <div class="form-foot"><button type="submit" class="btn-primary">تحديث كلمة المرور</button></div>
  </form>
</div>
