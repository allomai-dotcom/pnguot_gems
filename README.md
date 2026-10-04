# GEMS — General Expense Monitoring System

**Papua New Guinea University of Technology — School of Business Studies**

**Version:** 4.1.0 | © 2026 Internal Use Only  
**Stack:** HTML · CSS · Vanilla JavaScript (Frontend) | PHP · MySQL (Backend via XAMPP)

---

## Overview

GEMS is a web-based financial record-keeping and expense monitoring system built for the School of Business Studies (SBS) at PNG University of Technology. It digitises the General Expense (GE) claim process — replacing paper forms with an auditable, multi-level electronic approval workflow.

The system answers three core questions at any point in time:

- **What** was the money spent on?
- **Who** authorised and processed the expense?
- **How much** was spent, and against which budget codes?

Every GE submitted through GEMS is permanently recorded with full details — line items, GST calculations, supporting documents, supplier quotations, approval history, signatures, and a complete audit trail.

GEMS runs on a local XAMPP server (campus LAN) or any live PHP hosting provider. No internet connection is required for the local setup. The frontend is a browser-based application — staff open it in Chrome, Firefox, or Edge. Nothing is installed on staff computers.

---

## Who Uses This System

GEMS is deployed for the School of Business Studies only. The following staff are involved:

| Person | Role in GEMS | What they do |
|--------|-------------|--------------|
| Any SBS staff member submitting an expense | CLAIMANT | Creates, fills in, and submits GE forms |
| Head of School (SBS) | HEAD_OF_SCHOOL + CLAIMANT | First-level departmental approval |
| Dean responsible for SBS | DEAN | Faculty-level approval |
| ICT contact (for ICT purchases) | ICT_DIRECTOR | Reviews and approves ICT-specific purchases |
| Procurement Officer | PROCUREMENT_MANAGER | Reviews procurement before finance step |
| Finance / Accounts Officer | ACCOUNTS_OFFICER | Verifies and processes payment |
| Vice Chancellor | VICE_CHANCELLOR | Final approval for capital items only |
| IT staff managing the system | SYSTEM_ADMIN | User management, delegate assignments, reports |

> A user can hold more than one role. For example, the Head of School is typically also a CLAIMANT.

---

## How Staff Access the System

GEMS runs on one central computer (the admin's machine) with XAMPP. Staff do not install anything — they open a web browser and go to the system's URL.

### Local network access (campus LAN or Wi-Fi)

The admin shares the IP address of the computer running XAMPP. Staff on the same network visit:

```
http://<server-ip>/pnguot_gems/
```

For example: `http://192.168.1.10/pnguot_gems/`

> To find the server's IP address on Windows, open Command Prompt and run `ipconfig`. Look for the IPv4 address under your active network adapter.

### Deployed on a university server

If the system is moved to a proper server, all staff access it at a domain such as:

```
https://gems.pnguot.ac.pg/
```

### What staff need

- A web browser (Chrome, Firefox, or Edge — latest version)
- The URL of the system
- Their individual login credentials (email + password), provided by the admin

---

## How the Approval Process Works

When a staff member submits a General Expense, it travels through a structured approval chain. The chain depends on the type of purchase:

### Standard Purchase
```
Claimant submits GE
    ↓
Head of School reviews → approves (OTP required)
    ↓
Dean reviews → approves (OTP required)
    ↓
Procurement Manager reviews → approves (OTP required)
    ↓
Accounts Verification → approves (OTP required)
    ↓
Accounts Processing → payment confirmed ✅
```

### ICT Purchase
```
Claimant submits GE
    ↓
Head of School → Dean → ICT Director → Procurement Manager → Accounts ✅
```

### Capital Item (> K3,000)
```
Claimant submits GE
    ↓
Head of School → Dean → Procurement Manager → Vice Chancellor → Accounts ✅
```

If any approver has a concern, they **Query** the GE — it is returned to the claimant with a comment. The claimant updates the form and resubmits. The workflow resumes from where it was queried.

---

## GE Form — 7 Sections

Each General Expense form is divided into seven numbered sections:

| Section | Name | Filled by |
|---------|------|-----------|
| 1 | Claim Routing | Claimant — payee, department, references, procurement type |
| 2 | Particulars of Expenditure | Claimant — line items with quantity, unit rate, and GST% |
| 3 | Department Use | Claimant — accounting/budget coding lines |
| 4 | Claimant Declaration | Claimant — full name, signature, date |
| 5 | Financial Coding | Accounts Officer — CFC and Commitment numbers (visible to all, editable by Finance only) |
| 6 | HOD Certification | Claimant records HOD details — HOD name, designation, signature, date, approval status |
| 7 | Supporting Documents | Claimant — upload receipts, invoices, and quotations |

---

## GE Status Flow

A GE moves through these statuses from creation to completion:

```
DRAFT
  ↓
READY_FOR_DOCUMENTS    (claimant uploads supporting documents)
  ↓
SUBMITTED              (sent into the approval workflow)
  ↓
PENDING_HOS            (waiting for Head of School)
  ↓
PENDING_DEAN           (waiting for Dean)
  ↓
PENDING_ICT            (ICT purchases only)
  ↓
PENDING_PROCUREMENT    (waiting for Procurement Manager)
  ↓
PENDING_ACCOUNTS_VERIFICATION
  ↓
PENDING_ACCOUNTS_PROCESSING
  ↓
COMPLETED ✅

At any point:
  → QUERIED  (approver sends back to claimant with a comment)
  → CANCELLED (claimant or admin cancels)
```

---

## Admin Setup — Before Staff Can Use the System

The SYSTEM_ADMIN must complete these steps before any other staff member can log in and use the system.

### Step 1 — Create user accounts

Go to **👥 Users → + New User** for each staff member. Assign their role(s) from the table under "Who Uses This System" above.

### Step 2 — Assign delegates

Go to **🔗 Delegates → + Assign Delegate**. This maps each approval role to the correct person. Without this, submitted GEs will get stuck with a **Workflow Configuration Error**.

Minimum delegate assignments for SBS:

| Role | Scope | Assign to |
|------|-------|-----------|
| HEAD_OF_SCHOOL | Department: SBS | Head of School of Business Studies |
| DEAN | Faculty code (SBS faculty) | Dean responsible for SBS |
| ICT_DIRECTOR | Global | University ICT Director |
| PROCUREMENT_MANAGER | Global | University Procurement Manager |
| ACCOUNTS_OFFICER | Global | Finance / Accounts Officer |
| VICE_CHANCELLOR | Global | Vice Chancellor |

> Tip: A user must have the matching role assigned on their account before they can be assigned as a delegate for that role.

---

## Quick Start — Local XAMPP Installation

### Requirements

| Requirement | Notes |
|-------------|-------|
| XAMPP 8.0+ | Download from https://www.apachefriends.org — includes Apache, MySQL, PHP |
| Web browser | Chrome, Firefox, or Edge (latest) |
| Windows 10 or 11 | Also works on macOS/Linux with XAMPP |

### 1. Copy project files

Copy the project folder into XAMPP's web root and rename it:

```
C:\xampp\htdocs\pnguot_gems\
```

### 2. Start XAMPP

Open the XAMPP Control Panel and click **Start** next to both **Apache** and **MySQL**. Both rows should turn green.

### 3. Set up the database

Open your browser and go to:

```
http://localhost/pnguot_gems/database/setup.php?token=pnguot-setup-2026
```

Wait for the confirmation message:

```
✅ Schema created.
✅ Seed data inserted.
✅ Setup complete!

Admin credentials:
Email:    admin@pnguot.ac.pg
Password: StrongPassword123
```

> ⚠️ Delete `database/setup.php` immediately after this step.

### 4. Open the app

```
http://localhost/pnguot_gems/
```

Log in with the admin credentials above, then change the password immediately via Profile → Change Password.

---

## Deploying to a Live Server

For access outside the campus LAN, deploy to a web server or hosting provider.

1. Upload all project files to your server's web root (e.g. `/var/www/html/pnguot_gems/`)
2. Import `database/schema.sql` then `database/seed.sql` via phpMyAdmin
3. Update `config/config.php`:

```php
define('APP_ENV',  'production');
define('DB_HOST',  'localhost');
define('DB_NAME',  'pnguot_gems');
define('DB_USER',  'your_db_user');
define('DB_PASS',  'your_db_password');
define('CORS_ALLOWED_ORIGIN', 'https://your-domain.edu.pg');
```

4. Delete `database/setup.php` from the server
5. Enable HTTPS — credentials and OTP codes must travel over an encrypted connection

For full step-by-step instructions see **`SETUP_GUIDE.md`**.

---

## Configuration

Edit `config/config.php` to change:

| Setting | Default | Notes |
|---------|---------|-------|
| `DB_HOST` | `localhost` | Database server |
| `DB_NAME` | `pnguot_gems` | Database name |
| `DB_USER` | `root` | MySQL username |
| `DB_PASS` | `` | MySQL password (blank for XAMPP default) |
| `APP_ENV` | `development` | Set to `production` on a live server |
| `SESSION_LIFETIME` | `28800` | Session duration in seconds (default 8 hours) |
| `CORS_ALLOWED_ORIGIN` | `*` | Lock to your domain in production |

In `development` mode, OTP verification is bypassed and the code is returned in the API response — useful for local testing.

---

## Project Structure

```
pnguot_gems/
│
├── index.html                    ← Login page (entry point)
│
├── pages/                        ← All application pages
│   ├── dashboard.html
│   ├── my-ges.html
│   ├── ge-form.html
│   ├── ge-documents.html
│   ├── approvals.html
│   ├── quotation-tracker.html
│   ├── admin-users.html
│   ├── admin-delegates.html
│   ├── admin-reports.html
│   ├── notifications.html
│   └── profile.html
│
├── assets/
│   ├── css/main.css              ← All styles (dark sidebar theme)
│   └── js/
│       ├── api.js                ← API client (all HTTP calls)
│       ├── app.js                ← Auth, toast, modal, sidebar
│       ├── ge-form.js            ← GE form module (7-section form logic)
│       └── workflow.js           ← Approval workflow module
│
├── api/                          ← PHP backend endpoints
│   ├── auth/                     ← login, logout, me, change_password
│   ├── ge/                       ← create, list, get, update, submit, cancel…
│   ├── documents/                ← upload, view, delete
│   ├── workflow/                 ← my_tasks, approve, query, otp
│   └── admin/                    ← users, delegates, reports, notifications
│
├── config/
│   ├── config.php                ← ⚙️ Application settings (edit this)
│   ├── database.php              ← PDO singleton
│   └── helpers.php               ← Shared utility functions
│
├── database/
│   ├── schema.sql                ← Full DB schema
│   ├── seed.sql                  ← Departments, roles, workflow definitions
│   ├── migrations/
│   │   ├── 001_budget_coding.sql ← Run when upgrading an existing database
│   │   └── 002_academic_schools.sql
│   └── setup.php                 ← ⚠️ One-time setup script — delete after use
│
└── uploads/
    └── documents/                ← Uploaded files (not directly web-accessible)
        └── .htaccess             ← Blocks direct browser access
```

---

## User Roles — Full Reference

| Role | Description |
|------|-------------|
| `CLAIMANT` | Creates and submits GE forms, uploads supporting documents |
| `HEAD_OF_SCHOOL` | First-level departmental approval |
| `DEAN` | Faculty-level approval |
| `ICT_DIRECTOR` | Reviews and approves ICT-specific purchases |
| `PROCUREMENT_MANAGER` | Reviews procurement step in the workflow |
| `ACCOUNTS_OFFICER` | Financial verification, payment processing, CFC/Commitment numbers |
| `VICE_CHANCELLOR` | Final approval for capital items (> K3,000) |
| `SYSTEM_ADMIN` | Full system access — user management, delegate assignments, reports |

---

## Approval Workflows — Summary

| Purchase Type | Trigger | Approval Chain |
|---------------|---------|----------------|
| Standard Purchase | Default | HOS → Dean → Procurement → Accounts Verification → Accounts Processing |
| ICT Purchase | `procurement_type = ICT` | HOS → Dean → ICT Director → Procurement → Accounts Verification → Accounts Processing |
| Capital Item | `is_capital_item = true` | HOS → Dean → Procurement → Vice Chancellor → Accounts Processing |

---

## Security Notes

- All API endpoints require a valid session cookie — unauthenticated requests are rejected
- Each approval step requires OTP verification (bypassed in `development` mode)
- Uploaded documents are stored outside the web-accessible path and served only through authenticated PHP endpoints
- In production, use a dedicated MySQL user with only SELECT, INSERT, UPDATE, DELETE permissions — do not use `root`
- Enable HTTPS before going live

---

## Changelog

### v4.1.0 — October 2026
- GE form restructured into 7 numbered sections (Claim Routing, Particulars, Department Use, Claimant Declaration, Financial Coding, HOD Certification, Supporting Documents)
- GST percentage column added to line items — totals are GST-inclusive
- HOD Certification section added (HOD name, designation, signature, date, approval and send-to-accounts flags)
- Claimant Declaration section added (full name, signature, date)
- Financial Coding section (CFC/Commitment numbers) now visible to all roles but editable only by ACCOUNTS_OFFICER and SYSTEM_ADMIN
- Quotation Tracker page added
- Login page logo enlarged for better visibility
- Live hosting deployment support added to documentation

### v4.0.0 — October 2026
- Initial release

---

## Documentation Index

| File | Audience | Description |
|------|----------|-------------|
| `README.md` | Developers, IT administrators | This file — full system overview, architecture, and changelog |
| `SETUP_GUIDE.md` | IT administrators | Step-by-step installation, database setup, and troubleshooting |
| `USER_MANUAL.md` | All staff | How to use the system — submitting GEs, uploading documents, approvals |
| `STAFF_MANAGEMENT.md` | System admin | Creating and managing user accounts and delegate assignments |
| `DOCUMENTATION_INDEX.md` | All | Index of all documentation files with summaries and reading order |

---

*For technical support, contact the ICT Department.*  
*Document version 4.1.0 — October 2026*
