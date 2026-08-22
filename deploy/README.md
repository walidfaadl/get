# النشر إلى استضافة tasktrak.co

المشروع بالكامل **ثابت** (HTML + CSS + JavaScript، مع تخزين محلي عبر IndexedDB)،
فلا يحتاج PHP ولا قاعدة بيانات على الخادم. النشر = رفع خمسة ملفات إلى `~/public_html`.

## لماذا لا يُنشَر تلقائياً من جلسة Claude السحابية؟

جلسة Claude Code هذه تعمل في بيئة سحابية معزولة، ومنفذها الخارجي الوحيد هو
وكيل HTTPS على المنفذ 443 يُعيد إنهاء TLS. بروتوكول SSH (منفذ 22) هو اتصال TCP
خام على منفذ غير 443، وهذا **غير مدعوم عبر الوكيل** (يقبل نفق CONNECT شكلياً ثم
يُسقط البيانات). لذلك يتعذّر رفع الملفات عبر SSH/SCP من داخل الجلسة، ويجب تنفيذ
النشر من جهازك الذي يملك المفتاح.

## الخطوات (من جهازك)

1. اضبط مفتاح SSH كما في دليل `CLIENTtasktrak.md` (القسم ١):

   ```bash
   mkdir -p ~/.ssh && mv tasktrak_client ~/.ssh/ && chmod 600 ~/.ssh/tasktrak_client
   ```

   وأضف إلى `~/.ssh/config`:

   ```
   Host tasktrak
     HostName srv1.taktek.co
     User tasktrak
     IdentityFile ~/.ssh/tasktrak_client
     IdentitiesOnly yes
     ServerAliveInterval 30
   ```

2. اجلب المشروع وشغّل سكربت النشر من جذره:

   ```bash
   git clone https://github.com/walidfaadl/get.git
   cd get
   git checkout claude/lightweight-task-system-y3uc36
   bash deploy/deploy-tasktrak.sh
   ```

بعدها:

- الموقع: `https://tasktrak.co/`
- نظام المهام: `https://tasktrak.co/tasks.html`
- لوحة التحكم: `https://tasktrak.co/admin.html`

## السكربت آمن

- يرفع فقط: `index.html`, `admin.html`, `news.html`, `tasks.html`, `db.js`
- **لا يحذف** أي شيء، ولا يلمس ملفات cPanel المولَّدة (`.htaccess`, `php.ini`, `.user.ini`)
- يأخذ نسخة احتياطية `.bak-<تاريخ>` لأي ملف يستبدله (عند توفّر rsync)
