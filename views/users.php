<?php /** @var array $users */ ?>
<div class="two-col">
  <div class="card">
    <h3 class="card-h">المستخدمون</h3>
    <div class="user-list">
      <?php foreach ($users as $usr): ?>
        <div class="user-row">
          <div class="user-info">
            <div class="user-name"><?= e($usr['name']) ?> <?php if (!$usr['active']): ?><span class="tag off">موقوف</span><?php endif; ?></div>
            <div class="user-sub">
              <span class="tag role-<?= e($usr['role']) ?>"><?= e(ROLES[$usr['role']] ?? $usr['role']) ?></span>
              @<?= e($usr['username']) ?><?= $usr['department'] ? ' • ' . e($usr['department']) : '' ?>
            </div>
          </div>
          <div class="user-actions">
            <button class="btn-soft sm" type="button"
              onclick='fillUserForm(<?= json_encode([
                'id' => (int) $usr['id'], 'name' => $usr['name'], 'username' => $usr['username'],
                'email' => $usr['email'] ?? '', 'role' => $usr['role'],
                'department' => $usr['department'], 'active' => (int) $usr['active'],
              ], JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>تعديل</button>
            <form method="post" action="<?= e(url('user_delete')) ?>" onsubmit="return confirm('حذف هذا المستخدم؟')">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $usr['id'] ?>">
              <button class="btn-danger sm" type="submit">حذف</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card form-card">
    <h3 class="card-h" id="userFormTitle">إضافة مستخدم</h3>
    <form method="post" action="<?= e(url('user_save')) ?>" id="userForm">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="u_id" value="">
      <div class="field"><label>الاسم *</label><input type="text" name="name" id="u_name" required></div>
      <div class="field"><label>اسم المستخدم *</label><input type="text" name="username" id="u_username" autocomplete="off" required></div>
      <div class="field"><label>البريد الإلكتروني <span class="hint">(للإشعارات — اختياري)</span></label><input type="email" name="email" id="u_email" autocomplete="off" placeholder="name@example.com"></div>
      <div class="field-row">
        <div class="field">
          <label>الدور</label>
          <select name="role" id="u_role">
            <option value="head">مدير قسم</option>
            <option value="manager">مدير</option>
          </select>
        </div>
        <div class="field"><label>القسم</label><input type="text" name="department" id="u_department" placeholder="اختياري"></div>
      </div>
      <div class="field">
        <label>كلمة المرور <span class="hint" id="u_pwhint">(مطلوبة للحساب الجديد)</span></label>
        <input type="password" name="password" id="u_password" autocomplete="new-password">
      </div>
      <label class="check"><input type="checkbox" name="active" id="u_active" checked> الحساب مُفعَّل</label>
      <div class="form-foot">
        <button type="button" class="btn-soft" onclick="resetUserForm()">جديد</button>
        <button type="submit" class="btn-primary">حفظ</button>
      </div>
    </form>
  </div>
</div>
