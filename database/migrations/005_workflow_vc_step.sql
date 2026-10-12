-- ============================================================
-- Migration 005 — Add VC step to STANDARD and ICT workflows
-- Correct flows:
--   STANDARD: HOS→Dean→Procurement→Accounts Verification→VC→Accounts Processing
--   ICT:      HOS→Dean→ICT→Procurement→Accounts Verification→VC→Accounts Processing
-- ============================================================

-- STANDARD_PURCHASE: move Accounts Processing (step 5→6), insert VC at step 5
UPDATE workflow_steps
SET step_order = 6
WHERE workflow_id = (SELECT id FROM workflow_definitions WHERE code='STANDARD_PURCHASE')
  AND step_order = 5;

INSERT IGNORE INTO workflow_steps (workflow_id, step_order, step_code, label, role_required, requires_otp, ge_status_on_reach)
SELECT id, 5, 'VICE_CHANCELLOR', 'Vice Chancellor Approval', 'VICE_CHANCELLOR', 0, 'PENDING_VC'
FROM workflow_definitions WHERE code = 'STANDARD_PURCHASE';

-- ICT_PURCHASE: move Accounts Processing (step 6→7), insert VC at step 6
UPDATE workflow_steps
SET step_order = 7
WHERE workflow_id = (SELECT id FROM workflow_definitions WHERE code='ICT_PURCHASE')
  AND step_order = 6;

INSERT IGNORE INTO workflow_steps (workflow_id, step_order, step_code, label, role_required, requires_otp, ge_status_on_reach)
SELECT id, 6, 'VICE_CHANCELLOR', 'Vice Chancellor Approval', 'VICE_CHANCELLOR', 0, 'PENDING_VC'
FROM workflow_definitions WHERE code = 'ICT_PURCHASE';
