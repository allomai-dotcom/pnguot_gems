# PNGUOT GEMS — Documentation Index

**General Expense Monitoring System**  
Papua New Guinea University of Technology  
Version 1.0 — October 2026

---

## About This System

PNGUOT GEMS is the University's electronic General Expense (GE) form system. It replaces paper-based expense forms with a fully digital workflow — covering submission, multi-level approval routing, supporting document upload, OTP-based digital sign-off, and a complete audit trail. The system runs locally on XAMPP (Apache + MySQL + PHP) with a browser-based frontend. No internet connection is required.

---

## Documentation Files

| File | Audience | Purpose |
|------|----------|---------|
| [README.md](#readmemd) | Developers / IT | Technical overview, project structure, role and workflow reference |
| [SETUP_GUIDE.md](#setup_guidemd) | IT Administrator | Full installation, database setup, configuration, and troubleshooting |
| [QUICK_START.md](#quick_startmd) | IT Administrator | Condensed step-by-step checklist to get the system live fast |
| [USER_MANUAL.md](#user_manualmd) | All staff | How to use the system — creating GEs, uploading documents, approvals |
| [STAFF_MANAGEMENT.md](#staff_managementmd) | System Administrator | How to create, edit, and manage staff accounts and delegates |
| [DELEGATE_ACCESS_GUIDE.md](#delegate_access_guidemd) | All delegates + System Admin | How delegates access the system and perform their approval functions |

---

## README.md

**Audience:** Developers and IT staff  
**File:** `README.md`

The technical reference document for the system. Covers:

- What GEMS is and its core purpose (audit-ready expense tracking)
- Quick-start summary (copy to htdocs, run setup, open browser)
- Full project folder and file structure
- User roles and their access levels
- Approval workflow paths (Standard, ICT, Capital Item)
- GE status flow diagram
- Configuration reference (`config/config.php`)

Start here to understand the system's architecture before making any changes.

---

## SETUP_GUIDE.md

**Audience:** IT Administrator installing the system  
**File:** `SETUP_GUIDE.md`

Step-by-step installation guide. Covers:

- System requirements (XAMPP 8.0+, Windows/macOS/Linux, modern browser)
- Installing XAMPP
- Copying project files to `C:\xampp\htdocs\pnguot_gems\`
- Setting up the database — Option A (automatic via `setup.php`) and Option B (manual via phpMyAdmin)
- First-time configuration after login (change admin password, create users, assign delegates)
- Configuration file reference (`config/config.php` — DB credentials, environment mode, OTP settings)
- Starting and stopping the system
- Resetting the admin password via SQL
- Deploying to a live server (production settings, HTTPS, folder security)
- Troubleshooting table for the most common problems:
  - Apache/MySQL won't start (port conflicts)
  - Database connection errors
  - 404 / blank page
  - Login failure after manual import
  - Document upload issues

---

## QUICK_START.md

**Audience:** IT Administrator who needs the system running quickly  
**File:** `QUICK_START.md`

A condensed checklist for getting from zero to live in one sitting. Covers:

- Pre-flight check (XAMPP Apache and MySQL green)
- Opening the system and logging in as admin
- Changing the default admin password immediately
- Adding staff accounts with the correct roles
- Assigning delegates for all six approval roles (required before any GE can be submitted)
- Running a test GE end-to-end to confirm the workflow is connected
- Going live — announcing the URL to staff and directing them to the User Manual
- Summary checklist (tick off each step)
- Quick-reference URLs and common error fixes

Use this document on first setup day. For deeper troubleshooting, refer to `SETUP_GUIDE.md`.

---

## USER_MANUAL.md

**Audience:** All staff who use the system  
**File:** `USER_MANUAL.md`

The complete user guide for day-to-day use of GEMS. Covers:

- Accessing and logging in/out
- Dashboard overview and stat cards
- Creating a GE form — all seven sections:
  - Claim routing (payee, department, procurement type, capital item flag)
  - Line items (description, quantity, unit rate, GST, auto-calculated totals)
  - Cost allocation (account codes, balance indicator)
  - Claimant declaration and digital signature
  - Financial coding (Accounts Officers only)
  - HOD certification
  - Supporting documents on the form
- Saving drafts (auto-saves every 3 seconds)
- Validating the form and advancing to document upload
- Uploading the four required documents (three supplier quotes + justification letter)
- Uploading optional documents (PO, invoice, delivery docket, remittance advice)
- Submitting for approval
- Tracking GE forms and understanding statuses
- Approval workflow for approvers — reviewing, OTP authorization, approving, rejecting
- Raising and responding to queries
- Notifications — types and how to manage them
- Quotation Tracker page
- Administration section (System Admin only)
- Reports (admin and accounts staff)
- Profile and password change
- Full GE Status Reference table (15 statuses)
- Role Reference table
- 10 Frequently Asked Questions

Distribute this document to all staff before go-live.

---

## STAFF_MANAGEMENT.md

**Audience:** System Administrator  
**File:** `STAFF_MANAGEMENT.md`

A focused guide for managing staff accounts in the system. Covers:

- Accessing the User Management page
- Creating a new staff account — personal details, Staff ID, email format (`firstname.lastname@pnguot.ac.pg`), department, roles, and password options (manual or auto-generated)
- Role assignment guide (which role to give each type of staff member)
- Sharing login credentials with new staff
- Editing an existing account (name, phone, department, roles, password)
- Deactivating an account when a staff member leaves (preferred over deletion to preserve audit trail)
- Searching for staff by name, Staff ID, or email
- Email naming conventions and handling duplicate names
- Post-setup checklist: assign delegates, notify staff, run a test submission
- Common error messages and fixes

Read this alongside `QUICK_START.md` when onboarding staff for the first time.

---

## DELEGATE_ACCESS_GUIDE.md

**Audience:** HOD, Dean, ICT Director, Procurement Manager, Accounts Officer, Vice Chancellor, System Administrator  
**File:** `DELEGATE_ACCESS_GUIDE.md`

A complete guide for everyone involved in the GE approval process. Covers:

- How delegates access the system from their own computers over the campus LAN
- Step-by-step approval instructions for each role (HOD, Dean, ICT Director, Procurement Manager, Accounts Officer, Vice Chancellor)
- OTP verification process
- How to raise and respond to queries
- System Administrator setup and ongoing management functions
- Delegate assignment requirements
- Quick reference — where each role goes first after login, GE status meanings, OTP notes
- Getting help table

Distribute this to all approvers and the System Admin before go-live.

---

## Recommended Reading Order

**For IT staff setting up the system for the first time:**

```
1. README.md          — understand what you are installing
2. SETUP_GUIDE.md     — install and configure the system
3. QUICK_START.md     — go-live checklist
4. STAFF_MANAGEMENT.md — create staff accounts and assign delegates
```

**For staff who will use the system:**

```
1. USER_MANUAL.md     — everything needed to submit and track GE forms
```

**For approvers (HOS, Dean, ICT Director, Procurement, Accounts, VC):**

```
1. DELEGATE_ACCESS_GUIDE.md — how to access the system and perform your approval role
2. USER_MANUAL.md           — focus on Section 8 (Approval Workflow) and Section 9 (Queries)
```

**For the System Administrator:**

```
1. SETUP_GUIDE.md           — install and configure the system
2. QUICK_START.md           — go-live checklist
3. STAFF_MANAGEMENT.md      — create accounts and assign delegates
4. DELEGATE_ACCESS_GUIDE.md — Part 4 covers all admin functions
```

---

## System URLs (XAMPP local installation)

| Page | URL |
|------|-----|
| Login | `http://localhost/pnguot_gems/` |
| Dashboard | `http://localhost/pnguot_gems/pages/dashboard.html` |
| Users | `http://localhost/pnguot_gems/pages/admin-users.html` |
| Delegates | `http://localhost/pnguot_gems/pages/admin-delegates.html` |
| Reports | `http://localhost/pnguot_gems/pages/admin-reports.html` |
| Database setup (first time only) | `http://localhost/pnguot_gems/database/setup.php?token=pnguot-setup-2026` |
| phpMyAdmin | `http://localhost/phpmyadmin` |

---

## Default Admin Credentials

| Field | Value |
|-------|-------|
| Email | `admin@pnguot.ac.pg` |
| Password | `StrongPassword123` |

> ⚠️ Change this password immediately after first login.

---

*Papua New Guinea University of Technology — ICT Department*  
*Document version 1.0 — October 2026*
