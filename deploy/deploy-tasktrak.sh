#!/usr/bin/env bash
# ==========================================================
#  نشر موقع/نظام المهام إلى استضافة tasktrak.co (cPanel)
#  يرفع الملفات الثابتة فقط إلى ~/public_html بدون حذف
#  الملفات المولَّدة من cPanel (.htaccess / php.ini / .user.ini)
#
#  المتطلبات على جهازك:
#   - مفتاح SSH ‏tasktrak_client مضبوط في ~/.ssh/ مع كتلة config
#     باسم المضيف "tasktrak" (انظر دليل CLIENTtasktrak.md، القسم ١)
#   - rsync (مفضّل) أو scp
#
#  الاستخدام:
#     cd <جذر المشروع>
#     bash deploy/deploy-tasktrak.sh
# ==========================================================
set -euo pipefail

SSH_HOST="${SSH_HOST:-tasktrak}"                 # اسم المضيف في ~/.ssh/config
REMOTE_DIR="${REMOTE_DIR:-public_html}"          # جذر النشر (نسبةً إلى ~)
SITE_URL="${SITE_URL:-https://tasktrak.co/}"

# الملفات المراد رفعها فقط (لا نلمس ملفات cPanel المولَّدة)
FILES=(index.html admin.html news.html tasks.html db.js)

# التأكد أننا في جذر المشروع
for f in "${FILES[@]}"; do
  if [[ ! -f "$f" ]]; then
    echo "✗ لم يُعثر على $f — شغّل السكربت من جذر المشروع." >&2
    exit 1
  fi
done

echo "» فحص الاتصال بـ $SSH_HOST ..."
ssh -o ConnectTimeout=25 "$SSH_HOST" 'echo "  متصل: $(id -un)@$(hostname) — PHP $(php -r "echo PHP_VERSION;" 2>/dev/null || echo -)"'

echo "» التأكد من وجود مجلد النشر ~/$REMOTE_DIR ..."
ssh "$SSH_HOST" "mkdir -p ~/$REMOTE_DIR"

if command -v rsync >/dev/null 2>&1; then
  echo "» الرفع عبر rsync (بدون حذف، مع نسخة احتياطية للمستبدَل) ..."
  rsync -avz --backup --suffix=".bak-$(date +%Y%m%d%H%M%S)" \
        "${FILES[@]}" "$SSH_HOST:$REMOTE_DIR/"
else
  echo "» rsync غير متوفر — الرفع عبر scp ..."
  scp "${FILES[@]}" "$SSH_HOST:$REMOTE_DIR/"
fi

echo "» إبطال كاش opcache على الخادم (touch على الملفات المرفوعة) ..."
ssh "$SSH_HOST" "cd ~/$REMOTE_DIR && touch ${FILES[*]}"

echo "» التحقق من استجابة الموقع ..."
code=$(ssh "$SSH_HOST" "curl -s -o /dev/null -w '%{http_code}' -k --resolve tasktrak.co:443:127.0.0.1 https://tasktrak.co/ 2>/dev/null || true")
echo "  الصفحة الرئيسية (من داخل الخادم): HTTP ${code:-?}"

cat <<EOF

✓ تم النشر.
  • الموقع:        $SITE_URL
  • نظام المهام:   ${SITE_URL}tasks.html
  • لوحة التحكم:   ${SITE_URL}admin.html

ملاحظة: إن كان النطاق خلف Cloudflare فقد تحتاج إفراغ كاش Cloudflare
لرؤية التحديث فوراً (انظر الدليل، القسم ٧).
EOF
