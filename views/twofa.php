<?php /** @var ?string $error */ ?>
<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></div>
    <h1>التحقق بخطوتين</h1>
    <p class="sub">أدخِل الرمز من تطبيق المصادقة</p>
    <?php if ($error): ?><div class="login-err"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('2fa')) ?>">
      <?= csrf_field() ?>
      <label>رمز التحقق (6 أرقام)</label>
      <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" placeholder="123456" autofocus required>
      <button type="submit">تأكيد</button>
    </form>
    <p class="login-alt">تعذّر الوصول لتطبيق المصادقة؟ أدخِل أحد <b>رموز الاسترداد</b> في الحقل أعلاه.</p>
    <p class="login-alt"><a href="<?= e(url('logout')) ?>">الرجوع لتسجيل الدخول</a></p>
  </div>
</div>
