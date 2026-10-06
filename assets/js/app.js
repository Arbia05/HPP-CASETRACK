// ============================================================
// HPP CaseTrack — Global JavaScript
// ============================================================

// --- Sidebar Toggle (Mobile) ---
function openSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebar-overlay').classList.remove('hidden');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebar-overlay').classList.add('hidden');
}

// --- Dropdown Toggles ---
function toggleNotif() {
  const dd = document.getElementById('notif-dropdown');
  const ud = document.getElementById('user-dropdown');
  if (ud) ud.classList.add('hidden');
  if (dd) {
    dd.classList.toggle('hidden');
    if (!dd.classList.contains('hidden')) loadNotifications();
  }
}
function toggleUserMenu() {
  const dd = document.getElementById('user-dropdown');
  const nd = document.getElementById('notif-dropdown');
  if (nd) nd.classList.add('hidden');
  if (dd) dd.classList.toggle('hidden');
}
document.addEventListener('click', function(e) {
  ['notif-wrapper','user-wrapper'].forEach(id => {
    const el = document.getElementById(id);
    if (el && !el.contains(e.target)) {
      const dd = el.querySelector('[id$="-dropdown"]');
      if (dd) dd.classList.add('hidden');
    }
  });
});

// --- Load Notifications via fetch ---
async function loadNotifications() {
  const list = document.getElementById('notif-list');
  if (!list) return;
  try {
    const base = window.APP_BASE || '/hpp-casetrack';
    const r = await fetch(base + '/api/notifications/index.php', {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (r.status === 401) {
      list.innerHTML = '<p class="p-4 text-sm text-gray-400 text-center">Sesi habis. Silakan login ulang.</p>';
      return;
    }
    const data = await r.json();
    if (!data.success || !data.data.length) {
      list.innerHTML = '<p class="p-4 text-sm text-gray-400 text-center">Tidak ada notifikasi.</p>';
      return;
    }
    list.innerHTML = data.data.map(n => `
      <div class="notif-item ${n.is_read == 0 ? 'unread' : ''}">
        <div class="notif-icon">
          <span class="material-symbols-outlined text-[#800000] text-[18px]">${notifIcon(n.type)}</span>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-[#191c1d] leading-snug">${n.title}</p>
          <p class="text-xs text-[#5a413d] mt-0.5 line-clamp-2">${n.message}</p>
          <p class="text-[10px] text-gray-400 mt-1">${n.time_ago}</p>
        </div>
      </div>`).join('');
  } catch(e) {
    list.innerHTML = '<p class="p-4 text-sm text-red-400 text-center">Gagal memuat.</p>';
  }
}

function notifIcon(type) {
  const map = { complaint:'inbox', case_update:'update', payment:'payments',
    reimbursement:'receipt_long', contract:'history_edu', system:'notifications' };
  return map[type] || 'notifications';
}

async function markAllReadAjax() {
  const base = window.APP_BASE || '/hpp-casetrack';
  await fetch(base + '/api/notifications/mark-read.php', { method: 'POST' });
  document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
  const badge = document.querySelector('#notif-wrapper .animate-pulse');
  if (badge) badge.remove();
}

// --- Flash Messages auto-dismiss ---
document.querySelectorAll('.alert').forEach(el => {
  setTimeout(() => { el.style.opacity = '0'; el.style.transition = 'opacity 0.4s'; setTimeout(() => el.remove(), 400); }, 4000);
});

// --- Confirm Dialog ---
function confirmAction(msg, callback) {
  if (confirm(msg)) callback();
}

// --- Format currency display ---
function formatRupiah(num) {
  return 'Rp ' + Number(num).toLocaleString('id-ID');
}

// --- File size display ---
function formatFileSize(bytes) {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1048576) return (bytes/1024).toFixed(1) + ' KB';
  return (bytes/1048576).toFixed(1) + ' MB';
}

// --- Drag & Drop file upload preview ---
function initDropzone(dropzoneId, inputId, previewId) {
  const zone = document.getElementById(dropzoneId);
  const input = document.getElementById(inputId);
  if (!zone || !input) return;

  zone.addEventListener('click', () => input.click());
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('border-[#800000]','bg-red-50'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('border-[#800000]','bg-red-50'));
  zone.addEventListener('drop', e => {
    e.preventDefault();
    zone.classList.remove('border-[#800000]','bg-red-50');
    if (e.dataTransfer.files.length) {
      input.files = e.dataTransfer.files;
      showPreview(e.dataTransfer.files, previewId);
    }
  });
  input.addEventListener('change', () => showPreview(input.files, previewId));
}

function showPreview(files, previewId) {
  const preview = document.getElementById(previewId);
  if (!preview) return;
  preview.innerHTML = '';
  Array.from(files).forEach(f => {
    const div = document.createElement('div');
    div.className = 'flex items-center gap-2 p-2 bg-[#f3f4f5] rounded-lg text-sm';
    div.innerHTML = `<span class="material-symbols-outlined text-[#800000] text-[18px]">description</span>
      <span class="truncate">${f.name}</span>
      <span class="text-xs text-gray-400 ml-auto">${formatFileSize(f.size)}</span>`;
    preview.appendChild(div);
  });
}

// --- Stepper logic ---
function goToStep(step, totalSteps) {
  for (let i = 1; i <= totalSteps; i++) {
    const panel = document.getElementById(`step-panel-${i}`);
    const dot   = document.getElementById(`step-dot-${i}`);
    if (panel) panel.classList.toggle('hidden', i !== step);
    if (dot) {
      dot.classList.toggle('bg-[#800000]', i <= step);
      dot.classList.toggle('text-white', i <= step);
      dot.classList.toggle('bg-[#e1e3e4]', i > step);
      dot.classList.toggle('text-[#5a413d]', i > step);
    }
  }
}

// --- Active nav highlight ---
(function() {
  const path = window.location.pathname;
  document.querySelectorAll('nav a[href]').forEach(a => {
    if (path.endsWith(a.getAttribute('href').replace(/^\//, ''))) {
      a.classList.add('font-bold','text-white','bg-white/15','border-l-[#fed65b]');
    }
  });
})();
