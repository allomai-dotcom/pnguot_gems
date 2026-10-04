// ============================================================
// PNGUOT GEMS — API Client
// ============================================================

const API = {
  // Relative-to-origin base — works on any hostname (localhost or live hosting)
  base: '/api',

  async request(method, url, data = null, isFormData = false) {
    const opts = {
      method,
      credentials: 'include',
      headers: isFormData ? {} : { 'Content-Type': 'application/json' },
    };
    if (data) opts.body = isFormData ? data : JSON.stringify(data);

    try {
      const res = await fetch(url, opts);
      const json = await res.json();
      if (!res.ok && !json.success) throw { status: res.status, message: json.message || 'Request failed.', data: json };
      return json;
    } catch (err) {
      if (err.message) throw err;
      throw { message: 'Network error. Please check your connection.' };
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

  // GE
  geCreate:            d  => API.post('ge/create.php', d),
  geList:              p  => API.get('ge/list.php', p),
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
  requestOtp:  d => API.post('workflow/otp.php', d),

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
