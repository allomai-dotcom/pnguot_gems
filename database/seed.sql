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
-- TEST STAFF USER  (password: Staff@GEMS2026!)
-- bcrypt hash generated with cost 12
-- ============================================================
INSERT INTO users (staff_id, first_name, last_name, email, password_hash, department_id)
VALUES (
    'PNGUOT002',
    'John',
    'Smith',
    'staff@pnguot.ac.pg',
    '$2y$12$8qmGWtXUo4DnDRI2q3QeZ.nyJh0I5qEcL4TrClRm4Xgznn6Tu42K2',
    (SELECT id FROM departments WHERE code = 'SBS')
);

-- Assign CLAIMANT role to test staff user
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u, roles r
WHERE u.staff_id = 'PNGUOT002' AND r.code = 'CLAIMANT';

-- ============================================================
-- WORKFLOW DEFINITIONS
-- ============================================================
INSERT INTO workflow_definitions (code, label, description) VALUES
('STANDARD_PURCHASE', 'Standard Purchase',  'Standard procurement workflow: HOS → Dean → Procurement → Accounts Verification → Accounts Processing'),
('ICT_PURCHASE',      'ICT Purchase',       'ICT procurement workflow: HOS → Dean → ICT Director → Procurement → Accounts Verification → Accounts Processing'),
('CAPITAL_ITEM',      'Capital Item',       'Capital item workflow: HOS → Dean → Procurement → Vice Chancellor → Accounts Payment');

-- ============================================================
-- WORKFLOW STEPS — STANDARD_PURCHASE
-- HOS → Dean → Procurement → Accounts Verification → VC → Accounts Processing
-- ============================================================
SET @wf_std = (SELECT id FROM workflow_definitions WHERE code = 'STANDARD_PURCHASE');
INSERT INTO workflow_steps (workflow_id, step_order, step_code, label, role_required, requires_otp, ge_status_on_reach) VALUES
(@wf_std, 1, 'HEAD_OF_SCHOOL',        'Head of School Approval',      'HEAD_OF_SCHOOL',      0, 'PENDING_HOS'),
(@wf_std, 2, 'DEAN',                  'Dean Approval',                 'DEAN',                0, 'PENDING_DEAN'),
(@wf_std, 3, 'PROCUREMENT_MANAGER',   'Procurement Review',            'PROCUREMENT_MANAGER', 0, 'PENDING_PROCUREMENT'),
(@wf_std, 4, 'ACCOUNTS_VERIFICATION', 'Accounts Verification',         'ACCOUNTS_OFFICER',    0, 'PENDING_ACCOUNTS_VERIFICATION'),
(@wf_std, 5, 'VICE_CHANCELLOR',       'Vice Chancellor Approval',      'VICE_CHANCELLOR',     0, 'PENDING_VC'),
(@wf_std, 6, 'ACCOUNTS_PROCESSING',   'Accounts Processing/Payment',   'ACCOUNTS_OFFICER',    0, 'PENDING_ACCOUNTS_PROCESSING');

-- ============================================================
-- WORKFLOW STEPS — ICT_PURCHASE
-- HOS → Dean → ICT Director → Procurement → Accounts Verification → VC → Accounts Processing
-- ============================================================
SET @wf_ict = (SELECT id FROM workflow_definitions WHERE code = 'ICT_PURCHASE');
INSERT INTO workflow_steps (workflow_id, step_order, step_code, label, role_required, requires_otp, ge_status_on_reach) VALUES
(@wf_ict, 1, 'HEAD_OF_SCHOOL',        'Head of School Approval',      'HEAD_OF_SCHOOL',      0, 'PENDING_HOS'),
(@wf_ict, 2, 'DEAN',                  'Dean Approval',                 'DEAN',                0, 'PENDING_DEAN'),
(@wf_ict, 3, 'ICT_DIRECTOR',          'ICT Director Verification',     'ICT_DIRECTOR',        0, 'PENDING_ICT'),
(@wf_ict, 4, 'PROCUREMENT_MANAGER',   'Procurement Review',            'PROCUREMENT_MANAGER', 0, 'PENDING_PROCUREMENT'),
(@wf_ict, 5, 'ACCOUNTS_VERIFICATION', 'Accounts Verification',         'ACCOUNTS_OFFICER',    0, 'PENDING_ACCOUNTS_VERIFICATION'),
(@wf_ict, 6, 'VICE_CHANCELLOR',       'Vice Chancellor Approval',      'VICE_CHANCELLOR',     0, 'PENDING_VC'),
(@wf_ict, 7, 'ACCOUNTS_PROCESSING',   'Accounts Processing/Payment',   'ACCOUNTS_OFFICER',    0, 'PENDING_ACCOUNTS_PROCESSING');

-- ============================================================
-- WORKFLOW STEPS — CAPITAL_ITEM
-- HOS → Dean → Procurement → VC → Accounts Payment
-- ============================================================
SET @wf_cap = (SELECT id FROM workflow_definitions WHERE code = 'CAPITAL_ITEM');
INSERT INTO workflow_steps (workflow_id, step_order, step_code, label, role_required, requires_otp, ge_status_on_reach) VALUES
(@wf_cap, 1, 'HEAD_OF_SCHOOL',        'Head of School Approval',      'HEAD_OF_SCHOOL',      0, 'PENDING_HOS'),
(@wf_cap, 2, 'DEAN',                  'Dean Approval',                 'DEAN',                0, 'PENDING_DEAN'),
(@wf_cap, 3, 'PROCUREMENT_MANAGER',   'Procurement Review',            'PROCUREMENT_MANAGER', 0, 'PENDING_PROCUREMENT'),
(@wf_cap, 4, 'VICE_CHANCELLOR',       'Vice Chancellor Approval',      'VICE_CHANCELLOR',     0, 'PENDING_VC'),
(@wf_cap, 5, 'ACCOUNTS_PAYMENT',      'Accounts Payment',              'ACCOUNTS_OFFICER',    0, 'PENDING_PAYMENT');

-- ============================================================
-- GE NUMBER SEQUENCE — initialise current year
-- ============================================================
INSERT INTO ge_number_sequence (year, next_val) VALUES (YEAR(NOW()), 1)
ON DUPLICATE KEY UPDATE next_val = next_val;

-- ============================================================
-- TEST ROLE ACCOUNTS (password pattern: Role@GEMS2026!)
-- ============================================================

-- PNGUOT003 — Claimant (password: Claimant@GEMS2026!)
INSERT IGNORE INTO users (staff_id, first_name, last_name, email, password_hash, department_id)
VALUES ('PNGUOT003','Alice','Brown','claimant@pnguot.ac.pg',
    '$2y$12$LUuEci3dPiwFfbolZnlzzOWyiJ3.EVLsLbuqPWPYOD/tyhVcFMUJO',
    (SELECT id FROM departments WHERE code='SBS'));
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.staff_id='PNGUOT003' AND r.code='CLAIMANT';

-- PNGUOT004 — Head of School (password: Hos@GEMS2026!)
INSERT IGNORE INTO users (staff_id, first_name, last_name, email, password_hash, department_id)
VALUES ('PNGUOT004','Robert','Kila','hos@pnguot.ac.pg',
    '$2y$12$S50YCvT2HjvaOfsbQLySJuRFaFvFPj35BrNL64fbaqTe01ILzU1/S',
    (SELECT id FROM departments WHERE code='SBS'));
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.staff_id='PNGUOT004' AND r.code='HEAD_OF_SCHOOL';
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.staff_id='PNGUOT004' AND r.code='CLAIMANT';

-- PNGUOT005 — Dean (password: Dean@GEMS2026!)
INSERT IGNORE INTO users (staff_id, first_name, last_name, email, password_hash, department_id)
VALUES ('PNGUOT005','Mary','Tali','dean@pnguot.ac.pg',
    '$2y$12$Ibo9ctK21.8SvNZUoBU6iesxtgOdOriy/zBE0tTX9xPZ55a0h6r6i',
    (SELECT id FROM departments WHERE code='SBS'));
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.staff_id='PNGUOT005' AND r.code='DEAN';
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.staff_id='PNGUOT005' AND r.code='CLAIMANT';

-- PNGUOT006 — ICT Director (password: Ict@GEMS2026!)
INSERT IGNORE INTO users (staff_id, first_name, last_name, email, password_hash, department_id)
VALUES ('PNGUOT006','Peter','Amos','ict@pnguot.ac.pg',
    '$2y$12$Y6Xgqn7zws5jIHY2tJj23.IbDeuayDsgXAHOANM8c1SUZNY20CsXW',
    (SELECT id FROM departments WHERE code='SMCS'));
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.staff_id='PNGUOT006' AND r.code='ICT_DIRECTOR';

-- PNGUOT007 — Procurement Manager (password: Procurement@GEMS2026!)
INSERT IGNORE INTO users (staff_id, first_name, last_name, email, password_hash, department_id)
VALUES ('PNGUOT007','Grace','Mondo','procurement@pnguot.ac.pg',
    '$2y$12$vR.NgYFSa9AnLx8HBfKagejja/jsL8L8nAX.GycGIXuTarkYR/CS.',
    (SELECT id FROM departments WHERE code='SBS'));
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.staff_id='PNGUOT007' AND r.code='PROCUREMENT_MANAGER';

-- PNGUOT008 — Accounts Officer (password: Accounts@GEMS2026!)
INSERT IGNORE INTO users (staff_id, first_name, last_name, email, password_hash, department_id)
VALUES ('PNGUOT008','David','Ovia','accounts@pnguot.ac.pg',
    '$2y$12$.pKmuHhYshbRIaONzUH5K.uLGmOigYtW3dLTbsRd.SIE4qTjwXmUS',
    (SELECT id FROM departments WHERE code='SBS'));
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.staff_id='PNGUOT008' AND r.code='ACCOUNTS_OFFICER';

-- PNGUOT009 — Vice Chancellor (password: Vc@GEMS2026!)
INSERT IGNORE INTO users (staff_id, first_name, last_name, email, password_hash, department_id)
VALUES ('PNGUOT009','Thomas','Naupa','vc@pnguot.ac.pg',
    '$2y$12$u3TdSbtcwISUdS5B9wpakObmUxBmA1gFZEzo2usiCbQpC4IRz51BK',
    (SELECT id FROM departments WHERE code='SBS'));
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r WHERE u.staff_id='PNGUOT009' AND r.code='VICE_CHANCELLOR';
