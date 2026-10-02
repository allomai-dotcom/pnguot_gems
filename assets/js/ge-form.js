// ============================================================
// PNGUOT GEMS — GE Form Module
// ============================================================

const GEForm = {
  geId: null,
  lineItems: [],
  acctLines: [],
  autoSaveTimer: null,
  signaturePad: null,

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
    }

    this.bindEvents();

    const sigCanvas = document.getElementById('claimant-sig-canvas');
    if (sigCanvas && typeof SignaturePad !== 'undefined') {
      this.signaturePad = new SignaturePad(sigCanvas, { backgroundColor: 'rgb(255,255,255)' });
      document.getElementById('btn-clear-sig')?.addEventListener('click', () => this.signaturePad.clear());
    }
  },

  // ── Load existing GE ──────────────────────────────────────
  async load(id) {
    try {
      const res = await API.geGet(id);
      const ge  = res.ge;

      // Check editable
      if (!['DRAFT','READY_FOR_DOCUMENTS','QUERIED'].includes(ge.status)) {
        document.getElementById('ge-form-readonly-alert')?.classList.remove('hidden');
        document.querySelectorAll('#ge-form input, #ge-form select, #ge-form textarea')
          .forEach(el => el.disabled = true);
        document.querySelectorAll('.ge-form-actions .btn-primary').forEach(b => b.disabled = true);
        if (this.signaturePad) this.signaturePad.off();
      }

      // Populate header
      this.setVal('ge-number',              ge.ge_number);
      this.setVal('payee-name',             ge.payee_name);
      this.setVal('dept-ref',               ge.departmental_reference);
      this.setVal('claimant-ref',           ge.claimant_reference);
      this.setVal('description',            ge.description);
      this.setVal('procurement-type',       ge.procurement_type);
      this.setVal('is-capital-item',        ge.is_capital_item ? '1' : '0');

      // Finance-only fields
      if (Auth.isAdmin()) {
        this.setVal('cfc-number',        ge.cfc_number);
        this.setVal('commitment-number', ge.commitment_number);
        document.querySelectorAll('.finance-only').forEach(e => e.classList.remove('hidden'));
      }

      // Status
      const statusEl = document.getElementById('ge-status-badge');
      if (statusEl) statusEl.innerHTML = statusBadge(ge.status, ge.status_label);

      // Line items
      this.lineItems = ge.line_items?.length
        ? ge.line_items.map(li => ({
            description: li.description, quantity: li.quantity, unit_price: li.unit_price
          }))
        : [{ description: '', quantity: 1, unit_price: 0 }];
      this.renderLineItems();

      // Accounting lines
      this.acctLines = ge.accounting_lines?.length
        ? ge.accounting_lines.map(al => ({
            account_code: al.account_code, account_name: al.account_name,
            budget_div:  al.budget_div  || '',
            budget_fn:   al.budget_fn   || '',
            budget_act:  al.budget_act  || '',
            budget_item: al.budget_item || '',
            budget_si:   al.budget_si   || '',
            budget_d:    al.budget_d    || '',
            amount: al.amount, notes: al.notes
          }))
        : [{ account_code: '', account_name: '',
             budget_div: '', budget_fn: '', budget_act: '', budget_item: '', budget_si: '', budget_d: '',
             amount: 0, notes: '' }];
      this.renderAcctLines();

      // Restore claimant signature if present
      if (ge.claimant_signature_data && this.signaturePad) {
        this.signaturePad.fromDataURL(ge.claimant_signature_data);
      }

      // Audit trail
      if (ge.audit_trail?.length) this.renderAudit(ge.audit_trail);

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
      row.innerHTML = `
        <input class="form-control li-desc"  type="text"   placeholder="Description" value="${this.esc(li.description)}">
        <input class="form-control li-qty"   type="number" placeholder="Qty"  min="0.001" step="0.001" value="${li.quantity}">
        <input class="form-control li-price" type="number" placeholder="Unit Price" min="0" step="0.01" value="${li.unit_price}">
        <div class="line-total">${Fmt.currency(li.quantity * li.unit_price)}</div>
        <button class="btn btn-ghost btn-sm remove-li" title="Remove" ${this.lineItems.length <= 1 ? 'disabled' : ''}>✕</button>`;
      container.appendChild(row);
    });
    this.updateTotal();
  },

  renderAcctLines() {
    const container = document.getElementById('acct-lines-container');
    if (!container) return;
    container.innerHTML = '';
    this.acctLines.forEach((al, i) => {
      const row = document.createElement('div');
      row.className = 'acct-row';
      row.dataset.index = i;
      row.style.cssText = 'display:grid;grid-template-columns:60px 60px 60px 60px 60px 60px 1fr 120px 36px;gap:6px;margin-bottom:6px';
      row.innerHTML = `
        <input class="form-control al-div"    type="text" placeholder="Div"  style="width:60px" value="${this.esc(al.budget_div)}">
        <input class="form-control al-fn"     type="text" placeholder="FN"   style="width:60px" value="${this.esc(al.budget_fn)}">
        <input class="form-control al-act"    type="text" placeholder="Act"  style="width:60px" value="${this.esc(al.budget_act)}">
        <input class="form-control al-item"   type="text" placeholder="Item" style="width:60px" value="${this.esc(al.budget_item)}">
        <input class="form-control al-si"     type="text" placeholder="SI"   style="width:60px" value="${this.esc(al.budget_si)}">
        <input class="form-control al-d"      type="text" placeholder="D"    style="width:60px" value="${this.esc(al.budget_d)}">
        <input class="form-control al-name"   type="text" placeholder="Account Name" value="${this.esc(al.account_name)}">
        <input class="form-control al-amount" type="number" placeholder="Amount" min="0" step="0.01" value="${al.amount}">
        <button class="btn btn-ghost btn-sm remove-al" title="Remove" ${this.acctLines.length <= 1 ? 'disabled' : ''}>✕</button>`;
      container.appendChild(row);
    });
    this.updateAcctTotal();
  },

  updateTotal() {
    const total = this.lineItems.reduce((s, li) => s + (li.quantity * li.unit_price), 0);
    document.querySelectorAll('.ge-total-display').forEach(el => el.textContent = Fmt.currency(total));
    // Update line totals
    document.querySelectorAll('.line-item-row').forEach(row => {
      const i    = parseInt(row.dataset.index);
      const li   = this.lineItems[i];
      if (li) row.querySelector('.line-total').textContent = Fmt.currency(li.quantity * li.unit_price);
    });
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

    // Line item changes
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

    // Remove line item
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

    // Save draft
    document.getElementById('btn-save-draft')?.addEventListener('click', () => this.saveDraft());

    // Continue to documents
    document.getElementById('btn-ready-for-docs')?.addEventListener('click', () => this.readyForDocs());

    // Cancel GE
    document.getElementById('btn-cancel-ge')?.addEventListener('click', () => this.cancelGE());
  },

  collectHeader() {
    return {
      id:                    this.geId,
      payee_name:            document.getElementById('payee-name')?.value?.trim(),
      departmental_reference:document.getElementById('dept-ref')?.value?.trim(),
      claimant_reference:    document.getElementById('claimant-ref')?.value?.trim(),
      description:           document.getElementById('description')?.value?.trim(),
      procurement_type:      document.getElementById('procurement-type')?.value,
      is_capital_item:       document.getElementById('is-capital-item')?.value === '1',
      cfc_number:            document.getElementById('cfc-number')?.value?.trim(),
      commitment_number:     document.getElementById('commitment-number')?.value?.trim(),
      claimant_signature_data: (this.signaturePad && !this.signaturePad.isEmpty())
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
        // Update URL without reload
        history.replaceState({}, '', `ge-form.html?id=${this.geId}`);
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

  esc: (s) => String(s || '').replace(/"/g,'&quot;').replace(/</g,'&lt;'),
};

window.GEForm = GEForm;
