<?php
/** @var string $content @var string $__title */
$u = current_user();
$active = (string) ($_GET['r'] ?? 'tasks');
$f = flash();
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<title><?= e($__title) ?> — <?= e(APP_NAME) ?></title>
<!-- PWA -->
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#8b1e3f">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="المهام">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="/assets/icons/icon-192.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Readex+Pro:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= e(ASSET_VER) ?>">
</head>
<body>
<?php if ($u): ?>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <div class="sb-head">
      <div class="sb-logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></div>
      <div class="sb-title"><h2><?= e(APP_NAME) ?></h2><p><?= e(APP_ORG) ?></p></div>
    </div>
    <nav class="sb-nav">
      <a href="<?= e(url('tasks')) ?>" class="nv-tasks <?= $active === 'tasks' || $active === 'task' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>المهام</a>
      <?php if (can_manage_appointments()): ?>
      <a href="<?= e(url('appointments')) ?>" class="nv-appt <?= in_array($active, ['appointments', 'appt_new', 'appt_edit', 'appt'], true) ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>المواعيد</a>
      <?php endif; ?>
      <?php if (is_manager()): ?>
      <a href="<?= e(url('new')) ?>" class="<?= in_array($active, ['new', 'edit'], true) ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>مهمة جديدة</a>
      <a href="<?= e(url('stats')) ?>" class="<?= $active === 'stats' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>الإحصائيات</a>
      <a href="<?= e(url('users')) ?>" class="<?= $active === 'users' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>المستخدمون</a>
      <?php endif; ?>
      <a href="<?= e(url('account')) ?>" class="<?= $active === 'account' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>حسابي</a>
    </nav>
    <div class="sb-foot">
      <div class="who">
        <div class="who-name"><?= e($u['name']) ?></div>
        <div class="who-role"><?= e(ROLES[$u['role']] ?? $u['role']) ?><?= $u['department'] ? ' • ' . e($u['department']) : '' ?></div>
      </div>
      <a class="logout" href="<?= e(url('logout')) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>خروج</a>
    </div>
  </aside>

  <main class="main">
    <header class="topbar">
      <button class="burger" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="القائمة">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <h1><?= e($__title) ?></h1>
      <a class="topbar-logout" href="<?= e(url('logout')) ?>" title="تسجيل الخروج">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span>خروج</span>
      </a>
    </header>
    <div class="content">
      <?php if ($f): ?><div class="flash <?= $f['type'] === 'err' ? 'flash-err' : 'flash-ok' ?>"><?= e($f['msg']) ?></div><?php endif; ?>
      <?= $content ?>
    </div>
  </main>
</div>
<script src="assets/app.js?v=<?= e(ASSET_VER) ?>"></script>
<?php else: ?>
<?= $content ?>
<?php endif; ?>
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js').catch(function () {});
    });
  }
</script>
</body>
</html>
