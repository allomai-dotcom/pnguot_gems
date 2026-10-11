# PNGUOT GEMS — General Expense Monitoring System

**Papua New Guinea University of Technology**  
**Version:** 5.0.0 · © 2026 Internal Use Only  
**Stack:** HTML · CSS · Vanilla JavaScript · PHP 8.2 · MySQL 5.7+

---

## Overview

GEMS is a web-based General Expense (GE) claim management system built for the Papua New Guinea University of Technology. It replaces paper-based GE forms with a fully digital, multi-level approval workflow — covering everything from drafting a claim to final payment authorisation.

Every GE submitted through GEMS is permanently recorded with full details including line items, GST calculations, supporting documents, supplier quotations, approval history, digital signatures, and a complete audit trail.

The system runs on a local XAMPP server (campus LAN) or any standard PHP hosting provider. Staff access it through a web browser — nothing is installed on staff computers.

---

## What It Does

- **Claimants** fill in and submit General Expense forms digitally
- **Approvers** (HOS, Dean, Procurement, Accounts, VC) review and approve through a structured workflow
- **Accounts Officers** assign CFC and Commitment numbers and mark GEs as sent to accounts
- **System Admins** manage users, delegate assignments, and view expenditure reports
- All actions are logged in a full audit trail
- Supporting documents (quotations, invoices, delivery dockets) are uploaded and stored securely
- Claimants can also upload already-filled paper GE forms for archiving

---

## Who Uses This System

| Role | Code | Description |
|------|------|-------------|
| Staff submitting expenses | `CLAIMANT` | Creates, fills in, and submits GE forms |
| Head of School | `HEAD_OF_SCHOOL` | First departmental approval |
| Dean | `DEAN` | Faculty-level approval |
| ICT Director | `ICT_DIRECTOR` | Reviews ICT-specific purchases |
| Procurement Manager | `PROCUREMENT_MANAGER` | Reviews procurement step |
| Accounts Officer | `ACCOUNTS_OFFICER` | Financial verification and processing |
| Vice Chancellor | `VICE_CHANCELLOR` | Final sign-off on capital items |
| IT Administrator | `SYSTEM_ADMIN` | Full system access — user and delegate management |

> A user can hold more than one role (e.g. the Head of School is also a Claimant).

---

## Access

**Local (XAMPP campus LAN):**
```
http://<server-ip>/pnguot_gems/
```

**Live server:**
```
https://gems.pnguot.ac.pg/
```

Staff need only a web browser (Chrome, Firefox, or Edge) and their login credentials.

---

## Approval Workflow

GE forms travel through a configurable multi-step approval chain based on purchase type:

| Purchase Type | Trigger | Approval Chain |
|---------------|---------|----------------|
| Standard | Default | HOS → Dean → Procurement → Accounts Verification → Accounts Processing |
| ICT | `procurement_type = ICT` | HOS → Dean → ICT Director → Procurement → Accounts Verification → Accounts Processing |
| Capital Item | Amount > K3,000 | HOS → Dean → Procurement → Vice Chancellor → Accounts Processing |

At any step, an approver can **Query** the GE — it returns to the claimant with a comment, and the workflow resumes from the queried step after the claimant responds.

---

## GE Form — 7 Sections

| # | Section | Filled by |
|---|---------|-----------|
| 1 | Claim Routing | Claimant — payee, department, references, procurement type, expense category |
| 2 | Particulars | Claimant — line items with quantity, unit rate, GST% |
| 3 | For Departmental Use | Claimant — budget coding lines (Div, FN, Act, Item, SI, D) |
| 4 | Claimant Declaration | Claimant — full name, signature, date |
| 5 | Financial Coding | Accounts Officer — CFC and Commitment numbers |
| 6 | HOD Certification | Claimant records HOD name, designation, signature, date |
| 7 | Supporting Documents | Claimant — quotations, justification letter, invoice, delivery docket |

---

## GE Status Flow

```
DRAFT
  ↓  (validate & move to document upload)
READY_FOR_DOCUMENTS
  ↓  (submit)
SUBMITTED
  ↓
PENDING_HOS  →  PENDING_DEAN  →  PENDING_ICT (if ICT)
  ↓
PENDING_PROCUREMENT
  ↓
PENDING_ACCOUNTS_VERIFICATION
  ↓
PENDING_ACCOUNTS_PROCESSING
  ↓
COMPLETED ✅

Side paths:
  QUERIED     — approver sends back with a comment
  CANCELLED   — claimant or admin cancels
```

---

## My GE Forms — Tabs

The **My GE Forms** page has three tabs:

| Tab | Description |
|-----|-------------|
| Active | GEs not yet 24 hours since submission |
| Archived | GEs submitted more than 24 hours ago, kept for reference |
| Uploaded Forms | Filled paper GE forms uploaded by the claimant for archiving |

A **Recently Submitted** banner at the top shows GEs submitted within the last 24 hours with a live countdown timer showing when each will archive.

---

## Test Accounts

The following accounts are seeded automatically via `database/seed.sql` for testing the full approval workflow end-to-end. All accounts belong to the **School of Business Studies (SBS)** department.

| Staff ID | Name | Email | Password | Role(s) |
|----------|------|-------|----------|---------|
| PNGUOT001 | Jane Doe | admin@pnguot.ac.pg | Admin@GEMS2026! | SYSTEM_ADMIN |
| PNGUOT002 | John Smith | staff@pnguot.ac.pg | Staff@GEMS2026! | CLAIMANT |
| PNGUOT003 | Alice Brown | claimant@pnguot.ac.pg | Claimant@GEMS2026! | CLAIMANT |
| PNGUOT004 | Robert Kila | hos@pnguot.ac.pg | Hos@GEMS2026! | HEAD_OF_SCHOOL, CLAIMANT |
| PNGUOT005 | Mary Tali | dean@pnguot.ac.pg | Dean@GEMS2026! | DEAN, CLAIMANT |
| PNGUOT006 | Peter Amos | ict@pnguot.ac.pg | Ict@GEMS2026! | ICT_DIRECTOR |
| PNGUOT007 | Grace Mondo | procurement@pnguot.ac.pg | Procurement@GEMS2026! | PROCUREMENT_MANAGER |
| PNGUOT008 | David Ovia | accounts@pnguot.ac.pg | Accounts@GEMS2026! | ACCOUNTS_OFFICER |
| PNGUOT009 | Thomas Naupa | vc@pnguot.ac.pg | Vc@GEMS2026! | VICE_CHANCELLOR |

> Delegate assignments for all 6 approver roles are also pre-configured globally, so a submitted GE routes automatically to the correct approver at each workflow step.

### Testing the Full Workflow

1. Log in as **Claimant** (`claimant@pnguot.ac.pg`) — create and submit a GE
2. Log in as **Head of School** (`hos@pnguot.ac.pg`) — approve the first step
3. Log in as **Dean** (`dean@pnguot.ac.pg`) — approve the second step
4. Log in as **Procurement Manager** (`procurement@pnguot.ac.pg`) — approve
5. Log in as **Accounts Officer** (`accounts@pnguot.ac.pg`) — verify and process
6. GE status becomes **COMPLETED** ✅

For an **ICT Purchase**, step 3 routes to **ICT Director** (`ict@pnguot.ac.pg`) before Procurement.  
For a **Capital Item** (> K3,000), step 4 routes to **Vice Chancellor** (`vc@pnguot.ac.pg`) before Accounts.

---

## Quick Start — Local XAMPP

### Requirements

| Item | Notes |
|------|-------|
| XAMPP 8.0+ | https://www.apachefriends.org — includes Apache, MySQL, PHP |
| PHP 8.2+ | Included in recent XAMPP versions |
| Web browser | Chrome, Firefox, or Edge (latest) |
| Windows 10/11 | Also works on macOS/Linux with XAMPP |

### Steps

**1. Copy files**
```
C:\xampp\htdocs\pnguot_gems\
```

**2. Start XAMPP**
Open XAMPP Control Panel → Start **Apache** and **MySQL**.

**3. Create the database**
Open phpMyAdmin (`http://localhost/phpmyadmin`), create a database named `pnguot_gems`, then import:
1. `database/schema.sql`
2. `database/seed.sql`

Or run the one-time setup script:
```
http://localhost/pnguot_gems/database/setup.php?token=pnguot-setup-2026
```
> ⚠️ Delete `database/setup.php` immediately after running it.

**4. Open the app**
```
http://localhost/pnguot_gems/
```

**Default admin credentials:**
```
Email:    admin@pnguot.ac.pg
Password: StrongPassword123
```
Change this immediately via **My Profile → Change Password**.

---

## Database Migrations

When upgrading an existing database (not a fresh install), run the migrations in order:

```
database/migrations/001_budget_coding.sql
database/migrations/002_academic_schools.sql
database/migrations/003_expense_category.sql
database/migrations/004_uploaded_ge_forms.sql
```

> Do **not** run migrations on a fresh install — `schema.sql` already includes all changes.

---

## Configuration

Edit `config/config.php`:

| Setting | Default | Notes |
|---------|---------|-------|
| `APP_ENV` | `development` | Set to `production` on a live server |
| `DB_HOST` | `localhost` | Database host |
| `DB_NAME` | `pnguot_gems` | Database name |
| `DB_USER` | `root` | MySQL username |
| `DB_PASS` | `` | MySQL password (blank for XAMPP default) |
| `SESSION_LIFETIME` | `28800` | Session timeout in seconds (8 hours) |
| `CORS_ALLOWED_ORIGIN` | `*` | Lock to your domain in production |
| `MAX_UPLOAD_SIZE` | `10485760` | Max file upload size (10 MB) |

---

## Project Structure

```
pnguot_gems/
│
├── index.html                    ← Login page
│
├── pages/                        ← All UI pages
│   ├── dashboard.html            ← Staff dashboard (KPI cards, recent GEs)
│   ├── my-ges.html               ← My GE forms (Active / Archived / Uploaded tabs)
│   ├── ge-form.html              ← 7-section GE form
│   ├── ge-documents.html         ← Document upload step
│   ├── approvals.html            ← Pending approvals (staff)
│   ├── notifications.html        ← Notifications
│   ├── quotation-tracker.html    ← Quotation tracker
│   ├── my-reports.html           ← Personal expenditure reports
│   ├── profile.html              ← User profile & change password
│   ├── admin-panel.html          ← Admin dashboard
│   ├── admin-users.html          ← User management
│   ├── admin-delegates.html      ← Delegate assignments
│   ├── admin-all-ges.html        ← All GE forms (admin view)
│   ├── admin-approvals.html      ← Pending approvals (admin)
│   ├── admin-reports.html        ← Expenditure reports (3 sections)
│   ├── admin-notifications.html  ← Notifications (admin)
│   └── admin-profile.html        ← Admin profile
│
├── assets/
│   ├── css/main.css              ← All styles
│   └── js/
│       ├── api.js                ← HTTP client (all API calls)
│       ├── app.js                ← Auth, toast, modal, sidebar, helpers
│       ├── ge-form.js            ← GE form logic (7 sections, auto-save)
│       └── workflow.js           ← Approval workflow UI
│
├── api/                          ← PHP backend endpoints
│   ├── auth/                     ← login, logout, me, change_password, upload_avatar
│   ├── ge/                       ← create, list, get, update, submit, cancel,
│   │                                ready_for_documents, send_to_accounts,
│   │                                quotations, my_reports, submission_readiness,
│   │                                upload_form, uploaded_forms
│   ├── documents/                ← upload, view, delete
│   ├── workflow/                 ← my_tasks, approve, query
│   └── admin/                    ← users, delegates, departments, reports, notifications
│
├── config/
│   ├── config.php                ← ⚙️ Application settings
│   ├── database.php              ← PDO singleton
│   └── helpers.php               ← Shared functions (session, auth, audit, notify)
│
├── database/
│   ├── schema.sql                ← Full DB schema (use for fresh installs)
│   ├── seed.sql                  ← Departments, roles, workflow definitions
│   ├── setup.php                 ← ⚠️ One-time setup — delete after use
│   └── migrations/               ← Incremental upgrades for existing databases
│       ├── 001_budget_coding.sql
│       ├── 002_academic_schools.sql
│       ├── 003_expense_category.sql
│       └── 004_uploaded_ge_forms.sql
│
└── uploads/
    └── documents/                ← All uploaded files (PDF, images, avatars)
        └── .htaccess             ← Blocks direct browser access to uploaded files
```

---

## Admin Setup Checklist

Before any staff can use the system:

- [ ] Create user accounts (Admin → Staff Accounts → + New User)
- [ ] Assign roles to each user
- [ ] Assign delegates for each approval role (Admin → Delegates → + Assign Delegate)
- [ ] Verify at least one delegate exists for each role in the table below:

| Role | Minimum scope |
|------|---------------|
| `HEAD_OF_SCHOOL` | Department level |
| `DEAN` | Faculty level |
| `ICT_DIRECTOR` | Global |
| `PROCUREMENT_MANAGER` | Global |
| `ACCOUNTS_OFFICER` | Global |
| `VICE_CHANCELLOR` | Global |

Without delegates, submitted GEs will stall with a **Workflow Configuration Error**.

---

## Deploying to a Live Server

1. Upload all project files to your server's web root
2. Import `database/schema.sql` then `database/seed.sql` via phpMyAdmin
3. Update `config/config.php` with your production DB credentials and domain
4. Set `APP_ENV` to `production`
5. Delete `database/setup.php`
6. Enable HTTPS — required for secure login

---

## Storage Recommendations

| Option | Best for | Notes |
|--------|----------|-------|
| On-premises server (1–2TB) | Long-term institutional use | Full control, no recurring cost, data stays on campus |
| NAS (Synology/QNAP) | Backup / overflow storage | Expandable, can replicate offsite |
| VPS (DigitalOcean/Vultr) | Remote access required | $6–$12/month, full control |
| Shared hosting | Low volume, tight budget | Works out of the box with the current codebase |
| Object storage (Wasabi/S3) | High volume file storage | ~$6/TB/month, requires code changes to `upload.php` |

**Recommended:** Dedicated on-premises server with 1–2TB storage for the primary install, plus nightly backup to a cloud bucket for disaster recovery.

---

## Security Notes

- All API endpoints require an authenticated session — unauthenticated requests return `401`
- Uploaded files are stored with UUID filenames and served only through authenticated PHP endpoints, not via direct URL
- SQL injection is prevented throughout via PDO prepared statements
- In production: use a dedicated MySQL user with only `SELECT, INSERT, UPDATE, DELETE` — never `root`
- Enable HTTPS before going live — session cookies must be transmitted over TLS
- The `uploads/documents/` directory is protected by `.htaccess` to block direct browser access

---

## Changelog

### v5.0.0 — October 2026
- Split admin and staff UIs — separate navy-blue admin sidebar vs staff indigo sidebar
- New rich profile pages with avatar card, account info, GE stats, and change password
- My GE Forms redesigned with Active / Archived / Uploaded tabs and 24-hour recent submissions banner with countdown timers
- Uploaded paper GE forms feature — claimants can upload filled paper forms for archiving
- Visible Sign Out button added to all sidebars
- System name "General Expense and Monitoring System" displayed in sidebar header
- Login page redesigned — PNGUOT logo, school name, error/success alert animations, confirmation message on successful login
- OTP token verification removed from approval workflow
- GE number field made editable on new forms (custom number or auto-generated)
- Full front-to-back bug fixes: session handling, FK validation, department check on GE create, query.php MySQL subquery fix, API base path detection, upload error handling
- All broken emoji `??` characters replaced across all pages
- Database migration 004 added for `ge_uploaded_forms` table

### v4.1.0 — October 2026
- GE form restructured into 7 numbered sections
- GST percentage column added to line items
- HOD Certification and Claimant Declaration sections added
- Financial Coding visible to all, editable by Finance only
- Quotation Tracker page added
- Dashboard redesigned with KPI cards (indigo/cyan/green/amber/pink colour themes)
- Admin expenditure reports split into 3 printable sections

### v4.0.0 — October 2026
- Initial release

---

## Documentation Files

| File | Audience |
|------|----------|
| `README.md` | Developers, IT administrators |
| `SETUP_GUIDE.md` | IT administrators — detailed installation guide |
| `USER_MANUAL.md` | All staff — how to use the system |
| `STAFF_MANAGEMENT.md` | System admin — managing users and delegates |
| `DOCUMENTATION_INDEX.md` | All — index of all documentation |

---

*For technical support, contact the ICT Department.*  
*PNGUOT GEMS v5.0.0 — October 2026*
