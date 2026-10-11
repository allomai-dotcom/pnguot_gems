// ============================================================
// PNGUOT GEMS — API Client
// ============================================================

const API = {
  // Derive base path from current page location so it works under any subfolder
  // e.g. localhost/pnguot_gems/pages/dashboard.html  →  /pnguot_gems/api
  base: (() => {
    const parts = window.location.pathname.split('/').filter(Boolean);
    // If serving from a subfolder (e.g. pnguot_gems), include it; otherwise bare /api
    if (parts.length >= 1 && parts[0] !== 'api' && parts[0] !== 'pages') {
      return '/' + parts[0] + '/api';
    }
    return '/api';
  })(),

  async request(method, url, data = null, isFormData = false) {
    const opts = {
      method,
      credentials: 'include',
      headers: isFormData ? {} : { 'Content-Type': 'application/json' },
    };
    if (data) opts.body = isFormData ? data : JSON.stringify(data);

    try {
      const res  = await fetch(url, opts);
      let json;
      try {
        json = await res.json();
      } catch {
        // PHP may have output a warning before JSON — treat as server error
        throw { status: res.status, message: res.status === 500 ? 'Server error. Please try again.' : 'Unexpected server response.' };
      }
      if (!res.ok || !json.success) {
        throw { status: res.status, message: json.message || 'Request failed.', data: json };
      }
      return json;
    } catch (err) {
      // Re-throw our own error objects; wrap plain JS errors
      if (err.status !== undefined) throw err;
      throw { status: 0, message: 'Network error. Please check your connection.' };
    }
  },

  get:    (path, params = {}) => {
    const qs = new URLSearchParams(params).toString();
    return API.request('GET', `${API.base}/${path}${qs ? '?' + qs : ''}`);
  },
  post:   (path, data)        => API.request('POST',   `${API.base}/${path}`, data),
  patch:  (path, data)        => API.request('POST',   `${API.base}/${path}?update=1`, data),
  delete: (path, data)        => API.request('POST',   `${API.base}/${path}?delete=1`, data),
  upload: (path, formData)    => API.request('POST',   `${API.base}/${path}`, formData, true),

  // Auth
  login:          d  => API.post('auth/login.php', d),
  logout:         () => API.post('auth/logout.php'),
  me:             () => API.get('auth/me.php'),
  changePassword: d  => API.post('auth/change_password.php', d),
  uploadAvatar:   file => { const fd = new FormData(); fd.append('avatar', file); return API.upload('auth/upload_avatar.php', fd); },

  // GE
  geCreate:            d  => API.post('ge/create.php', d),
  geList:              p  => API.get('ge/list.php', p),
  geListRecent:        ()  => API.get('ge/list.php', { recent: '1', limit: 10 }),

  // Uploaded paper GE forms
  uploadedForms:       ()  => API.get('ge/uploaded_forms.php'),
  uploadForm:          (title, reference, notes, deptId, file) => {
    const fd = new FormData();
    fd.append('title',         title);
    fd.append('ge_reference',  reference  || '');
    fd.append('notes',         notes      || '');
    fd.append('department_id', deptId     || '');
    fd.append('ge_form',       file);
    return API.upload('ge/upload_form.php', fd);
  },
  deleteUploadedForm:  d  => API.delete('ge/uploaded_forms.php', d),
  geGet:               id => API.get('ge/get.php', { id }),
  geUpdate:            d  => API.post('ge/update.php', d),
  geReadyForDocs:      d  => API.post('ge/ready_for_documents.php', d),
  geCancel:            d  => API.post('ge/cancel.php', d),
  geSubmit:            d  => API.post('ge/submit.php', d),
  geReadiness:         id => API.get('ge/submission_readiness.php', { id }),
  geSendToAccounts:    d  => API.post('ge/send_to_accounts.php', d),
  quotations:          () => API.get('ge/quotations.php'),
  myReport:            p  => API.get('ge/my_reports.php', p),

  // Documents
  docUpload:   (geId, type, file) => {
    const fd = new FormData();
    fd.append('ge_id', geId);
    fd.append('document_type', type);
    fd.append('document', file);
    return API.upload('documents/upload.php', fd);
  },
  docView:     id => `${API.base}/documents/view.php?doc_id=${id}`,
  docDownload: id => `${API.base}/documents/view.php?doc_id=${id}&download=1`,
  docDelete:   d  => API.post('documents/delete.php', d),

  // Workflow
  myTasks:   ()  => API.get('workflow/my_tasks.php'),
  taskApprove: d => API.post('workflow/approve.php', d),
  taskQuery:   d => API.post('workflow/query.php', d),

  // Admin
  adminUsers:       p  => API.get('admin/users.php', p),
  adminUserGet:     id => API.get('admin/users.php', { id }),
  adminUserCreate:  d  => API.post('admin/users.php', d),
  adminUserUpdate:  d  => API.patch('admin/users.php', d),
  adminDelegates:   () => API.get('admin/delegates.php'),
  adminDelegateAdd: d  => API.post('admin/delegates.php', d),
  adminDelegateRemove: d => API.delete('admin/delegates.php', d),
  reports:          () => API.get('admin/reports.php'),
  reportsExpenditure: (params) => API.get('admin/reports.php', { report: 'expenditure', ...params }),
  notifications:    () => API.get('admin/notifications.php'),
  markNotifRead:    d  => API.post('admin/notifications.php', d),
};

window.API = API;
