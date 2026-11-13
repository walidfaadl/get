/* تفاعلات بسيطة للواجهة */

// تبديل الوضع الداكن/الفاتح (يُحفظ في المتصفح)
(function () {
  var btn = document.getElementById('themeBtn');
  if (!btn) return;
  btn.addEventListener('click', function () {
    var cur = document.documentElement.getAttribute('data-theme');
    var next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    try { localStorage.setItem('tt-theme', next); } catch (e) {}
  });
})();

// اختيار حالة التعقيب (تظليل الزر المختار)
document.addEventListener('click', function (e) {
  var lbl = e.target.closest('.status-choices label');
  if (lbl) {
    lbl.parentNode.querySelectorAll('label').forEach(function (l) { l.classList.remove('sel'); });
    lbl.classList.add('sel');
  }

  // نسخ رابط المشاركة
  var copyBtn = e.target.closest('[data-copy]');
  if (copyBtn) {
    var inp = document.querySelector(copyBtn.getAttribute('data-copy'));
    if (inp) {
      inp.select();
      var done = function () { var t = copyBtn.textContent; copyBtn.textContent = 'تم النسخ ✓'; setTimeout(function () { copyBtn.textContent = t; }, 1600); };
      if (navigator.clipboard) { navigator.clipboard.writeText(inp.value).then(done, function () { try { document.execCommand('copy'); done(); } catch (x) {} }); }
      else { try { document.execCommand('copy'); done(); } catch (x) {} }
    }
    return;
  }

  // مشاركة عبر واجهة النظام (الهاتف)
  var sh = e.target.closest('[data-share-url]');
  if (sh && navigator.share) {
    navigator.share({ title: sh.getAttribute('data-share-title') || '', text: sh.getAttribute('data-share-text') || '', url: sh.getAttribute('data-share-url') }).catch(function () {});
  }
});

// إظهار زر المشاركة الأصلية إن كان مدعوماً
(function () {
  if (navigator.share) {
    document.querySelectorAll('.share-btn.native').forEach(function (b) { b.hidden = false; });
  }
})();

// ===== محرّر النص الغنيّ للمخرجات =====
(function () {
  var areas = document.querySelectorAll('.rte-area');
  if (!areas.length) return;

  function sync(area) {
    var tgt = document.querySelector(area.getAttribute('data-target'));
    if (tgt) tgt.value = area.innerHTML;
  }

  areas.forEach(function (area) {
    // مزامنة أولية + عند كل تعديل
    sync(area);
    area.addEventListener('input', function () { sync(area); });
    area.addEventListener('blur', function () { sync(area); });
    // مزامنة مؤكّدة قبل إرسال النموذج
    var form = area.closest('form');
    if (form) form.addEventListener('submit', function () { sync(area); });
  });

  // أزرار شريط الأدوات
  document.addEventListener('mousedown', function (e) {
    var btn = e.target.closest('.rte-btn');
    if (!btn) return;
    e.preventDefault(); // حافظ على التحديد داخل المحرّر
  });
  document.addEventListener('click', function (e) {
    var btn = e.target.closest('.rte-btn');
    if (!btn) return;
    e.preventDefault();
    var rte = btn.closest('.rte');
    var area = rte ? rte.querySelector('.rte-area') : null;
    if (area) area.focus();
    var cmd = btn.getAttribute('data-cmd');
    var val = btn.getAttribute('data-val') || null;
    try {
      if (cmd === 'createLink') {
        var url = prompt('أدخل الرابط (https://...)');
        if (url) { document.execCommand('createLink', false, url); }
      } else if (cmd === 'formatBlock') {
        document.execCommand('formatBlock', false, val);
      } else {
        document.execCommand(cmd, false, null);
      }
    } catch (x) {}
    if (area) {
      var tgt = document.querySelector(area.getAttribute('data-target'));
      if (tgt) tgt.value = area.innerHTML;
    }
  });
})();

// ===== نموذج المخرجات: المتابعة والحضور =====
(function () {
  // إظهار/إخفاء حقل موعد المتابعة
  var fc = document.getElementById('followChoices');
  var ff = document.getElementById('followupField');
  if (fc && ff) {
    fc.addEventListener('click', function (e) {
      var lbl = e.target.closest('label'); if (!lbl) return;
      ff.style.display = (lbl.getAttribute('data-fo') === 'yes') ? '' : 'none';
    });
  }

  // اختيار الحاضرين: تظليل + عدّاد + تحديد الكل
  var grid = document.getElementById('attGrid');
  if (grid) {
    var countEl = document.getElementById('attCount');
    var boxes = grid.querySelectorAll('input[type="checkbox"]');
    function refresh() {
      var n = 0;
      boxes.forEach(function (b) {
        b.closest('.att').classList.toggle('on', b.checked);
        if (b.checked) n++;
      });
      if (countEl) countEl.textContent = n;
    }
    grid.addEventListener('change', refresh);
    refresh();

    var allBtn = document.querySelector('[data-check-all]');
    if (allBtn) {
      allBtn.addEventListener('click', function () {
        var anyOff = Array.prototype.some.call(boxes, function (b) { return !b.checked; });
        boxes.forEach(function (b) { b.checked = anyOff; });
        allBtn.textContent = anyOff ? 'إلغاء التحديد' : 'تحديد الكل';
        refresh();
      });
    }
  }
})();

// نموذج المستخدمين: تعبئة للتعديل
function fillUserForm(u) {
  document.getElementById('u_id').value = u.id;
  document.getElementById('u_name').value = u.name || '';
  document.getElementById('u_username').value = u.username || '';
  document.getElementById('u_email').value = u.email || '';
  document.getElementById('u_role').value = u.role || 'head';
  document.getElementById('u_department').value = u.department || '';
  document.getElementById('u_password').value = '';
  document.getElementById('u_active').checked = !!u.active;
  document.getElementById('u_pwhint').textContent = '(اتركها فارغة لعدم التغيير)';
  document.getElementById('userFormTitle').textContent = 'تعديل مستخدم';
  window.scrollTo({ top: document.getElementById('userForm').offsetTop - 80, behavior: 'smooth' });
}

function resetUserForm() {
  var f = document.getElementById('userForm');
  f.reset();
  document.getElementById('u_id').value = '';
  document.getElementById('u_active').checked = true;
  document.getElementById('u_pwhint').textContent = '(مطلوبة للحساب الجديد)';
  document.getElementById('userFormTitle').textContent = 'إضافة مستخدم';
}
