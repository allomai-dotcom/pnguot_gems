-- ============================================================
-- PNGUOT GEMS — Seed Data
-- Run AFTER schema.sql
-- ============================================================
-- Ensure you have selected the correct database in phpMyAdmin before importing.

-- ============================================================
-- DEPARTMENTS
-- ============================================================
INSERT INTO departments (code, name, faculty_code) VALUES
-- Academic Schools
('SOA',   'School of Agriculture',                                NULL),
('SOAP',  'School of Applied Physics',                            NULL),
('SOAS',  'School of Applied Sciences',                           NULL),
('SACM',  'School of Architecture and Construction Management',   NULL),
('SBS',   'School of Business Studies',                           NULL),
('SCE',   'School of Civil Engineering',                          NULL),
('SCDS',  'School of Communication and Development Studies',      NULL),
('SECE',  'School of Electrical and Communication Engineering',   NULL),
('SOF',   'School of Forestry',                                   NULL),
('SMCS',  'School of Mathematics and Computer Science',           NULL),
('SME',   'School of Mechanical Engineering',                     NULL),
('SMINE', 'School of Mining Engineering',                         NULL),
('SSLS',  'School of Surveying and Land Studies',                 NULL);

-- ============================================================
-- ROLES
-- ============================================================
INSERT INTO roles (code, label, description) VALUES
('CLAIMANT',            'Claimant',              'Creates and submits General Expense forms'),
('HEAD_OF_SCHOOL',      'Head of School/Dept',   'First-level departmental approval'),
('DEAN',                'Dean',                  'Faculty-level approval'),
('ICT_DIRECTOR',        'ICT Director',          'ICT purchase verification'),
('PROCUREMENT_MANAGER', 'Procurement Manager',   'Procurement review and approval'),
('ACCOUNTS_OFFICER',    'Accounts Officer',      'Financial verification and payment processing'),
('VICE_CHANCELLOR',     'Vice Chancellor',        'Final approval for capital items'),
('SYSTEM_ADMIN',        'System Administrator',  'User management, configuration, delegate assignments');

-- ============================================================
-- ADMIN USER  (password: StrongPassword123)
-- bcrypt hash generated with cost 12
-- ============================================================
INSERT INTO users (staff_id, first_name, last_name, email, password_hash, department_id)
VALUES (
    'PNGUOT001',
    'Jane',
    'Doe',
    'admin@pnguot.ac.pg',
    '$2y$12$YourBcryptHashHere00000000000000000000000000000000000000', -- replaced at setup
    (SELECT id FROM departments WHERE code = 'SBS')
);

-- Assign SYSTEM_ADMIN role to admin user
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u, roles r
WHERE u.staff_id = 'PNGUOT001' AND r.code = 'SYSTEM_ADMIN';

-- ============================================================
-- WORKFLOW DEFINITIONS
-- ============================================================
INSERT INTO workflow_definitions (code, label, description) VALUES
('STANDARD_PURCHASE', 'Standard Purchase',  'Standard procurement workflow: HOS → Dean → Procurement → Accounts Verification → Accounts Processing'),
('ICT_PURCHASE',      'ICT Purchase',       'ICT procurement workflow: HOS → Dean → ICT Director → Procurement → Accounts Verification → Accounts Processing'),
('CAPITAL_ITEM',      'Capital Item',       'Capital item workflow: HOS → Dean → Procurement → Vice Chancellor → Accounts Payment');

-- ============================================================
-- WORKFLOW STEPS — STANDARD_PURCHASE
-- ============================================================
SET @wf_std = (SELECT id FROM workflow_definitions WHERE code = 'STANDARD_PURCHASE');
INSERT INTO workflow_steps (workflow_id, step_order, step_code, label, role_required, requires_otp, ge_status_on_reach) VALUES
(@wf_std, 1, 'HEAD_OF_SCHOOL',          'Head of School Approval',     'HEAD_OF_SCHOOL',      1, 'PENDING_HOS'),
(@wf_std, 2, 'DEAN',                    'Dean Approval',                'DEAN',                1, 'PENDING_DEAN'),
(@wf_std, 3, 'PROCUREMENT_MANAGER',     'Procurement Review',           'PROCUREMENT_MANAGER', 1, 'PENDING_PROCUREMENT'),
(@wf_std, 4, 'ACCOUNTS_VERIFICATION',   'Accounts Verification',        'ACCOUNTS_OFFICER',    1, 'PENDING_ACCOUNTS_VERIFICATION'),
(@wf_std, 5, 'ACCOUNTS_PROCESSING',     'Accounts Processing/Payment',  'ACCOUNTS_OFFICER',    1, 'PENDING_ACCOUNTS_PROCESSING');

-- ============================================================
-- WORKFLOW STEPS — ICT_PURCHASE
-- ============================================================
SET @wf_ict = (SELECT id FROM workflow_definitions WHERE code = 'ICT_PURCHASE');
INSERT INTO workflow_steps (workflow_id, step_order, step_code, label, role_required, requires_otp, ge_status_on_reach) VALUES
(@wf_ict, 1, 'HEAD_OF_SCHOOL',          'Head of School Approval',     'HEAD_OF_SCHOOL',      1, 'PENDING_HOS'),
(@wf_ict, 2, 'DEAN',                    'Dean Approval',                'DEAN',                1, 'PENDING_DEAN'),
(@wf_ict, 3, 'ICT_DIRECTOR',            'ICT Director Verification',    'ICT_DIRECTOR',        1, 'PENDING_ICT'),
(@wf_ict, 4, 'PROCUREMENT_MANAGER',     'Procurement Review',           'PROCUREMENT_MANAGER', 1, 'PENDING_PROCUREMENT'),
(@wf_ict, 5, 'ACCOUNTS_VERIFICATION',   'Accounts Verification',        'ACCOUNTS_OFFICER',    1, 'PENDING_ACCOUNTS_VERIFICATION'),
(@wf_ict, 6, 'ACCOUNTS_PROCESSING',     'Accounts Processing/Payment',  'ACCOUNTS_OFFICER',    1, 'PENDING_ACCOUNTS_PROCESSING');

-- ============================================================
-- WORKFLOW STEPS — CAPITAL_ITEM
-- ============================================================
SET @wf_cap = (SELECT id FROM workflow_definitions WHERE code = 'CAPITAL_ITEM');
INSERT INTO workflow_steps (workflow_id, step_order, step_code, label, role_required, requires_otp, ge_status_on_reach) VALUES
(@wf_cap, 1, 'HEAD_OF_SCHOOL',          'Head of School Approval',     'HEAD_OF_SCHOOL',      1, 'PENDING_HOS'),
(@wf_cap, 2, 'DEAN',                    'Dean Approval',                'DEAN',                1, 'PENDING_DEAN'),
(@wf_cap, 3, 'PROCUREMENT_MANAGER',     'Procurement Review',           'PROCUREMENT_MANAGER', 1, 'PENDING_PROCUREMENT'),
(@wf_cap, 4, 'VICE_CHANCELLOR',         'Vice Chancellor Approval',     'VICE_CHANCELLOR',     1, 'PENDING_VC'),
(@wf_cap, 5, 'ACCOUNTS_PAYMENT',        'Accounts Payment',             'ACCOUNTS_OFFICER',    1, 'PENDING_PAYMENT');

-- ============================================================
-- GE NUMBER SEQUENCE — initialise current year
-- ============================================================
INSERT INTO ge_number_sequence (year, next_val) VALUES (YEAR(NOW()), 1)
ON DUPLICATE KEY UPDATE next_val = next_val;
