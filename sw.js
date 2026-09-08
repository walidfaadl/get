/* ==========================================================
   Service Worker — نظام المهام (PWA)
   استراتيجية آمنة لتطبيق ديناميكي بمصادقة:
   - الأصول الثابتة (CSS/JS/أيقونات): cache-first مع تحديث بالخلفية.
   - طلبات التصفح (صفحات HTML): network-only، وعند انقطاع الشبكة
     تُعرض صفحة "غير متصل". لا تُخزَّن صفحات المستخدمين المحمية إطلاقاً
     تفادياً لتسريب البيانات بين المستخدمين على جهاز مشترك.
   ========================================================== */
const VERSION = 'v5';
const STATIC_CACHE = 'tasktrak-static-' + VERSION;

// أصول ثابتة تُخزَّن مسبقاً (متاحة بلا اتصال)
const PRECACHE = [
  '/assets/style.css',
  '/assets/app.js',
  '/assets/icons/icon-192.png',
  '/assets/icons/icon-512.png',
  '/assets/icons/apple-touch-icon.png',
  '/manifest.webmanifest',
  '/offline.html'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== STATIC_CACHE).map((k) => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

// هل الطلب لأصل ثابت نخزّنه؟
function isStaticAsset(url) {
  return url.pathname.startsWith('/assets/') || url.pathname === '/manifest.webmanifest';
}

self.addEventListener('fetch', (event) => {
  const req = event.request;

  // لا نتدخّل إلا في GET على نفس الأصل
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;

  // طلبات التصفح (فتح صفحة) → شبكة أولاً، وعند الفشل صفحة عدم الاتصال
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() => caches.match('/offline.html'))
    );
    return;
  }

  // الأصول الثابتة → من الكاش أولاً مع تحديث بالخلفية (stale-while-revalidate)
  if (isStaticAsset(url)) {
    event.respondWith(
      caches.match(req).then((cached) => {
        const network = fetch(req).then((res) => {
          if (res && res.status === 200) {
            const copy = res.clone();
            caches.open(STATIC_CACHE).then((cache) => cache.put(req, copy));
          }
          return res;
        }).catch(() => cached);
        return cached || network;
      })
    );
  }
  // غير ذلك: نتركه للشبكة كالمعتاد (لا تخزين).
});
