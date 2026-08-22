# النشر إلى استضافة tasktrak.co

نظام المهام تطبيق **PHP 8.2 + MariaDB**، يُخدَم من `~/public_html` عبر Apache على
حساب cPanel. البيانات في قاعدة بيانات مشتركة، فيعمل بين المدير ومدراء الأقسام على
أجهزة مختلفة عبر الإنترنت.

## لماذا لا يُنشَر تلقائياً من جلسة Claude السحابية؟

جلسة Claude السحابية منفذها الخارجي الوحيد وكيل HTTPS على المنفذ 443 يُعيد إنهاء TLS،
و SSH (منفذ 22) اتصال TCP خام على منفذ غير 443 **غير مدعوم عبر هذا الوكيل**. لذلك
يُنفَّذ النشر من جهازك الذي يملك مفتاح SSH.

## النشر التلقائي (موصى به)

1. اضبط مفتاح SSH كما في `CLIENTtasktrak.md` (القسم ١): ضع `tasktrak_client` في
   `~/.ssh/`، وأضف كتلة `Host tasktrak` إلى `~/.ssh/config`.

2. من جذر المشروع:

   ```bash
   git clone https://github.com/walidfaadl/get.git
   cd get
   git checkout claude/lightweight-task-system-y3uc36
   bash deploy/deploy-tasktrak.sh
   ```

السكربت يرفع الملفات، ينشئ قاعدة البيانات والمستخدم عبر `uapi` تلقائياً، يكتب
`app/config.local.php` بكلمة مرور عشوائية، ينشئ الجداول، ويطلب منك بيانات حساب
المدير الأول، ثم يحذف `install.php`.

بعدها: `https://tasktrak.co/`

## النشر اليدوي (بديل)

على الخادم عبر SSH:

```bash
# 1) أنشئ قاعدة البيانات والمستخدم
uapi Mysql create_database name=tasktrak_app
uapi Mysql create_user name=tasktrak_app password='كلمة-مرور-قوية'
uapi Mysql set_privileges_on_database user=tasktrak_app database=tasktrak_app privileges='ALL PRIVILEGES'
```

على جهازك: ارفع ملفات المشروع (index.php, install.php, schema.sql, app/, views/,
assets/, bin/) إلى `~/public_html` عبر rsync/scp. ثم على الخادم:

```bash
cd ~/public_html
cp app/config.sample.php app/config.local.php
nano app/config.local.php      # املأ db_name/db_user/db_pass
chmod 600 app/config.local.php
```

ثم إمّا زُر `https://tasktrak.co/install.php` (يُنشئ الجداول وحساب المدير عبر متصفح)،
أو من الطرفية:

```bash
php bin/seed_user.php manager "اسم المدير" mudir "كلمة المرور"
rm install.php                 # للأمان بعد التثبيت
```

## ملاحظات أمنية وتشغيلية

- `app/config.local.php` (بيانات القاعدة) **غير مُتتبَّع في Git**، ومحميّ بـ`.htaccess`
  في مجلد `app/`، وحتى لو طُلب مباشرةً فهو PHP يُرجِع مصفوفة بلا إخراج — لا تُسرَّب بياناته.
- مجلدات `app/` و`views/` و`bin/` محميّة من الوصول المباشر عبر `.htaccess` (Require all denied).
- بعد أي تحديث للكود، السكربت يُنفّذ `touch` لإبطال كاش opcache.
- لا يستعمل التطبيق أي مكتبات خارجية (لا Composer) — يتجنّب حدود الذاكرة و Imunify360.
- الجداول تُنشأ بـ`utf8mb4` لدعم العربية بالكامل.

## هيكل المشروع

```
index.php            واجهة التحكم (توجيه المسارات)
install.php          مُثبِّت لمرة واحدة (يُحذف بعد التثبيت)
schema.sql           مخطط قاعدة البيانات
app/                 config, db, auth, helpers, models, bootstrap
views/               قوالب العرض (layout, login, tasks, detail, users, ...)
assets/              style.css, app.js
bin/seed_user.php    أداة إنشاء مستخدم من سطر الأوامر
```
