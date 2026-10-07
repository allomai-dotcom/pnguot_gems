// ============================================================
// PNGUOT GEMS — Workflow / Approval Module
// ============================================================

const Workflow = {
  currentTask: null,
  hodSigPad: null,

  // ── My Tasks list ─────────────────────────────────────────
  async loadMyTasks() {
    const container = document.getElementById('tasks-container');
    if (!container) return;
    container.innerHTML = `<div class="page-loader"><div class="spinner"></div> Loading tasks…</div>`;

    try {
      const res  = await API.myTasks();
      const tasks = res.tasks;
      const countEl = document.getElementById('pending-task-count');
      if (countEl) countEl.textContent = tasks.length;
      const badgeEl = document.getElementById('task-count-badge');
      if (badgeEl) badgeEl.textContent = tasks.length;

      if (!tasks.length) {
        container.innerHTML = `
          <div class="empty-state">
            <div class="es-icon">✅</div>
            <div class="es-title">No pending tasks</div>
            <div class="es-sub">All caught up! No approvals waiting.</div>
          </div>`;
        return;
      }

      container.innerHTML = `
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>GE Number</th><th>Payee</th><th>Claimant</th>
                <th>Department</th><th>Amount</th><th>Step</th>
                <th>Submitted</th><th>Action</th>
              </tr>
            </thead>
            <tbody>
              ${tasks.map(t => `
                <tr>
                  <td><span class="font-mono fw-600">${t.ge_number}</span></td>
                  <td>${t.payee_name || '—'}</td>
                  <td>${t.claimant_name}</td>
                  <td>${t.department_name}</td>
                  <td class="fw-600">${Fmt.currency(t.total_amount)}</td>
                  <td><span class="status-badge status-${t.ge_status}">${t.step_label}</span></td>
                  <td>${Fmt.date(t.submitted_at)}</td>
                  <td>
                    <button class="btn btn-primary btn-sm" onclick="Workflow.openTask(${t.task_id})">
                      Review
                    </button>
                  </td>
                </tr>`).join('')}
            </tbody>
          </table>
        </div>`;
    } catch (err) {
      container.innerHTML = `<div class="alert alert-error"><span class="alert-icon">❌</span>${err.message}</div>`;
    }
  },

  // ── Open task detail modal ────────────────────────────────
  async openTask(taskId) {
    Modal.open('task-modal');
    const body = document.getElementById('task-modal-body');
    body.innerHTML = `<div class="page-loader"><div class="spinner"></div></div>`;

    try {
      // Find task in cached list or re-fetch
      const res   = await API.myTasks();
      const task  = res.tasks.find(t => t.task_id == taskId);
      if (!task) throw { message: 'Task not found.' };

      this.currentTask = task;

      // Fetch full GE
      const geRes = await API.geGet(task.ge_id);
      const ge    = geRes.ge;

      body.innerHTML = this.renderTaskBody(task, ge);

      // OTP button
      document.getElementById('btn-request-otp')?.addEventListener('click', () => this.requestOtp(task));

      // Approve / Reject
      document.getElementById('btn-approve')?.addEventListener('click', () => this.submitDecision('APPROVED', task));
      document.getElementById('btn-reject') ?.addEventListener('click', () => this.submitDecision('REJECTED', task));
      document.getElementById('btn-query')  ?.addEventListener('click', () => this.raiseQuery(task));

      // Send to Accounts
      document.getElementById('btn-send-to-accounts')?.addEventListener('click', async () => {
        const ok = await window.confirm('Mark this GE as sent to Accounts?', 'Send to Accounts');
        if (!ok) return;
        try {
          await API.geSendToAccounts({ id: ge.id });
          Toast.success('GE marked as sent to Accounts.');
          Modal.close('task-modal');
          await this.loadMyTasks();
        } catch (err) {
          Toast.error(err.message || 'Failed.');
        }
      });

    } catch (err) {
      body.innerHTML = `<div class="alert alert-error"><span>❌</span>${err.message}</div>`;
    }
  },

  renderTaskBody(task, ge) {
    const lineItemsHtml = ge.line_items?.map((li, i) => `
      <tr>
        <td>${i+1}</td>
        <td>${li.description}</td>
        <td class="text-right">${li.quantity}</td>
        <td class="text-right">${Fmt.currency(li.unit_price)}</td>
        <td class="text-right fw-600">${Fmt.currency(li.total_price)}</td>
      </tr>`).join('') || '';

    const docsHtml = ge.documents?.map(d => `
      <div class="doc-item">
        <span class="doc-icon">📄</span>
        <div class="doc-info">
          <div class="doc-name">${d.label}</div>
          <div class="doc-meta">${d.original_name} · ${Fmt.fileSize(d.file_size)}</div>
        </div>
        <div class="doc-actions">
          <a href="${API.docView(d.id)}" target="_blank" class="btn btn-secondary btn-sm">View</a>
          <a href="${API.docDownload(d.id)}" class="btn btn-ghost btn-sm">⬇</a>
        </div>
      </div>`).join('') || '<p class="text-muted">No documents.</p>';

    const otpSection = task.requires_otp ? `
      <div class="card mt-16">
        <div class="card-header"><span class="card-title">🔐 OTP Authorization</span></div>
        <div class="card-body">
          <p class="text-muted mb-16" style="font-size:13px">This step requires OTP verification. Request an OTP and enter it below to approve.</p>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label" for="otp-input">OTP Code</label>
              <input id="otp-input" name="otp-input" class="form-control" type="text" maxlength="6" placeholder="6-digit code" style="letter-spacing:6px;font-size:18px;font-weight:700">
              <span class="form-hint" id="otp-hint"></span>
            </div>
          </div>
          <button id="btn-request-otp" class="btn btn-secondary btn-sm">📱 Send OTP to Phone</button>
        </div>
      </div>` : '';

    const sendToAccountsHtml = (() => {
      const isAccountsOfficer = Auth.hasRole('ACCOUNTS_OFFICER') || Auth.hasRole('SYSTEM_ADMIN');
      const eligibleStatus = ['PENDING_ACCOUNTS_VERIFICATION', 'PENDING_ACCOUNTS_PROCESSING'].includes(ge.status);
      if (isAccountsOfficer && eligibleStatus && !ge.sent_to_accounts) {
        return `<div class="card mt-16" id="send-to-accounts-section">
          <div class="card-body" style="display:flex;align-items:center;gap:12px">
            <span style="font-size:13px;color:var(--text-muted);flex:1">
              Mark this GE as physically sent to the Accounts Office.
            </span>
            <button class="btn btn-primary btn-sm" id="btn-send-to-accounts">📤 Send to Accounts</button>
          </div>
        </div>`;
      }
      if (ge.sent_to_accounts) {
        return `<div class="alert alert-success mt-16">
          <span class="alert-icon">✅</span> Sent to Accounts on ${Fmt.datetime(ge.sent_to_accounts_at)}.
        </div>`;
      }
      return '';
    })();

    return `
      <div class="flex-between mb-16">
        <div>
          <div class="fw-700" style="font-size:16px">${ge.ge_number}</div>
          <div class="text-muted" style="font-size:12px">Step: ${task.step_label}</div>
        </div>
        ${statusBadge(ge.status, ge.status_label)}
      </div>

      <div class="form-row mb-16">
        <div><span class="form-label">Payee</span><div class="fw-600">${ge.payee_name || '—'}</div></div>
        <div><span class="form-label">Department</span><div>${ge.department_name}</div></div>
        <div><span class="form-label">Claimant</span><div>${ge.claimant_name}</div></div>
        <div><span class="form-label">Submitted</span><div>${Fmt.date(ge.submitted_at)}</div></div>
      </div>
      <div class="form-row mb-16">
        <div><span class="form-label">Dept Ref</span><div class="font-mono">${ge.departmental_reference || '—'}</div></div>
        <div><span class="form-label">Claimant Ref</span><div class="font-mono">${ge.claimant_reference || '—'}</div></div>
        <div><span class="form-label">Type</span><div>${ge.procurement_type}${ge.is_capital_item ? ' · Capital Item' : ''}</div></div>
      </div>

      <div class="card mb-16">
        <div class="card-header"><span class="card-title">Line Items</span></div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Description</th><th class="text-right">Qty</th><th class="text-right">Unit Price</th><th class="text-right">Total</th></tr></thead>
            <tbody>${lineItemsHtml}</tbody>
            <tfoot>
              <tr><td colspan="4" style="text-align:right;font-weight:700;padding:10px 14px">TOTAL</td>
              <td style="text-align:right;font-weight:700;padding:10px 14px;color:var(--primary)">${Fmt.currency(ge.total_amount)}</td></tr>
            </tfoot>
          </table>
        </div>
      </div>

      <div class="card mb-16">
        <div class="card-header"><span class="card-title">Supporting Documents</span></div>
        <div class="card-body"><div class="doc-list">${docsHtml}</div></div>
      </div>

      ${otpSection}

      ${sendToAccountsHtml}

      <div class="form-group mt-16">
        <label class="form-label" for="task-comments">Comments</label>
        <textarea id="task-comments" name="task-comments" class="form-control" rows="3" placeholder="Add comments (required for rejection)…"></textarea>
      </div>`;
  },

  async requestOtp(task) {
    const btn = document.getElementById('btn-request-otp');
    btn.disabled = true; btn.textContent = 'Sending…';
    try {
      const res = await API.requestOtp({ task_id: task.task_id });
      Toast.success(res.message);
      if (res.otp) { // dev mode
        document.getElementById('otp-input').value = res.otp;
        document.getElementById('otp-hint').textContent = '[Dev] OTP auto-filled: ' + res.otp;
      }
      // Countdown
      let secs = 60;
      const interval = setInterval(() => {
        btn.textContent = `Resend in ${--secs}s`;
        if (secs <= 0) { clearInterval(interval); btn.disabled = false; btn.textContent = '📱 Send OTP to Phone'; }
      }, 1000);
    } catch (err) {
      Toast.error(err.message);
      btn.disabled = false; btn.textContent = '📱 Send OTP to Phone';
    }
  },

  async submitDecision(action, task) {
    const comments = document.getElementById('task-comments')?.value?.trim();
    const otp      = document.getElementById('otp-input')?.value?.trim();

    if (action === 'REJECTED' && !comments) {
      Toast.warning('Comments are required when rejecting.'); return;
    }

    // HOD certification intercept
    if (action === 'APPROVED' && task.step_code === 'HEAD_OF_SCHOOL') {
      const modal = document.getElementById('hod-cert-modal');
      modal.classList.remove('hidden');

      // Replace confirm button to avoid stacking listeners
      const oldBtn = document.getElementById('btn-hod-confirm');
      const newBtn = oldBtn.cloneNode(true);
      oldBtn.parentNode.replaceChild(newBtn, oldBtn);

      newBtn.addEventListener('click', async () => {
        if (!Workflow.hodSigPad || Workflow.hodSigPad.isEmpty()) {
          Toast.warning('Please provide your signature.'); return;
        }
        const desig = document.getElementById('hod-designation')?.value?.trim();
        if (!desig) { Toast.warning('Designation is required.'); return; }

        modal.classList.add('hidden');

        const hodSigData = Workflow.hodSigPad.toDataURL();
        const hodCertAt  = new Date().toISOString();

        const ok = await window.confirm('Are you sure you want to approve this GE?', 'Confirm Decision');
        if (!ok) return;

        try {
          const res = await API.taskApprove({
            task_id: task.task_id, action, comments, otp,
            hod_signature_data: hodSigData,
            hod_designation:    desig,
            hod_certified_at:   hodCertAt,
          });
          Toast.success(res.message);
          Modal.close('task-modal');
          await this.loadMyTasks();
        } catch (err) {
          Toast.error(err.message || 'Action failed.');
        }
      });
      return; // flow continues via modal
    }

    const label = action === 'APPROVED' ? 'approve' : 'reject';
    const ok    = await window.confirm(`Are you sure you want to ${label} this GE?`, 'Confirm Decision');
    if (!ok) return;

    try {
      const res = await API.taskApprove({ task_id: task.task_id, action, comments, otp });
      Toast.success(res.message);
      Modal.close('task-modal');
      await this.loadMyTasks();
    } catch (err) {
      Toast.error(err.message || 'Action failed.');
    }
  },

  async raiseQuery(task) {
    const message = prompt('Enter your query message (minimum 10 characters):');
    if (!message || message.trim().length < 10) {
      Toast.warning('Query message must be at least 10 characters.'); return;
    }
    try {
      const res = await API.taskQuery({ action: 'raise', task_id: task.task_id, message });
      Toast.success(res.message);
      Modal.close('task-modal');
      await this.loadMyTasks();
    } catch (err) {
      Toast.error(err.message || 'Failed to raise query.');
    }
  },
};

window.Workflow = Workflow;

document.addEventListener('DOMContentLoaded', () => {
  const hodCanvas = document.getElementById('hod-sig-canvas');
  if (hodCanvas && typeof SignaturePad !== 'undefined') {
    Workflow.hodSigPad = new SignaturePad(hodCanvas, { backgroundColor: 'rgb(255,255,255)' });
  }

  // Pre-fill today's date
  const dateEl = document.getElementById('hod-cert-date');
  if (dateEl) {
    dateEl.value = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
  }

  // Clear signature button
  document.getElementById('btn-clear-hod-sig')?.addEventListener('click', () => {
    Workflow.hodSigPad?.clear();
  });

  // Cancel HOD modal
  document.getElementById('btn-hod-cancel')?.addEventListener('click', () => {
    document.getElementById('hod-cert-modal').classList.add('hidden');
  });
});
