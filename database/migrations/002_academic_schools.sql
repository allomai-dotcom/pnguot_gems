-- ============================================================
-- Migration 002 — Replace faculty/department structure with
--                 Academic Schools
-- Run against an existing pnguot_gems database.
-- Select the correct database in phpMyAdmin before importing.
-- DO NOT use USE <db> here — shared hosts do not allow it.
-- ============================================================

-- 1. Temporarily disable foreign key checks so we can delete
--    departments that may be referenced by general_expenses
SET FOREIGN_KEY_CHECKS = 0;

-- 2. Remove old faculty rows and their child departments
DELETE FROM departments
WHERE code IN ('FBE','FNS','FED','ACCT','BUSS','NURS','BIOL','PEDUC','SEDUC');

-- 3. Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- 4. Insert the 13 Academic Schools as standalone departments
INSERT IGNORE INTO departments (code, name, faculty_code) VALUES
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
