// ============================================================
// PNGUOT GEMS — GE Form Module (v4)
// ============================================================

const GEForm = {
  geId: null,
  lineItems: [],
  acctLines: [],
  autoSaveTimer: null,
  signaturePad: null,
  hodSignaturePad: null,
  _currentUploadType: null,
  _selectedFile: null,

  // ── Bootstrap ─────────────────────────────────────────────
  async init(geId = null) {
    this.geId = geId ? parseInt(geId) : null;

    if (this.geId) {
      await this.load(this.geId);
    } else {
      this.lineItems = [{ description: '', quantity: 1, unit_price: 0 }];
      this.acctLines = [{ account_code: '', account_name: '',
        budget_div: '', budget_fn: '', budget_act: '', budget_item: '', budget_si: '', budget_d: '',
        amount: 0, notes: '' }];
      this.renderLineItems();
      this.renderAcctLines();

      // Populate dept-display from Auth.user
      const deptEl = document.getElementById('dept-display');
      if (deptEl && Auth.user) deptEl.value = Auth.user.dept_name || '';

      // Default claimant full name from Auth.user
      const claimantNameEl = document.getElementById('claimant-full-name');
      if (claimantNameEl && Auth.user) {
        claimantNameEl.value = (Auth.user.first_name || '') + ' ' + (Auth.user.last_name || '');
      }

      // Default claimant declaration date to today
      const declDateEl = document.getElementById('claimant-declaration-date');
      if (declDateEl) {
        declDateEl.value = new Date().toISOString().slice(0, 10);
      }
    }

    // Claimant signature pad
    const sigCanvas = document.getElementById('claimant-sig-canvas');
    if (sigCanvas && typeof SignaturePad !== 'undefined') {
      this.signaturePad = new SignaturePad(sigCanvas, { backgroundColor: 'rgb(255,255,255)' });
      document.getElementById('btn-clear-sig')?.addEventListener('click', () => this.signaturePad.clear());
    }

    // HOD signature pad
    const hodCanvas = document.getElementById('hod-sig-canvas');
    if (hodCanvas && typeof SignaturePad !== 'undefined') {
      this.hodSignaturePad = new SignaturePad(hodCanvas, { backgroundColor: 'rgb(255,255,255)' });
      document.getElementById('btn-clear-hod-sig')?.addEventListener('click', () => this.hodSignaturePad.clear());
    }

    // Finance fields — readonly for non-privileged users
    if (!Auth.hasAnyRole(['ACCOUNTS_OFFICER', 'SYSTEM_ADMIN'])) {
      document.getElementById('cfc-number')?.setAttribute('readonly', true);
      document.getElementById('commitment-number')?.setAttribute('readonly', true);
    }

    this.bindEvents();
    this.bindUploadZone();

    // Wire submit button
    document.getElementById('btn-submit-ge')?.addEventListener('click', () => this.submitGE());
  },

  // ── Load existing GE ──────────────────────────────────────
  async load(id) {
    try {
      const res = await API.geGet(id);
      const ge  = res.ge;

      // Check editable
      const isEditable = ['DRAFT','READY_FOR_DOCUMENTS','QUERIED'].includes(ge.status);
      if (!isEditable) {
        document.getElementById('ge-form-readonly-alert')?.classList.remove('hidden');
        document.querySelectorAll('#ge-form input, #ge-form select, #ge-form textarea')
          .forEach(el => el.disabled = true);
        document.querySelectorAll('.ge-form-actions .btn-primary').forEach(b => b.disabled = true);
        if (this.signaturePad) this.signaturePad.off();
        if (this.hodSignaturePad) this.hodSignaturePad.off();
      }

      // Section 1 — Claim Routing
      this.setVal('ge-number',        ge.ge_number);
      this.setVal('payee-name',       ge.payee_name);
      this.setVal('dept-display',     ge.department_name || (Auth.user?.dept_name) || '');
      this.setVal('dept-ref',         ge.departmental_reference);
      this.setVal('claimant-ref',     ge.claimant_reference);
      this.setVal('description',      ge.description);
      this.setVal('procurement-type', ge.procurement_type);
      this.setVal('is-capital-item',  ge.is_capital_item ? '1' : '0');

      // Status badge
      const statusEl = document.getElementById('ge-status-badge');
      if (statusEl) statusEl.innerHTML = statusBadge(ge.status, ge.status_label);

      // Section 2 — Line items
      this.lineItems = ge.line_items?.length
        ? ge.line_items.map(li => ({
            description: li.description,
            quantity:    li.quantity,
            unit_price:  li.unit_price,
            gst_percent: parseFloat(li.gst_percent) || 0,
          }))
        : [{ description: '', quantity: 1, unit_price: 0, gst_percent: 0 }];
      this.renderLineItems();

      // Section 3 — Accounting lines
      this.acctLines = ge.accounting_lines?.length
        ? ge.accounting_lines.map(al => ({
            account_code: al.account_code, account_name: al.account_name,
            budget_div:  al.budget_div  || '',
            budget_fn:   al.budget_fn   || '',
            budget_act:  al.budget_act  || '',
            budget_item: al.budget_item || '',
            budget_si:   al.budget_si   || '',
            budget_d:    al.budget_d    || '',
            amount: al.amount, notes: al.notes,
          }))
        : [{ account_code: '', account_name: '',
             budget_div: '', budget_fn: '', budget_act: '', budget_item: '', budget_si: '', budget_d: '',
             amount: 0, notes: '' }];
      this.renderAcctLines();

      // Section 4 — Claimant Declaration
      this.setVal('claimant-full-name',
        ge.claimant_full_name ||
        (ge.claimant_name ? ge.claimant_name : ((Auth.user?.first_name || '') + ' ' + (Auth.user?.last_name || ''))));
      this.setVal('claimant-declaration-date',
        ge.claimant_declaration_date || new Date().toISOString().slice(0, 10));

      // Restore claimant signature
      if (ge.claimant_signature_data && this.signaturePad) {
        this.signaturePad.fromDataURL(ge.claimant_signature_data);
      }

      // Section 5 — Financial Coding (visible to all)
      this.setVal('cfc-number',        ge.cfc_number);
      this.setVal('commitment-number', ge.commitment_number);

      // Section 6 — HOD Certification
      this.setVal('hod-name',               ge.hod_name);
      this.setVal('hod-designation',        ge.hod_designation);
      this.setVal('hod-certification-date', ge.hod_certification_date);
      const hodApprovedEl = document.getElementById('hod-approved');
      if (hodApprovedEl) hodApprovedEl.checked = !!ge.hod_approved;
      const hodSentEl = document.getElementById('hod-sent-to-accounts');
      if (hodSentEl) hodSentEl.checked = !!ge.hod_sent_to_accounts;
      if (ge.hod_signature_data && this.hodSignaturePad) {
        this.hodSignaturePad.fromDataURL(ge.hod_signature_data);
      }

      // Audit trail
      if (ge.audit_trail?.length) this.renderAudit(ge.audit_trail);

      // Section 7 — Documents inline
      this.loadDocuments();

      // Show submit button if ready
      if (ge.status === 'READY_FOR_DOCUMENTS') {
        API.geReadiness(this.geId).then(r => {
          if (r.ready) document.getElementById('btn-submit-ge')?.classList.remove('hidden');
        }).catch(() => {});
      }

    } catch (err) {
      Toast.error(err.message || 'Failed to load GE.');
    }
  },

  setVal(id, val) {
    const el = document.getElementById(id);
    if (el) el.value = val ?? '';
  },

  // ── Line items ─────────────────────────────────────────────
  renderLineItems() {
    const container = document.getElementById('line-items-container');
    if (!container) return;
    container.innerHTML = '';
    this.lineItems.forEach((li, i) => {
      const row = document.createElement('div');
      row.className = 'line-item-row';
      row.dataset.index = i;
      row.style.cssText = 'display:grid;grid-template-columns:1fr 80px 120px 110px 36px;gap:8px;margin-bottom:6px';
      row.innerHTML = `
        <input class="form-control li-desc"  type="text"   placeholder="Description" value="${this.esc(li.description)}">
        <input class="form-control li-qty"   type="number" placeholder="Qty"  min="0.001" step="0.001" value="${li.quantity}">
        <input class="form-control li-price" type="number" placeholder="Unit Rate" min="0" step="0.01" value="${li.unit_price}">
        <div class="line-total text-right" style="line-height:36px">${Fmt.currency(li.quantity * li.unit_price)}</div>
        <button class="btn btn-ghost btn-sm remove-li" title="Remove" ${this.lineItems.length <= 1 ? 'disabled' : ''}>✕</button>`;
      container.appendChild(row);
    });
    this.updateTotal();
    this.updateGSTDisplay();
  },

  renderAcctLines() {
    const container = document.getElementById('acct-lines-container');
    if (!container) return;
    container.innerHTML = '';
    this.acctLines.forEach((al, i) => {
      const row = document.createElement('div');
      row.className = 'acct-row';
      row.dataset.index = i;
      row.style.cssText = 'display:grid;grid-template-columns:60px 60px 60px 70px 60px 60px 1fr 36px;gap:6px;margin-bottom:6px';
      row.innerHTML = `
        <input class="form-control al-div"    type="text" placeholder="Div"    value="${this.esc(al.budget_div)}">
        <input class="form-control al-fn"     type="text" placeholder="FN"     value="${this.esc(al.budget_fn)}">
        <input class="form-control al-act"    type="text" placeholder="Act"    value="${this.esc(al.budget_act)}">
        <input class="form-control al-item"   type="text" placeholder="Item"   value="${this.esc(al.budget_item)}">
        <input class="form-control al-si"     type="text" placeholder="SI"     value="${this.esc(al.budget_si)}">
        <input class="form-control al-d"      type="text" placeholder="D"      value="${this.esc(al.budget_d)}">
        <input class="form-control al-amount" type="number" placeholder="Amount" min="0" step="0.01" value="${al.amount}">
        <button class="btn btn-ghost btn-sm remove-al" title="Remove" ${this.acctLines.length <= 1 ? 'disabled' : ''}>✕</button>`;
      container.appendChild(row);
    });
    this.updateAcctTotal();
  },

  updateTotal() {
    const total = this.lineItems.reduce((s, li) => s + (li.quantity * li.unit_price), 0);
    document.querySelectorAll('.ge-total-display').forEach(el => {
      el.textContent = Fmt.currency(total);
      el.dataset.raw = total;
    });
    document.querySelectorAll('.line-item-row').forEach(row => {
      const i  = parseInt(row.dataset.index);
      const li = this.lineItems[i];
      if (li) {
        const lineTotal = li.quantity * li.unit_price;
        const totalEl = row.querySelector('.line-total');
        if (totalEl) totalEl.textContent = Fmt.currency(lineTotal);
      }
    });
    const gstEl = document.getElementById('gst-amount-display');
    if (gstEl) gstEl.textContent = Fmt.currency(total * 0.10);
    this.updateAcctTotal();
  },

  updateGSTDisplay() {
    const total = this.lineItems.reduce((s, li) => s + (li.quantity * li.unit_price), 0);
    const gstEl = document.getElementById('gst-amount-display');
    if (gstEl) gstEl.textContent = Fmt.currency(total * 0.10);
  },

  updateAcctTotal() {
    const total = this.acctLines.reduce((s, al) => s + parseFloat(al.amount || 0), 0);
    document.querySelectorAll('.acct-total-display').forEach(el => el.textContent = Fmt.currency(total));
    const geTotalEl = document.querySelector('.ge-total-display');
    const geTotal   = parseFloat(geTotalEl?.dataset.raw || 0);
    const diffEl    = document.getElementById('acct-diff');
    if (diffEl) {
      const diff = total - geTotal;
      diffEl.textContent = Math.abs(diff) < 0.01 ? '✅ Balanced' : `⚠️ Difference: ${Fmt.currency(Math.abs(diff))}`;
      diffEl.style.color = Math.abs(diff) < 0.01 ? 'var(--primary)' : 'var(--danger)';
    }
  },

  // ── Events ────────────────────────────────────────────────
  bindEvents() {
    const form = document.getElementById('ge-form');
    if (!form) return;

    // Line item and accounting line input changes
    form.addEventListener('input', e => {
      const row = e.target.closest('.line-item-row');
      if (row) {
        const i  = parseInt(row.dataset.index);
        const li = this.lineItems[i];
        if (!li) return;
        if (e.target.classList.contains('li-desc'))  li.description = e.target.value;
        if (e.target.classList.contains('li-qty'))   li.quantity    = parseFloat(e.target.value) || 0;
        if (e.target.classList.contains('li-price')) li.unit_price  = parseFloat(e.target.value) || 0;
        this.updateTotal();
      }
      const aRow = e.target.closest('.acct-row');
      if (aRow) {
        const i  = parseInt(aRow.dataset.index);
        const al = this.acctLines[i];
        if (!al) return;
        if (e.target.classList.contains('al-code'))   al.account_code = e.target.value;
        if (e.target.classList.contains('al-name'))   al.account_name = e.target.value;
        if (e.target.classList.contains('al-amount')) al.amount       = parseFloat(e.target.value) || 0;
        if (e.target.classList.contains('al-div'))    al.budget_div   = e.target.value;
        if (e.target.classList.contains('al-fn'))     al.budget_fn    = e.target.value;
        if (e.target.classList.contains('al-act'))    al.budget_act   = e.target.value;
        if (e.target.classList.contains('al-item'))   al.budget_item  = e.target.value;
        if (e.target.classList.contains('al-si'))     al.budget_si    = e.target.value;
        if (e.target.classList.contains('al-d'))      al.budget_d     = e.target.value;
        this.updateAcctTotal();
      }
      this.scheduleAutoSave();
    });

    // Remove buttons
    form.addEventListener('click', e => {
      if (e.target.classList.contains('remove-li')) {
        const i = parseInt(e.target.closest('.line-item-row').dataset.index);
        this.lineItems.splice(i, 1);
        this.renderLineItems();
        this.scheduleAutoSave();
      }
      if (e.target.classList.contains('remove-al')) {
        const i = parseInt(e.target.closest('.acct-row').dataset.index);
        this.acctLines.splice(i, 1);
        this.renderAcctLines();
        this.scheduleAutoSave();
      }
    });

    // Add buttons
    document.getElementById('add-line-item')?.addEventListener('click', () => {
      this.lineItems.push({ description: '', quantity: 1, unit_price: 0 });
      this.renderLineItems();
    });
    document.getElementById('add-acct-line')?.addEventListener('click', () => {
      this.acctLines.push({ account_code: '', account_name: '',
        budget_div: '', budget_fn: '', budget_act: '', budget_item: '', budget_si: '', budget_d: '',
        amount: 0, notes: '' });
      this.renderAcctLines();
    });

    // Action buttons
    document.getElementById('btn-save-draft')?.addEventListener('click',   () => this.saveDraft());
    document.getElementById('btn-ready-for-docs')?.addEventListener('click', () => this.readyForDocs());
    document.getElementById('btn-cancel-ge')?.addEventListener('click',     () => this.cancelGE());
  },

  collectHeader() {
    return {
      id:                        this.geId,
      payee_name:                document.getElementById('payee-name')?.value?.trim(),
      departmental_reference:    document.getElementById('dept-ref')?.value?.trim(),
      claimant_reference:        document.getElementById('claimant-ref')?.value?.trim(),
      description:               document.getElementById('description')?.value?.trim(),
      procurement_type:          document.getElementById('procurement-type')?.value,
      is_capital_item:           document.getElementById('is-capital-item')?.value === '1',
      cfc_number:                document.getElementById('cfc-number')?.value?.trim(),
      commitment_number:         document.getElementById('commitment-number')?.value?.trim(),
      claimant_full_name:        document.getElementById('claimant-full-name')?.value?.trim(),
      claimant_declaration_date: document.getElementById('claimant-declaration-date')?.value || null,
      hod_name:                  document.getElementById('hod-name')?.value?.trim(),
      hod_designation:           document.getElementById('hod-designation')?.value,
      hod_certification_date:    document.getElementById('hod-certification-date')?.value || null,
      hod_approved:              document.getElementById('hod-approved')?.checked ?? false,
      hod_sent_to_accounts:      document.getElementById('hod-sent-to-accounts')?.checked ?? false,
      hod_signature_data:        (this.hodSignaturePad && !this.hodSignaturePad.isEmpty())
                                   ? this.hodSignaturePad.toDataURL()
                                   : null,
      claimant_signature_data:   (this.signaturePad && !this.signaturePad.isEmpty())
                                   ? this.signaturePad.toDataURL()
                                   : null,
    };
  },

  async saveDraft(silent = false) {
    try {
      const data = { ...this.collectHeader(), line_items: this.lineItems, accounting_lines: this.acctLines };

      if (!this.geId) {
        const res = await API.geCreate(data);
        this.geId = res.ge_id;
        document.getElementById('ge-number').value = res.ge_number;
        history.replaceState({}, '', `ge-form.html?id=${this.geId}`);
        // Now that we have an ID, bind upload zone and load docs
        this.loadDocuments();
      } else {
        await API.geUpdate(data);
      }
      if (!silent) Toast.success('Draft saved successfully.');
      document.getElementById('save-indicator')?.classList.remove('hidden');
      setTimeout(() => document.getElementById('save-indicator')?.classList.add('hidden'), 2000);
    } catch (err) {
      if (!silent) Toast.error(err.message || 'Failed to save draft.');
    }
  },

  scheduleAutoSave() {
    if (this.autoSaveTimer) clearTimeout(this.autoSaveTimer);
    this.autoSaveTimer = setTimeout(() => this.saveDraft(true), 3000);
  },

  async readyForDocs() {
    await this.saveDraft(true);
    if (!this.geId) return;
    try {
      await API.geReadyForDocs({ id: this.geId });
      Toast.success('GE validated! You can now upload supporting documents.');
      setTimeout(() => window.location.href = `ge-documents.html?id=${this.geId}`, 1500);
    } catch (err) {
      const errors = err.data?.errors || [];
      const msg = errors.length
        ? '<ul>' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>'
        : (err.message || 'Validation failed.');
      Toast.error(msg, 'Validation Error');
    }
  },

  async cancelGE() {
    const reason = prompt('Enter cancellation reason:');
    if (!reason) return;
    const ok = await window.confirm(`Cancel GE "${document.getElementById('ge-number')?.value}"?`, 'Cancel GE');
    if (!ok) return;
    try {
      await API.geCancel({ id: this.geId, reason });
      Toast.success('GE cancelled.');
      setTimeout(() => window.location.href = 'my-ges.html', 1500);
    } catch (err) {
      Toast.error(err.message || 'Failed to cancel GE.');
    }
  },

  async submitGE() {
    const ok = await window.confirm('Submit this GE for approval? The approval workflow will start immediately.', 'Submit GE');
    if (!ok) return;
    try {
      const res = await API.geSubmit({ id: this.geId });
      Toast.success(res.message);
      setTimeout(() => window.location.href = 'my-ges.html', 2000);
    } catch (err) {
      Toast.error(err.message || 'Submission failed.', 'Submission Error');
    }
  },

  // ── Section 7 — Inline Documents ──────────────────────────
  async loadDocuments() {
    const container = document.getElementById('inline-docs-content');
    if (!container || !this.geId) return;

    const REQUIRED_DOCS = [
      { type: 'QUOTATION_1',          label: 'Supplier Quotation 1' },
      { type: 'QUOTATION_2',          label: 'Supplier Quotation 2' },
      { type: 'QUOTATION_3',          label: 'Supplier Quotation 3' },
      { type: 'JUSTIFICATION_LETTER', label: 'Justification Letter' },
    ];

    try {
      const geRes = await API.geGet(this.geId);
      const ge    = geRes.ge;
      const uploadedMap = {};
      ge.documents?.forEach(d => { uploadedMap[d.document_type] = d; });
      const readonly = !['DRAFT','READY_FOR_DOCUMENTS','QUERIED'].includes(ge.status);

      const makeDocRow = (doc, required) => {
        const uploaded = uploadedMap[doc.type];
        return `
          <div class="doc-item">
            <span class="doc-icon">${uploaded ? '📄' : '⬜'}</span>
            <div class="doc-info">
              <div class="doc-name">${doc.label}${required ? ' <span class="required">*</span>' : ''}</div>
              ${uploaded
                ? `<div class="doc-meta">${uploaded.original_name} · ${Fmt.fileSize(uploaded.file_size)} · ${Fmt.date(uploaded.uploaded_at)}</div>`
                : `<div class="doc-meta text-muted">Not uploaded</div>`}
            </div>
            <div class="doc-status ${uploaded ? 'uploaded' : 'required'}">${uploaded ? '✅ Uploaded' : (required ? '⚠ Required' : 'Optional')}</div>
            <div class="doc-actions">
              ${uploaded
                ? `<a href="${API.docView(uploaded.id)}" target="_blank" class="btn btn-secondary btn-sm">View</a>
                   <button class="btn btn-ghost btn-sm" onclick="GEForm.deleteDoc(${uploaded.id})">🗑</button>`
                : (!readonly
                  ? `<button class="btn btn-primary btn-sm" onclick="GEForm.openDocUpload('${doc.type}','${doc.label}')">Upload</button>`
                  : '')}
            </div>
          </div>`;
      };

      container.innerHTML = `
        <div class="doc-list">
          <div style="font-weight:600;font-size:13px;margin-bottom:8px">Required Documents</div>
          ${REQUIRED_DOCS.map(d => makeDocRow(d, true)).join('')}
        </div>`;

      // Show/hide submit button
      if (ge.status === 'READY_FOR_DOCUMENTS') {
        API.geReadiness(this.geId).then(r => {
          const btn = document.getElementById('btn-submit-ge');
          if (btn) {
            if (r.ready) btn.classList.remove('hidden');
            else btn.classList.add('hidden');
          }
        }).catch(() => {});
      }
    } catch (err) {
      container.innerHTML = `<div class="text-muted" style="font-size:13px">Could not load documents: ${err.message}</div>`;
    }
  },

  openDocUpload(type, label) {
    this._currentUploadType = type;
    this._selectedFile = null;
    const titleEl = document.getElementById('upload-modal-title');
    if (titleEl) titleEl.textContent = 'Upload: ' + label;
    const selFile = document.getElementById('selected-file');
    if (selFile) selFile.classList.add('hidden');
    const uploadBtn = document.getElementById('btn-do-upload');
    if (uploadBtn) uploadBtn.disabled = true;
    const fileInput = document.getElementById('file-input');
    if (fileInput) fileInput.value = '';
    Modal.open('upload-modal');
  },

  async deleteDoc(docId) {
    const ok = await window.confirm('Remove this document?', 'Delete Document');
    if (!ok) return;
    try {
      await API.docDelete({ doc_id: docId });
      Toast.success('Document removed.');
      this.loadDocuments();
    } catch (err) {
      Toast.error(err.message);
    }
  },

  bindUploadZone() {
    const zone  = document.getElementById('upload-zone');
    const input = document.getElementById('file-input');
    if (!zone || !input) return;

    zone.addEventListener('click', () => input.click());
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
      e.preventDefault(); zone.classList.remove('drag-over');
      this._handleDocFile(e.dataTransfer.files[0]);
    });
    input.addEventListener('change', () => this._handleDocFile(input.files[0]));

    document.getElementById('btn-do-upload')?.addEventListener('click', async () => {
      if (!this._selectedFile) return;
      const btn  = document.getElementById('btn-do-upload');
      const prog = document.getElementById('upload-progress');
      btn.disabled = true;
      if (prog) prog.classList.remove('hidden');
      try {
        await API.docUpload(this.geId, this._currentUploadType, this._selectedFile);
        Toast.success('Document uploaded.');
        Modal.close('upload-modal');
        this.loadDocuments();
      } catch (err) {
        Toast.error(err.message || 'Upload failed.');
        btn.disabled = false;
      }
      if (prog) prog.classList.add('hidden');
    });
  },

  _handleDocFile(file) {
    if (!file) return;
    this._selectedFile = file;
    const el = document.getElementById('selected-file');
    if (el) { el.textContent = `📄 ${file.name} (${Fmt.fileSize(file.size)})`; el.classList.remove('hidden'); }
    const btn = document.getElementById('btn-do-upload');
    if (btn) btn.disabled = false;
  },

  // ── Audit trail ───────────────────────────────────────────
  renderAudit(trail) {
    const container = document.getElementById('audit-trail-container');
    if (!container) return;
    container.innerHTML = trail.map(a => `
      <div class="timeline-item">
        <div class="tl-dot">📋</div>
        <div class="tl-content">
          <div class="tl-action">${a.action.replace(/_/g,' ')}</div>
          <div class="tl-meta">${a.performed_by || 'System'} · ${Fmt.datetime(a.created_at)}</div>
          ${a.description ? `<div class="tl-desc">${a.description}</div>` : ''}
        </div>
      </div>`).join('');
  },

  esc: (s) => String(s || '').replace(/"/g, '&quot;').replace(/</g, '&lt;'),
};

window.GEForm = GEForm;
