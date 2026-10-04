# PNGUOT GEMS — Staff Account Management Guide

**For System Administrators**  
Papua New Guinea University of Technology  
Version 1.0

---

## Overview

Only the **System Administrator** can create and manage staff accounts. Staff cannot self-register — all accounts must be created by the admin and login credentials shared directly with each staff member.

---

## Accessing User Management

1. Log in as System Administrator
2. Click **👥 Users** in the left sidebar under **Administration**

The page shows:
- A summary bar with total, active, and inactive staff counts
- A searchable table of all staff accounts

---

## Adding a New Staff Member

1. Click **+ Add Staff** in the top-right corner
2. Fill in the form:

### Personal Details

| Field | Required | Notes |
|-------|----------|-------|
| First Name | ✅ | e.g. `Alita` |
| Last Name | ✅ | e.g. `Sari` |
| Phone | — | e.g. `+675 7123 4567` |

### Staff ID

| Field | Required | Notes |
|-------|----------|-------|
| Staff ID | ✅ | e.g. `PNGUOT002` — always uppercase, must be unique |

### University Email

Type only the **username part** before the `@` symbol. The domain `@pnguot.ac.pg` is fixed automatically.

| You type | Full email created |
|----------|--------------------|
| `alita.sari` | `alita.sari@pnguot.ac.pg` |
| `john.kila` | `john.kila@pnguot.ac.pg` |

> ⚠️ The system will reject any email that does not end in `@pnguot.ac.pg`.

The email prefix auto-fills from the first and last name as you type — you can edit it if needed.

### Department

Select the department the staff member belongs to from the dropdown.

### Roles

Select all roles that apply to this staff member. Each role is shown with a description:

| Role | Description | Who gets this |
|------|-------------|---------------|
| **Claimant** | Can create and submit General Expense forms | All staff who submit expense claims |
| **Head of School/Dept** | First-level departmental approval | Heads of Department or School |
| **Dean** | Faculty-level approval | Faculty Deans |
| **ICT Director** | Approves ICT purchases | The ICT Director |
| **Procurement Manager** | Reviews and approves procurement | The Procurement Manager |
| **Accounts Officer** | Financial verification and payment | Finance and Accounts staff |
| **Vice Chancellor** | Final approval for capital items | The Vice Chancellor |
| **System Administrator** | Full system access | IT staff managing the system |

> A staff member can have more than one role. For example, a Head of Department is typically both a **Claimant** and a **Head of School/Dept**.

### Password

Two options:

**Option A — Set a password manually:**
- Type a password of at least 8 characters
- Click the 👁 eye icon to show or hide what you typed

**Option B — Generate a password automatically:**
- Click **🔀 Generate Password**
- A secure 12-character password is created and shown in the field
- **Copy it immediately** — you will need to share it with the staff member

3. Click **Add Staff Member**

A success message appears showing the staff member's full email:
```
Alita Sari added successfully. Share login: alita.sari@pnguot.ac.pg
```

---

## Sharing Login Credentials with Staff

After creating the account, share these details with the staff member:

```
System: PNGUOT General Expense Monitoring System
URL:    http://[server-address]/pnguot_gems/
Email:  [their full email, e.g. alita.sari@pnguot.ac.pg]
Password: [the password you set or generated]
```

> Ask the staff member to change their password on first login via **Profile → Change Password**.

---

## Editing a Staff Account

1. Find the staff member in the table
2. Click **Edit** on their row
3. Update the fields as needed:
   - Change name, phone, department, or roles
   - To change the password, type a new one (leave blank to keep the current password)
   - To deactivate the account, change **Account Status** to **🚫 Inactive**
4. Click **Save Changes**

---

## Viewing a Staff Profile

Click **View** on any row to see a summary card showing the staff member's full details, roles, and join date — without opening the edit form.

---

## Deactivating a Staff Account

When a staff member leaves the university or no longer needs access:

1. Click **Edit** on their row
2. Change **Account Status** from **✅ Active** to **🚫 Inactive**
3. Click **Save Changes**

The staff member will no longer be able to log in. Their existing GE forms and history are preserved.

> Deactivation is preferred over deletion — it keeps the audit trail intact.

---

## Searching for Staff

Use the **search bar** in the topbar to find staff by:
- First or last name
- Staff ID (e.g. `PNGUOT002`)
- Email address (e.g. `alita.sari`)

Results update as you type.

---

## Staff Email Format

All PNGUOT staff emails follow this pattern:

```
firstname.lastname@pnguot.ac.pg
```

**Examples:**

| Staff Member | Email |
|-------------|-------|
| Alita Sari | `alita.sari@pnguot.ac.pg` |
| John Kila | `john.kila@pnguot.ac.pg` |
| Mary Tomana | `mary.tomana@pnguot.ac.pg` |
| James Aihi | `james.aihi@pnguot.ac.pg` |

If two staff members have the same name, add a number or middle initial:
- `john.kila2@pnguot.ac.pg`
- `j.b.kila@pnguot.ac.pg`

---

## After Adding Staff — Next Steps

Once staff accounts are created, complete these steps to make the approval workflow work:

### 1. Assign Delegates

Go to **🔗 Delegates** and assign the correct staff members to approval roles. Without delegates, GE forms will get stuck when submitted.

**Minimum delegates required:**

| Role | How many | Scope |
|------|---------|-------|
| Head of School/Dept | One per department | Per department |
| Dean | One per faculty | Per faculty |
| ICT Director | One | Global |
| Procurement Manager | One | Global |
| Accounts Officer | One or more | Global |
| Vice Chancellor | One | Global |

### 2. Notify Staff

Send each staff member their login credentials and direct them to:
- The **User Manual** (`USER_MANUAL.md`) for how to use the system
- Their first login at `http://[server]/pnguot_gems/`

### 3. Test a GE submission

Ask a staff member to:
1. Log in and create a draft GE form
2. Add line items and submit
3. Confirm the approval notification reaches the assigned HOS delegate

---

## Common Issues

**"Email or staff ID already exists"**  
A staff member with that email or staff ID is already in the system. Search for them — they may already have an account that was deactivated.

**"Email must use the university domain: @pnguot.ac.pg"**  
The email prefix you entered resulted in an invalid email. Check for typos and make sure you are only entering the part before `@`.

**Staff member cannot log in**  
- Check their account is **Active** (Edit → Account Status)
- Confirm the email is correct
- Reset their password by editing their account and setting a new one

**Approval workflow not routing to the right person**  
The delegate assignment may be missing or pointing to the wrong user. Go to **🔗 Delegates** and check the assignments for the relevant role and department.

---

*For technical support, contact the ICT Department.*  
*Document version 1.0 — October 2026*
