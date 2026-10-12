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

      // Clone-replace footer buttons to remove any previously stacked listeners
      ['btn-approve', 'btn-reject', 'btn-query'].forEach(id => {
        const old = document.getElementById(id);
        if (old) { const n = old.cloneNode(true); old.parentNode.replaceChild(n, old); }
      });

      // Wire up fresh listeners
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

    const otpSection = '';

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

  async submitDecision(action, task) {
    const comments = document.getElementById('task-comments')?.value?.trim();

    if (action === 'REJECTED' && !comments) {
      Toast.warning('Comments are required when rejecting.'); return;
    }

    // Steps that require a signature from the approver
    const sigSteps = ['HEAD_OF_SCHOOL','DEAN','ICT_DIRECTOR','PROCUREMENT_MANAGER','VICE_CHANCELLOR','ACCOUNTS_VERIFICATION','ACCOUNTS_PROCESSING','ACCOUNTS_PAYMENT'];

    if (action === 'APPROVED' && sigSteps.includes(task.step_code)) {

      // HOD uses its own dedicated modal with designation field
      if (task.step_code === 'HEAD_OF_SCHOOL') {
        const modal = document.getElementById('hod-cert-modal');
        modal.classList.remove('hidden');
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
              task_id: task.task_id, action, comments,
              hod_signature_data: hodSigData,
              hod_designation:    desig,
              hod_certified_at:   hodCertAt,
            });
            Toast.success(res.message);
            Modal.close('task-modal');
            await this.loadMyTasks();
          } catch (err) { Toast.error(err.message || 'Action failed.'); }
        });
        return;
      }

      // All other approver steps — generic signature capture modal
      const stepLabels = {
        'DEAN':                  'Dean',
        'ICT_DIRECTOR':          'ICT Director',
        'PROCUREMENT_MANAGER':   'Procurement Manager',
        'VICE_CHANCELLOR':       'Vice Chancellor',
        'ACCOUNTS_VERIFICATION': 'Accounts Officer',
        'ACCOUNTS_PROCESSING':   'Accounts Officer',
        'ACCOUNTS_PAYMENT':      'Accounts Officer',
      };
      const roleLabel = stepLabels[task.step_code] || 'Approver';

      // Build / show the delegate sig modal
      let delModal = document.getElementById('delegate-sig-modal');
      if (!delModal) {
        delModal = document.createElement('div');
        delModal.id = 'delegate-sig-modal';
        delModal.className = 'modal-overlay hidden';
        delModal.innerHTML = `
          <div class="modal" style="max-width:520px">
            <div class="modal-header">
              <span class="modal-title" id="del-modal-title">Approver Signature</span>
            </div>
            <div class="modal-body">
              <p style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
                Please sign below to confirm your approval of this GE.
              </p>
              <div class="sig-field-wrap" id="del-sig-wrap">
                <div class="sig-placeholder" id="del-sig-placeholder">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" opacity=".35"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                  <span>Sign here</span>
                </div>
                <canvas id="del-sig-canvas"
                  style="display:block;width:100%;height:150px;touch-action:none"></canvas>
              </div>
              <div class="sig-field-footer">
                <span class="sig-status" id="del-sig-status"></span>
                <button type="button" id="btn-clear-del-sig" class="btn btn-ghost btn-sm">✏️ Clear</button>
              </div>
            </div>
            <div class="modal-footer">
              <button class="btn btn-secondary" id="btn-del-cancel">Cancel</button>
              <button class="btn btn-primary" id="btn-del-confirm">✅ Confirm &amp; Approve</button>
            </div>
          </div>`;
        document.body.appendChild(delModal);

        // Init signature pad — resize to fix offset/DPI issue
        const canvas = delModal.querySelector('#del-sig-canvas');
        const resizeDelCanvas = () => {
          const ratio = Math.max(window.devicePixelRatio || 1, 1);
          const rect  = canvas.getBoundingClientRect();
          if (rect.width === 0) return; // not visible yet
          canvas.width  = rect.width  * ratio;
          canvas.height = rect.height * ratio;
          canvas.getContext('2d').scale(ratio, ratio);
          if (Workflow.delSigPad) Workflow.delSigPad.clear();
        };
        Workflow.delSigPad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
        // Resize after modal is visible
        setTimeout(resizeDelCanvas, 50);
        window.addEventListener('resize', resizeDelCanvas);
        Workflow.delSigPad.addEventListener('beginStroke', () => {
          delModal.querySelector('#del-sig-placeholder')?.classList.add('hidden');
        });
        Workflow.delSigPad.addEventListener('endStroke', () => {
          if (!Workflow.delSigPad.isEmpty()) {
            delModal.querySelector('#del-sig-wrap')?.classList.add('signed');
            delModal.querySelector('#del-sig-status').textContent = '✅ Signature captured';
          }
        });
        delModal.querySelector('#btn-clear-del-sig').addEventListener('click', () => {
          Workflow.delSigPad.clear();
          delModal.querySelector('#del-sig-placeholder')?.classList.remove('hidden');
          delModal.querySelector('#del-sig-wrap')?.classList.remove('signed');
          delModal.querySelector('#del-sig-status').textContent = '';
        });
        delModal.querySelector('#btn-del-cancel').addEventListener('click', () => {
          delModal.classList.add('hidden');
        });
      }

      // Reset and show
      Workflow.delSigPad?.clear();
      delModal.querySelector('#del-sig-placeholder')?.classList.remove('hidden');
      delModal.querySelector('#del-sig-wrap')?.classList.remove('signed');
      delModal.querySelector('#del-sig-status').textContent = '';
      delModal.querySelector('#del-modal-title').textContent = `${roleLabel} Signature`;
      delModal.classList.remove('hidden');

      // Wire confirm button (clone to remove old listener)
      const oldConfirm = delModal.querySelector('#btn-del-confirm');
      const newConfirm = oldConfirm.cloneNode(true);
      oldConfirm.parentNode.replaceChild(newConfirm, oldConfirm);

      newConfirm.addEventListener('click', async () => {
        if (!Workflow.delSigPad || Workflow.delSigPad.isEmpty()) {
          Toast.warning('Please provide your signature before approving.'); return;
        }
        delModal.classList.add('hidden');
        const delegateSig = Workflow.delSigPad.toDataURL();
        const ok = await window.confirm('Are you sure you want to approve this GE?', 'Confirm Approval');
        if (!ok) return;
        try {
          const res = await API.taskApprove({
            task_id: task.task_id, action, comments,
            delegate_signature_data: delegateSig,
          });
          Toast.success(res.message);
          Modal.close('task-modal');
          await this.loadMyTasks();
        } catch (err) { Toast.error(err.message || 'Action failed.'); }
      });
      return;
    }

    // REJECTED path — no signature needed
    const label = action === 'APPROVED' ? 'approve' : 'reject';
    const ok    = await window.confirm(`Are you sure you want to ${label} this GE?`, 'Confirm Decision');
    if (!ok) return;
    try {
      const res = await API.taskApprove({ task_id: task.task_id, action, comments });
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
    // Resize to fix pen offset issue
    const resizeHod = () => {
      const ratio = Math.max(window.devicePixelRatio || 1, 1);
      const rect  = hodCanvas.getBoundingClientRect();
      if (rect.width === 0) return;
      hodCanvas.width  = rect.width  * ratio;
      hodCanvas.height = rect.height * ratio;
      hodCanvas.getContext('2d').scale(ratio, ratio);
      Workflow.hodSigPad.clear();
    };
    resizeHod();
    window.addEventListener('resize', resizeHod);
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
