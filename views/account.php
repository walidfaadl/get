<?php /** @var ?array $row @var bool $twoFaOn @var int $recoveryLeft @var bool $waConfigured */
$u = current_user();
$row = $row ?? [];
$twoFaOn = $twoFaOn ?? false;
$recoveryLeft = $recoveryLeft ?? 0;
$waConfigured = $waConfigured ?? false;
?>
<div class="card">
  <h3 class="card-h">بيانات الحساب</h3>
  <div class="detail-grid">
    <div class="cell"><div class="k">الاسم</div><div class="val"><?= e($u['name']) ?></div></div>
    <div class="cell"><div class="k">اسم المستخدم</div><div class="val">@<?= e($u['username']) ?></div></div>
    <div class="cell"><div class="k">الدور</div><div class="val"><?= e(ROLES[$u['role']] ?? $u['role']) ?></div></div>
    <?php if ($u['department']): ?><div class="cell"><div class="k">القسم</div><div class="val"><?= e($u['department']) ?></div></div><?php endif; ?>
  </div>
</div>

<!-- التحقق بخطوتين -->
<div class="card">
  <div class="sec-head">
    <h3 class="card-h" style="margin:0">التحقق بخطوتين</h3>
    <span class="status-chip <?= $twoFaOn ? 'on' : 'off' ?>"><?= $twoFaOn ? 'مُفعّل' : 'غير مُفعّل' ?></span>
  </div>
  <?php if (!$twoFaOn): ?>
    <p class="muted" style="margin:6px 0 14px">أضِف طبقة حماية ثانية عبر تطبيق مصادقة (Google Authenticator وغيره). سيُطلب رمز مؤقّت عند كل دخول.</p>
    <a class="btn-primary" href="<?= e(url('tfa_setup')) ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      تفعيل التحقق بخطوتين
    </a>
  <?php else: ?>
    <p class="muted" style="margin:6px 0 14px">حسابك محميّ بالتحقق بخطوتين. رموز الاسترداد المتبقّية: <b><?= (int) $recoveryLeft ?></b>.</p>
    <div class="acct-actions">
      <form method="post" action="<?= e(url('tfa_recovery')) ?>" class="inline-pw">
        <?= csrf_field() ?>
        <input type="password" name="password" placeholder="كلمة المرور الحالية" autocomplete="current-password" required>
        <button type="submit" class="btn-soft">توليد رموز استرداد جديدة</button>
      </form>
      <form method="post" action="<?= e(url('tfa_disable')) ?>" class="inline-pw" onsubmit="return confirm('تعطيل التحقق بخطوتين؟')">
        <?= csrf_field() ?>
        <input type="password" name="password" placeholder="كلمة المرور الحالية" autocomplete="current-password" required>
        <button type="submit" class="btn-danger">تعطيل</button>
      </form>
    </div>
  <?php endif; ?>
</div>

<!-- تذكير واتساب -->
<div class="card form-card">
  <div class="sec-head">
    <h3 class="card-h" style="margin:0">تذكير المواعيد عبر واتساب</h3>
    <span class="status-chip <?= !empty($row['wa_enabled']) ? 'on' : 'off' ?>"><?= !empty($row['wa_enabled']) ? 'مُفعّل' : 'متوقّف' ?></span>
  </div>
  <p class="muted" style="margin:6px 0 14px">اربط رقم واتساب لتصلك تذكيرات بمواعيدك <b>صباح يوم الموعد</b>.</p>
  <?php if (!$waConfigured): ?>
    <div class="flash flash-err" style="margin-bottom:14px">تكامل واتساب لم يُهيّأ على الخادم بعد (توكن المزوّد). يمكنك حفظ رقمك الآن، وسيعمل الإرسال فور تهيئة الخادم.</div>
  <?php endif; ?>
  <form method="post" action="<?= e(url('wa_save')) ?>">
    <?= csrf_field() ?>
    <div class="field">
      <label>رقم واتساب <span class="hint">(بالصيغة الدولية بلا +، مثال: 9665XXXXXXXX)</span></label>
      <input type="tel" name="wa_phone" value="<?= e((string) ($row['wa_phone'] ?? '')) ?>" inputmode="numeric" placeholder="9665XXXXXXXX">
    </div>
    <label class="check"><input type="checkbox" name="wa_enabled" <?= !empty($row['wa_enabled']) ? 'checked' : '' ?>> تفعيل تذكير المواعيد عبر واتساب</label>
    <div class="form-foot" style="justify-content:flex-start;gap:8px">
      <button type="submit" class="btn-primary">حفظ</button>
    </div>
  </form>
  <?php if (!empty($row['wa_phone'])): ?>
    <form method="post" action="<?= e(url('wa_test')) ?>" style="margin-top:4px">
      <?= csrf_field() ?>
      <button type="submit" class="btn-soft sm" <?= $waConfigured ? '' : 'disabled title="يلزم تهيئة الخادم"' ?>>إرسال رسالة تجريبية</button>
    </form>
  <?php endif; ?>
</div>

<!-- تذكير تيليجرام -->
<?php $tgConfigured = $tgConfigured ?? false; $tgLinked = $tgLinked ?? false; $tgLinkUrl = $tgLinkUrl ?? ''; ?>
<div class="card">
  <div class="sec-head">
    <h3 class="card-h" style="margin:0">تذكير المواعيد عبر تيليجرام</h3>
    <span class="status-chip <?= ($tgLinked && !empty($row['tg_enabled'])) ? 'on' : 'off' ?>"><?= $tgLinked ? (!empty($row['tg_enabled']) ? 'مُفعّل' : 'مربوط (متوقّف)') : 'غير مربوط' ?></span>
  </div>
  <p class="muted" style="margin:6px 0 14px">تيليجرام مجاني ويصل كإشعار على هاتفك. اربط حسابك مرّة واحدة لتصلك تذكيرات المواعيد <b>صباح يوم الموعد</b>.</p>
  <?php if (!$tgConfigured): ?>
    <div class="flash flash-err" style="margin-bottom:0">تكامل تيليجرام لم يُهيّأ على الخادم بعد (توكن البوت).</div>
  <?php elseif (!$tgLinked): ?>
    <?php if ($tgLinkUrl !== ''): ?>
      <a class="btn-primary" href="<?= e($tgLinkUrl) ?>" target="_blank" rel="noopener">
        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M21.9 4.3 18.7 19c-.2 1-.9 1.3-1.8.8l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.3-4.9 8.9-8c.4-.3-.1-.5-.6-.2L6.4 13 1.6 11.5c-1-.3-1-1 .2-1.5l19-7.3c.9-.3 1.6.2 1.3 1.6z"/></svg>
        ربط تيليجرام
      </a>
      <p class="hint" style="margin-top:10px">يفتح البوت في تيليجرام — اضغط «Start» ليُربط حسابك تلقائياً، ثم عُد لهذه الصفحة.</p>
    <?php else: ?>
      <div class="flash flash-err" style="margin-bottom:0">لم يُضبط اسم البوت على الخادم (TG_BOT_USERNAME).</div>
    <?php endif; ?>
  <?php else: ?>
    <div class="acct-actions">
      <form method="post" action="<?= e(url('tg_save')) ?>">
        <?= csrf_field() ?>
        <label class="check"><input type="checkbox" name="tg_enabled" <?= !empty($row['tg_enabled']) ? 'checked' : '' ?>> تفعيل تذكير المواعيد عبر تيليجرام</label>
        <div class="form-foot" style="justify-content:flex-start;gap:8px;margin-top:4px">
          <button type="submit" class="btn-primary">حفظ</button>
          <button type="submit" formaction="<?= e(url('tg_test')) ?>" class="btn-soft">إرسال رسالة تجريبية</button>
        </div>
      </form>
      <form method="post" action="<?= e(url('tg_unlink')) ?>" onsubmit="return confirm('فكّ ربط تيليجرام؟')">
        <?= csrf_field() ?>
        <button type="submit" class="btn-danger sm">فكّ الربط</button>
      </form>
    </div>
  <?php endif; ?>
  <?php if (is_manager() && $tgConfigured): ?>
    <form method="post" action="<?= e(url('tg_setwebhook')) ?>" style="margin-top:14px;border-top:.5px dashed var(--line);padding-top:12px">
      <?= csrf_field() ?>
      <button type="submit" class="btn-soft sm">ضبط webhook تيليجرام (مرّة واحدة)</button>
      <span class="hint" style="margin-inline-start:8px">للمدير: يُفعّل استقبال ربط الحسابات.</span>
    </form>
  <?php endif; ?>
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
