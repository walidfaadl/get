<?php
/** @var string $content @var string $__title */
$u = current_user();
$active = (string) ($_GET['r'] ?? 'tasks');
$f = flash();
$navTasks = 0;
$navAppts = 0;
if ($u) {
    try {
        $navTasks = task_counts(is_manager() ? null : scope_for_user($u))['total'];
        if (can_manage_appointments()) {
            $now = time();
            $navAppts = count(array_filter(appointments_for($u), fn($a) => strtotime($a['starts_at']) >= $now));
        }
    } catch (Throwable $e) { /* ignore count errors */ }
}
?><!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<script>(function(){try{var t=localStorage.getItem('tt-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
<title><?= e($__title) ?> — <?= e(APP_NAME) ?></title>
<!-- PWA -->
<link rel="manifest" href="/manifest.webmanifest">
<meta name="theme-color" content="#FAF9F5">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="المهام">
<link rel="apple-touch-icon" href="/assets/icons/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="/assets/icons/icon-192.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/style.css?v=<?= e(ASSET_VER) ?>">
</head>
<body>
<?php if ($u): ?>
<div class="shell">
  <aside class="sidebar" id="sidebar">
    <div class="sb-head">
      <div class="sb-logo"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg></div>
      <div class="sb-title"><h2><?= e(APP_NAME) ?></h2><p><?= e(APP_ORG) ?></p></div>
    </div>
    <nav class="sb-nav">
      <a href="<?= e(url('tasks')) ?>" class="nv-tasks <?= in_array($active, ['tasks', 'task', 'new', 'edit'], true) ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 17 2 2 4-4"/><path d="m3 7 2 2 4-4"/><path d="M13 6h8"/><path d="M13 12h8"/><path d="M13 18h8"/></svg>
        المهام <span class="nav-count"><?= (int) $navTasks ?></span></a>
      <?php if (can_manage_appointments()): ?>
      <a href="<?= e(url('appointments')) ?>" class="nv-appt <?= in_array($active, ['appointments', 'appt_new', 'appt_edit', 'appt'], true) ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        المواعيد <?php if ($navAppts): ?><span class="nav-count"><?= (int) $navAppts ?></span><?php endif; ?></a>
      <?php endif; ?>
      <?php if (is_manager()): ?>
      <a href="<?= e(url('stats')) ?>" class="<?= $active === 'stats' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><rect x="7" y="10" width="4" height="7" rx="1"/><rect x="15" y="5" width="4" height="12" rx="1"/></svg>
        الإحصائيات</a>
      <a href="<?= e(url('users')) ?>" class="<?= $active === 'users' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        المستخدمون</a>
      <?php endif; ?>
      <a href="<?= e(url('account')) ?>" class="<?= $active === 'account' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        حسابي</a>
    </nav>
    <div class="sb-foot">
      <div class="who">
        <span class="who-name"><?= e($u['name']) ?></span>
        <span class="who-role"><?= e(ROLES[$u['role']] ?? $u['role']) ?><?= $u['department'] ? ' • ' . e($u['department']) : '' ?></span>
      </div>
      <a class="logout" href="<?= e(url('logout')) ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg>
        خروج</a>
    </div>
  </aside>

  <main class="main">
    <header class="topbar">
      <h1><?= e($__title) ?></h1>
      <div class="topbar-tools">
        <button class="theme-btn" id="themeBtn" type="button" aria-label="تبديل الوضع الداكن">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
        </button>
        <?php $__unread = notifs_unread((int) $u['id']); ?>
        <a class="topbar-bell" href="<?= e(url('notifications')) ?>" aria-label="التنبيهات">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
          <?php if ($__unread > 0): ?><span class="bell-badge"><?= $__unread > 99 ? '99+' : (int) $__unread ?></span><?php endif; ?>
        </a>
      </div>
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
<script src="assets/app.js?v=<?= e(ASSET_VER) ?>"></script>
<?php endif; ?>
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
  }
</script>
</body>
</html>
