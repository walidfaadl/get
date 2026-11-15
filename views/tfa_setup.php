<?php /** @var string $secret @var string $uri */ ?>
<div class="detail-head">
  <a class="back" href="<?= e(url('account')) ?>">→ رجوع للحساب</a>
</div>

<div class="card">
  <h3 class="card-h">تفعيل التحقق بخطوتين</h3>
  <ol class="tfa-steps">
    <li>ثبّت تطبيق مصادقة على هاتفك (Google Authenticator، Microsoft Authenticator، Authy...).</li>
    <li>امسح رمز QR التالي، أو أدخِل المفتاح يدوياً.</li>
    <li>اكتب الرمز المكوّن من 6 أرقام الظاهر في التطبيق للتأكيد.</li>
  </ol>

  <div class="tfa-enroll">
    <div class="tfa-qr" id="tfaQr" data-uri="<?= e($uri) ?>">
      <div class="tfa-qr-fallback">يُعرض رمز QR هنا…</div>
    </div>
    <div class="tfa-manual">
      <div class="k">المفتاح اليدوي</div>
      <code class="tfa-key" id="tfaKey"><?= e(totp_format_secret($secret)) ?></code>
      <button type="button" class="btn-soft sm" data-copy="#tfaKey" data-copy-text="<?= e($secret) ?>">نسخ المفتاح</button>
    </div>
  </div>

  <form method="post" action="<?= e(url('tfa_enable')) ?>" class="tfa-confirm">
    <?= csrf_field() ?>
    <div class="field">
      <label>رمز التأكيد (6 أرقام)</label>
      <input type="text" name="code" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]*" placeholder="123456" required>
    </div>
    <div class="form-foot">
      <a class="btn-soft" href="<?= e(url('account')) ?>">إلغاء</a>
      <button type="submit" class="btn-primary">تأكيد وتفعيل</button>
    </div>
  </form>
</div>

<script src="assets/vendor/qrcode.js?v=<?= e(ASSET_VER) ?>"></script>
<script>
(function () {
  var box = document.getElementById('tfaQr');
  if (!box || typeof qrcode === 'undefined') return;
  try {
    var qr = qrcode(0, 'M');
    qr.addData(box.getAttribute('data-uri'));
    qr.make();
    box.innerHTML = qr.createSvgTag({ cellSize: 5, margin: 2, scalable: true });
  } catch (e) {}
})();
</script>
