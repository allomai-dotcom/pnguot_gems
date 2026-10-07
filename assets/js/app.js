// ============================================================
// PNGUOT GEMS — Core App (auth state, toast, modal, sidebar)
// ============================================================

// ── Auth state ────────────────────────────────────────────────
const Auth = {
  user: null,

  async init() {
    try {
      const res = await API.me();
      Auth.user = res.user;
      return true;
    } catch {
      return false;
    }
  },

  logout() {
    // Redirect immediately — don't wait for the server response
    API.logout().catch(() => {}); // fire and forget
    Auth.user = null;
    // Detect depth from current page location
    const depth = window.location.pathname.split('/').filter(Boolean).length;
    window.location.href = depth > 1 ? '../index.html' : 'index.html';
  },

  hasRole(role) {
    return Auth.user?.roles?.includes(role) ?? false;
  },
  hasAnyRole(roles) {
    return roles.some(r => Auth.hasRole(r));
  },
  isAdmin()    { return Auth.hasRole('SYSTEM_ADMIN'); },
  isApprover() {
    return Auth.hasAnyRole(['HEAD_OF_SCHOOL','DEAN','ICT_DIRECTOR',
      'PROCUREMENT_MANAGER','ACCOUNTS_OFFICER','VICE_CHANCELLOR']);
  },
};

// ── Toast ─────────────────────────────────────────────────────
const Toast = {
  icons: { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' },

  show(message, type = 'info', title = '', duration = 4000) {
    let container = document.getElementById('toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toast-container';
      document.body.appendChild(container);
    }
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `
      <span class="toast-icon">${this.icons[type] || 'ℹ️'}</span>
      <div class="toast-body">
        ${title ? `<div class="toast-title">${title}</div>` : ''}
        <div class="toast-msg">${message}</div>
      </div>
      <button class="toast-close" onclick="this.closest('.toast').remove()">✕</button>`;
    container.appendChild(t);
    if (duration > 0) setTimeout(() => t.remove(), duration);
    return t;
  },
  success: (msg, title) => Toast.show(msg, 'success', title),
  error:   (msg, title) => Toast.show(msg, 'error',   title, 6000),
  warning: (msg, title) => Toast.show(msg, 'warning', title, 5000),
  info:    (msg, title) => Toast.show(msg, 'info',    title),
};

// ── Modal ─────────────────────────────────────────────────────
const Modal = {
  open(id)  {
    const el = document.getElementById(id);
    if (el) { el.classList.add('show'); el.querySelector('.modal')?.focus(); }
  },
  close(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('show');
  },
  closeAll() {
    document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
  },
};

// Close modal on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) Modal.closeAll();
});

// ── Sidebar ───────────────────────────────────────────────────
const Sidebar = {
  init() {
    // Mobile toggle
    const hamburger = document.getElementById('hamburger-btn');
    const sidebar   = document.getElementById('sidebar');
    const overlay   = document.getElementById('sidebar-overlay');
    if (hamburger) {
      hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('show');
      });
    }
    if (overlay) {
      overlay.addEventListener('click', () => {
        sidebar.classList.remove('open');
        overlay.classList.remove('show');
      });
    }

    // Active nav item
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('.nav-item[data-page]').forEach(item => {
      if (item.dataset.page === currentPage) item.classList.add('active');
    });
  },

  setActive(page) {
    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
    document.querySelector(`.nav-item[data-page="${page}"]`)?.classList.add('active');
  },
};

// ── Topbar user info ──────────────────────────────────────────
function renderTopbarUser(user) {
  const nameEl    = document.getElementById('topbar-user-name');
  const roleEl    = document.getElementById('topbar-user-role');
  const avatarEl  = document.getElementById('topbar-avatar');
  const sidebarWs = document.getElementById('sidebar-ws-name');
  const sidebarSub = document.getElementById('sidebar-ws-sub');
  const sidebarFooterName = document.getElementById('sidebar-footer-name');
  const sidebarFooterAv   = document.getElementById('sidebar-footer-av');

  const initials = ((user.first_name?.[0] || '') + (user.last_name?.[0] || '')).toUpperCase();
  const fullName = `${user.first_name} ${user.last_name}`;
  const roleLbl  = (user.roles?.[0] || '').replace(/_/g, ' ');

  if (nameEl)    nameEl.textContent  = fullName;
  if (roleEl)    roleEl.textContent  = roleLbl;
  if (avatarEl)  avatarEl.textContent = initials;
  if (sidebarWs)   sidebarWs.textContent  = fullName;
  if (sidebarSub)  sidebarSub.textContent = user.dept_name || 'PNGUOT';
  if (sidebarFooterName) sidebarFooterName.textContent = fullName;
  if (sidebarFooterAv)   sidebarFooterAv.textContent   = initials;

  // Notification badge
  if (user.unread_notifications > 0) {
    document.querySelectorAll('.notif-badge').forEach(b => {
      b.textContent = user.unread_notifications;
      b.classList.remove('hidden');
    });
  }

  // Draft count badge — load in background, non-blocking
  if (document.querySelector('.draft-count')) {
    API.geList({ page: 1, limit: 1, status: 'DRAFT' }).then(res => {
      const count = res.pagination?.total ?? 0;
      document.querySelectorAll('.draft-count').forEach(b => {
        b.textContent = count;
        if (count > 0) b.classList.remove('hidden');
      });
    }).catch(() => {});
  }

  // Show/hide admin nav items
  if (user.roles?.includes('SYSTEM_ADMIN')) {
    document.querySelectorAll('.admin-only').forEach(e => e.classList.remove('hidden'));
  }
  if (user.roles?.some(r => ['HEAD_OF_SCHOOL','DEAN','ICT_DIRECTOR',
      'PROCUREMENT_MANAGER','ACCOUNTS_OFFICER','VICE_CHANCELLOR'].includes(r))) {
    document.querySelectorAll('.approver-only').forEach(e => e.classList.remove('hidden'));
  }
}

// ── Dropdown ──────────────────────────────────────────────────
document.addEventListener('click', e => {
  const trigger = e.target.closest('[data-dropdown]');
  const menu    = e.target.closest('.dropdown-menu');
  if (!trigger && !menu) {
    document.querySelectorAll('.dropdown-menu.show')
      .forEach(m => m.classList.remove('show'));
  }
  if (trigger) {
    const target = document.getElementById(trigger.dataset.dropdown);
    if (target) target.classList.toggle('show');
  }
});

// ── Tabs ──────────────────────────────────────────────────────
function initTabs(container) {
  (container || document).querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const group = btn.closest('[data-tab-group]') || btn.closest('.tab-bar').parentElement;
      group.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      group.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      const panel = group.querySelector(`[data-tab="${btn.dataset.target}"]`);
      if (panel) panel.classList.add('active');
    });
  });
}

// ── Format helpers ────────────────────────────────────────────
const Fmt = {
  currency: (v) => 'K ' + parseFloat(v || 0).toLocaleString('en-PG', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
  date:     (v) => v ? new Date(v).toLocaleDateString('en-PG', { day: '2-digit', month: 'short', year: 'numeric' }) : '—',
  datetime: (v) => v ? new Date(v).toLocaleString('en-PG',  { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—',
  fileSize: (bytes) => {
    if (!bytes) return '';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(1) + ' MB';
  },
};

// ── Status badge ──────────────────────────────────────────────
function statusBadge(status, label) {
  return `<span class="status-badge status-${status}">${label || status}</span>`;
}

// ── Confirm dialog ────────────────────────────────────────────
function confirm(message, title = 'Confirm') {
  return new Promise(resolve => {
    const id = 'confirm-modal-' + Date.now();
    const div = document.createElement('div');
    div.className = 'modal-overlay show';
    div.id = id;
    div.innerHTML = `
      <div class="modal" style="max-width:380px">
        <div class="modal-header">
          <span class="modal-title">${title}</span>
        </div>
        <div class="modal-body"><p style="font-size:14px">${message}</p></div>
        <div class="modal-footer">
          <button class="btn btn-secondary" id="${id}-no">Cancel</button>
          <button class="btn btn-danger"    id="${id}-yes">Confirm</button>
        </div>
      </div>`;
    document.body.appendChild(div);
    document.getElementById(`${id}-yes`).onclick = () => { div.remove(); resolve(true); };
    document.getElementById(`${id}-no`).onclick  = () => { div.remove(); resolve(false); };
  });
}

// ── Page guard ────────────────────────────────────────────────
async function requireAuth(allowedRoles = []) {
  const ok = await Auth.init();
  if (!ok) { window.location.href = '../index.html'; return false; }
  if (allowedRoles.length && !Auth.hasAnyRole(allowedRoles)) {
    Toast.error('You do not have permission to view this page.');
    window.location.href = 'dashboard.html';
    return false;
  }
  renderTopbarUser(Auth.user);
  Sidebar.init();
  initTabs();
  return true;
}

window.Auth   = Auth;
window.Toast  = Toast;
window.Modal  = Modal;
window.Sidebar = Sidebar;
window.Fmt    = Fmt;
window.statusBadge  = statusBadge;
window.confirm      = confirm;
window.requireAuth  = requireAuth;
window.renderTopbarUser = renderTopbarUser;
