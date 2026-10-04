# PNGUOT GEMS — Quick Start Guide

**Steps to run the system right now**  
Papua New Guinea University of Technology

---

## Before You Start

Make sure XAMPP is open and both services are green:

| Service | Status needed |
|---------|--------------|
| Apache  | ✅ Running |
| MySQL   | ✅ Running |

If either is stopped, open **XAMPP Control Panel** and click **Start**.

---

## Step 1 — Open the System

Open your browser and go to:

```
http://localhost/pnguot_gems/
```

You should see the **PNGUOT GEMS Login** page.

---

## Step 2 — Log In as Administrator

| Field    | Value                    |
|----------|--------------------------|
| Email    | `admin@pnguot.ac.pg`     |
| Password | `StrongPassword123`      |

Click **Sign In**.

---

## Step 3 — Change the Admin Password

Do this immediately after your first login.

1. Click your name in the top-right corner
2. Select **👤 Profile**
3. Scroll down to **Change Password**
4. Enter `StrongPassword123` as the current password
5. Enter and confirm your new secure password
6. Click **Update Password**

---

## Step 4 — Add Staff Accounts

Every staff member who will use the system needs an account.

1. Click **👥 Users** in the left sidebar
2. Click **+ Add Staff**
3. Fill in:
   - First Name and Last Name
   - Staff ID (e.g. `PNGUOT002`)
   - Email prefix — type only `firstname.lastname`, the `@pnguot.ac.pg` is added automatically
   - Department
   - Roles — tick all that apply (see role guide below)
   - Password — click **🔀 Generate Password** for a secure one
4. Click **Add Staff Member**
5. Share the login details with the staff member:
   - Email: `firstname.lastname@pnguot.ac.pg`
   - Password: the one you set or generated

### Role Quick Guide

| Role | Give this to |
|------|-------------|
| Claimant | Any staff who submits expense claims |
| Head of School/Dept | Heads of Department or School |
| Dean | Faculty Deans |
| ICT Director | The ICT Director |
| Procurement Manager | The Procurement Manager |
| Accounts Officer | Finance and Accounts staff |
| Vice Chancellor | The Vice Chancellor |
| System Administrator | IT staff only |

> A staff member can hold more than one role — e.g. a Head of Department is usually both **Claimant** and **Head of School/Dept**.

---

## Step 5 — Assign Delegates ⚠️ Required

Delegates tell the system who to send approval tasks to at each workflow step.  
**Without this, GE forms will get stuck when submitted.**

1. Click **🔗 Delegates** in the left sidebar
2. Click **+ Assign Delegate**
3. Select the **Role**, then the **User**, then the **Scope**
4. Click **Assign**

Repeat for every role in this list:

| Role | Scope to select | Example |
|------|----------------|---------|
| Head of School/Dept | Department | Assign per department |
| Dean | Faculty | Assign per faculty code (e.g. FBE) |
| ICT Director | Global | One person covers all |
| Procurement Manager | Global | One person covers all |
| Accounts Officer | Global | One or more people |
| Vice Chancellor | Global | One person covers all |

---

## Step 6 — Test the Full Workflow

Run a quick test to confirm everything is connected before going live.

1. **Log in as a Claimant** (a staff member you just created)
2. Click **➕ New GE** and fill in a test form:
   - Payee: `Test Supplier`
   - Departmental Reference: `TEST-001`
   - Add one line item: any description, qty 1, price K100
   - Accounting line: any code, amount K100
3. Click **Continue to Supporting Documents**
4. Upload any 4 dummy PDF files for the required documents:
   - Supplier Quotation 1
   - Supplier Quotation 2
   - Supplier Quotation 3
   - Justification Letter
5. Click **Submit for Approval**
6. **Log in as the assigned HOS delegate** and check **✅ Pending Approvals**
7. The test GE should appear — click **Review** to see the full form

If the GE appears in Pending Approvals, the workflow routing is working correctly.

---

## Step 7 — Go Live

Once the test passes:

- Inform all staff of the system URL: `http://localhost/pnguot_gems/`
- Direct them to the **User Manual** (`USER_MANUAL.md`) for instructions on submitting GE forms
- Direct approvers to check **✅ Pending Approvals** regularly or when they receive an email/SMS notification

---

## Summary Checklist

```
□ XAMPP Apache and MySQL are running
□ Logged in as admin at http://localhost/pnguot_gems/
□ Admin password changed from default
□ Staff accounts created for all users
□ Login credentials shared with each staff member
□ Delegates assigned for all 6 roles
□ Test GE submitted and appeared in approver's task list
□ System announced to staff — ready to use
```

---

## Useful URLs

| Page | URL |
|------|-----|
| Login | `http://localhost/pnguot_gems/` |
| Dashboard | `http://localhost/pnguot_gems/pages/dashboard.html` |
| Users | `http://localhost/pnguot_gems/pages/admin-users.html` |
| Delegates | `http://localhost/pnguot_gems/pages/admin-delegates.html` |
| Reports | `http://localhost/pnguot_gems/pages/admin-reports.html` |
| phpMyAdmin | `http://localhost/phpmyadmin` |

---

## If Something Goes Wrong

| Problem | Fix |
|---------|-----|
| Login page doesn't load | Check Apache is running in XAMPP |
| "Database connection failed" | Check MySQL is running in XAMPP |
| GE stuck after submission | Assign delegates in **🔗 Delegates** |
| Staff can't log in | Check account is Active and email is correct in **👥 Users** |
| Can't upload documents | Check `uploads/documents/` folder exists in the project |

For more detail see:
- `SETUP_GUIDE.md` — full installation and troubleshooting
- `USER_MANUAL.md` — how to use the system (for all staff)
- `STAFF_MANAGEMENT.md` — how to add and manage staff accounts

---

*Papua New Guinea University of Technology — ICT Department*  
*Document version 1.0 — October 2026*
