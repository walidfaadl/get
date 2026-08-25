#!/usr/bin/env bash
# ==========================================================
#  نشر نظام المهام (PHP + MariaDB) إلى استضافة tasktrak.co (cPanel)
#
#  ما يفعله السكربت:
#   1) يرفع ملفات التطبيق إلى ~/public_html عبر rsync (بدون حذف)
#   2) عند أول تشغيل: ينشئ قاعدة البيانات والمستخدم عبر uapi،
#      ويكتب app/config.local.php على الخادم بكلمة مرور عشوائية
#   3) ينشئ الجداول ويضيف حساب المدير الأول (php bin/seed_user.php)
#   4) يُبطِل كاش opcache ويتحقق من استجابة الموقع
#
#  المتطلبات على جهازك:
#   - مفتاح SSH ‏tasktrak_client مضبوط في ~/.ssh/ مع كتلة config
#     باسم المضيف "tasktrak" (انظر CLIENTtasktrak.md، القسم ١)
#   - rsync
#
#  الاستخدام (من جذر المشروع):
#     bash deploy/deploy-tasktrak.sh
# ==========================================================
set -euo pipefail

SSH_HOST="${SSH_HOST:-tasktrak}"
REMOTE_DIR="${REMOTE_DIR:-public_html}"
DB_NAME="${DB_NAME:-tasktrak_app}"     # يجب أن يبدأ بـ tasktrak_ على cPanel
DB_USER="${DB_USER:-tasktrak_app}"
SITE_URL="${SITE_URL:-https://tasktrak.co/}"

# ملفات التطبيق المراد رفعها (لا نرفع .git ولا deploy ولا config.local.php)
PATHS=(index.php install.php schema.sql app views assets bin)

say(){ printf '\n\033[1;35m» %s\033[0m\n' "$*"; }
die(){ printf '\n\033[1;31m✗ %s\033[0m\n' "$*" >&2; exit 1; }

# التأكد من التشغيل من جذر المشروع
[ -f index.php ] && [ -d app ] || die "شغّل السكربت من جذر المشروع (حيث index.php)."

say "فحص الاتصال بـ $SSH_HOST"
ssh -o ConnectTimeout=25 "$SSH_HOST" 'echo "  متصل: $(id -un) — PHP $(php -r "echo PHP_VERSION;" 2>/dev/null || echo -)"' \
  || die "تعذّر الاتصال عبر SSH. راجع إعداد المفتاح في ~/.ssh/config."

say "رفع ملفات التطبيق إلى ~/$REMOTE_DIR"
ssh "$SSH_HOST" "mkdir -p ~/$REMOTE_DIR"
rsync -avz --human-readable \
  --exclude 'config.local.php' \
  --exclude '.git' --exclude '.github' --exclude 'deploy' \
  "${PATHS[@]}" "$SSH_HOST:$REMOTE_DIR/"

# ---------- إعداد قاعدة البيانات (أول مرة فقط) ----------
CFG_PATH="$REMOTE_DIR/app/config.local.php"
if ssh "$SSH_HOST" "test -f ~/$CFG_PATH"; then
  say "app/config.local.php موجود على الخادم — إعادة استخدام إعدادات قاعدة البيانات الحالية."
else
  say "إعداد قاعدة البيانات لأول مرة"
  DB_PASS="$(LC_ALL=C tr -dc 'A-Za-z0-9' < /dev/urandom | head -c 24)"

  echo "  إنشاء القاعدة والمستخدم عبر uapi ..."
  ssh "$SSH_HOST" "uapi Mysql create_database name='$DB_NAME'" >/dev/null 2>&1 || echo "  (القاعدة موجودة already؟ تجاهل)"
  ssh "$SSH_HOST" "uapi Mysql create_user name='$DB_USER' password='$DB_PASS'" >/dev/null 2>&1 \
    || die "تعذّر إنشاء مستخدم قاعدة البيانات (قد يكون موجوداً بكلمة مرور مختلفة). أنشئ config.local.php يدوياً — انظر deploy/README.md."
  ssh "$SSH_HOST" "uapi Mysql set_privileges_on_database user='$DB_USER' database='$DB_NAME' privileges='ALL PRIVILEGES'" >/dev/null 2>&1 \
    || die "تعذّر منح الصلاحيات."

  echo "  كتابة app/config.local.php على الخادم ..."
  ssh "$SSH_HOST" "cat > ~/$CFG_PATH" <<PHP
<?php
return [
  'db_host' => 'localhost',
  'db_port' => 3306,
  'db_name' => '$DB_NAME',
  'db_user' => '$DB_USER',
  'db_pass' => '$DB_PASS',
  'app_name' => 'نظام المهام',
  'app_org'  => 'TaskTrak',
  'debug'    => false,
];
PHP
  ssh "$SSH_HOST" "chmod 600 ~/$CFG_PATH"
  echo "  ✓ تم حفظ الإعدادات (كلمة مرور القاعدة عشوائية ومخزّنة على الخادم فقط)."
fi

# ---------- إنشاء الجداول وحساب المدير ----------
say "التأكد من الجداول وحساب المدير"
HAS_MGR="$(ssh "$SSH_HOST" "cd ~/$REMOTE_DIR && php -r '
  require \"app/bootstrap.php\";
  try { echo managers_count() > 0 ? \"yes\" : \"no\"; }
  catch (Throwable \$e) { echo \"err\"; }
' 2>/dev/null" || echo err)"

if [ "$HAS_MGR" = "yes" ]; then
  echo "  حساب مدير موجود بالفعل — تخطّي إنشاء الحساب."
  echo "  تطبيق ترحيلات قاعدة البيانات (إن وُجدت) ..."
  ssh "$SSH_HOST" "cd ~/$REMOTE_DIR && php bin/migrate.php" || echo "  (تنبيه: تعذّر تطبيق الترحيلات — راجع لاحقاً)"
else
  echo "  لننشئ حساب المدير الأول:"
  read -r -p "    اسم المدير: " M_NAME
  read -r -p "    اسم المستخدم للدخول: " M_USER
  read -r -p "    البريد الإلكتروني (اختياري، للإشعارات): " M_MAIL
  read -r -s -p "    كلمة المرور: " M_PASS; echo
  [ -n "$M_NAME" ] && [ -n "$M_USER" ] && [ -n "$M_PASS" ] || die "البيانات ناقصة."
  ssh "$SSH_HOST" "cd ~/$REMOTE_DIR && php bin/seed_user.php manager $(printf %q "$M_NAME") $(printf %q "$M_USER") $(printf %q "$M_PASS") '' $(printf %q "$M_MAIL")" \
    || die "فشل إنشاء حساب المدير. راجع app/config.local.php واتصال قاعدة البيانات."
  echo "  ✓ تم إنشاء حساب المدير."
fi

# ---------- opcache + حذف المُثبِّت + تحقق ----------
say "إبطال كاش opcache وتأمين المُثبِّت"
ssh "$SSH_HOST" "cd ~/$REMOTE_DIR && touch index.php app/*.php && rm -f install.php" || true

say "التحقق من استجابة الموقع"
code=$(ssh "$SSH_HOST" "curl -s -o /dev/null -w '%{http_code}' -k --resolve tasktrak.co:443:127.0.0.1 https://tasktrak.co/index.php?r=login 2>/dev/null || true")
echo "  صفحة الدخول (من داخل الخادم): HTTP ${code:-?}"

cat <<EOF

✓ اكتمل النشر.
  • النظام:     ${SITE_URL}
  • الدخول:     ${SITE_URL}index.php?r=login

ملاحظات:
  - أُزيل install.php تلقائياً بعد التثبيت لأسباب أمنية.
  - إن كان النطاق خلف Cloudflare فقد تحتاج إفراغ كاشه لرؤية التحديث فوراً.
  - لإضافة حسابات مدراء الأقسام: ادخل كمدير ← «المستخدمون».
EOF
