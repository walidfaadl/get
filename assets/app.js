/* تفاعلات بسيطة للواجهة */

// اختيار حالة التعقيب (تظليل الزر المختار)
document.addEventListener('click', function (e) {
  var lbl = e.target.closest('.status-choices label');
  if (lbl) {
    lbl.parentNode.querySelectorAll('label').forEach(function (l) { l.classList.remove('sel'); });
    lbl.classList.add('sel');
  }
});

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
