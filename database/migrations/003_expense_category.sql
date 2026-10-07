-- Migration 003: Add expense_category column to general_expenses
-- Run once against the live database.

ALTER TABLE general_expenses
  ADD COLUMN expense_category VARCHAR(100) NULL
    COMMENT 'Expense category selected by claimant'
  AFTER description;
