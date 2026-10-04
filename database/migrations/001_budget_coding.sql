-- ============================================================
-- PNGUOT GEMS — Migration 001: Budget Coding & New Feature Columns
-- Run once against the pnguot_gems database.
-- ============================================================

-- GAP 1 MIGRATION — Structured Budget Coding (Div / FN / Act / Item / SI / D)
ALTER TABLE ge_accounting_lines
  ADD COLUMN budget_div  VARCHAR(20) NULL AFTER account_name,
  ADD COLUMN budget_fn   VARCHAR(20) NULL AFTER budget_div,
  ADD COLUMN budget_act  VARCHAR(20) NULL AFTER budget_fn,
  ADD COLUMN budget_item VARCHAR(20) NULL AFTER budget_act,
  ADD COLUMN budget_si   VARCHAR(20) NULL AFTER budget_item,
  ADD COLUMN budget_d    VARCHAR(20) NULL AFTER budget_si;

-- GAP 2 MIGRATION — Claimant Declaration Signature
ALTER TABLE general_expenses
  ADD COLUMN claimant_signature_data MEDIUMTEXT NULL AFTER description,
  ADD COLUMN claimant_signed_at      DATETIME   NULL AFTER claimant_signature_data;

-- GAP 3 MIGRATION — HOD Certification
ALTER TABLE workflow_tasks
  ADD COLUMN hod_signature_data MEDIUMTEXT   NULL AFTER comments,
  ADD COLUMN hod_designation    VARCHAR(150) NULL AFTER hod_signature_data,
  ADD COLUMN hod_certified_at   DATETIME     NULL AFTER hod_designation;

-- GAP 4 MIGRATION — Sent to Accounts Flag
ALTER TABLE general_expenses
  ADD COLUMN sent_to_accounts    TINYINT(1)   NOT NULL DEFAULT 0 AFTER commitment_number,
  ADD COLUMN sent_to_accounts_at DATETIME     NULL               AFTER sent_to_accounts,
  ADD COLUMN sent_to_accounts_by INT UNSIGNED NULL               AFTER sent_to_accounts_at;

ALTER TABLE general_expenses
  ADD CONSTRAINT fk_ge_sent_by
  FOREIGN KEY (sent_to_accounts_by) REFERENCES users(id) ON DELETE SET NULL;

-- GAP 5 MIGRATION — GST Percent on Line Items
ALTER TABLE ge_line_items
  ADD COLUMN gst_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER unit_price;

-- GAP 6 MIGRATION — Claimant Declaration Fields
ALTER TABLE general_expenses
  ADD COLUMN claimant_full_name        VARCHAR(200) NULL AFTER payee_name,
  ADD COLUMN claimant_declaration_date DATE         NULL AFTER claimant_signed_at;

-- GAP 7 MIGRATION — HOD Certification on General Expenses
-- NOTE: hod_signature_data / hod_designation / hod_certified_at on workflow_tasks
--       were added in GAP 3 and MUST NOT be re-added here.
ALTER TABLE general_expenses
  ADD COLUMN hod_name              VARCHAR(200) NULL AFTER claimant_declaration_date,
  ADD COLUMN hod_designation       VARCHAR(100) NULL AFTER hod_name,
  ADD COLUMN hod_signature_data    MEDIUMTEXT   NULL AFTER hod_designation,
  ADD COLUMN hod_certification_date DATE        NULL AFTER hod_signature_data,
  ADD COLUMN hod_approved          TINYINT(1)   NOT NULL DEFAULT 0 AFTER hod_certification_date,
  ADD COLUMN hod_sent_to_accounts  TINYINT(1)   NOT NULL DEFAULT 0 AFTER hod_approved;

-- GAP 5 FIX — Regenerate total_price to include GST
-- MySQL requires DROP + re-ADD for generated columns
ALTER TABLE ge_line_items
  DROP COLUMN total_price;

ALTER TABLE ge_line_items
  ADD COLUMN total_price DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_price * (1 + gst_percent / 100)) STORED AFTER gst_percent;
