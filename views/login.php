<?php /** @var ?string $error */ ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div>
    <h1><?= e(APP_NAME) ?></h1>
    <p class="sub">سجّل الدخول للمتابعة</p>
    <?php if ($error): ?><div class="login-err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('login')) ?>">
      <?= csrf_field() ?>
      <label>اسم المستخدم</label>
      <input type="text" name="username" autocomplete="username" autofocus required>
      <label>كلمة المرور</label>
      <input type="password" name="password" autocomplete="current-password" required>
      <button type="submit">دخول</button>
    </form>
  </div>
</div>
