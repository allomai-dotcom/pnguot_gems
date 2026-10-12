-- ============================================================
-- Migration 006 — Add delegate signature columns to general_expenses
-- Stores digital signatures from each approver for audit/print
-- ============================================================

ALTER TABLE general_expenses
  ADD COLUMN dean_signature_data        MEDIUMTEXT NULL AFTER hod_sent_to_accounts,
  ADD COLUMN ict_signature_data         MEDIUMTEXT NULL AFTER dean_signature_data,
  ADD COLUMN procurement_signature_data MEDIUMTEXT NULL AFTER ict_signature_data,
  ADD COLUMN vc_signature_data          MEDIUMTEXT NULL AFTER procurement_signature_data,
  ADD COLUMN accounts_signature_data    MEDIUMTEXT NULL AFTER vc_signature_data;
