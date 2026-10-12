-- ============================================================
-- Migration 007 — Sole-source supplier exemption
-- Allows submission with a single quotation when only one
-- supplier is available for a particular item/software
-- ============================================================

ALTER TABLE general_expenses
  ADD COLUMN is_sole_source             TINYINT(1) NOT NULL DEFAULT 0 AFTER total_amount,
  ADD COLUMN sole_source_justification  TEXT       NULL               AFTER is_sole_source;
