# DELEGATE ACCESS GUIDE — PNGUOT GEMS

**Papua New Guinea University of Technology — School of Business Studies**  
**General Expense Monitoring System**  
Version 1.0 — October 2026

---

## Who This Guide Is For

This guide is for everyone involved in the GE approval process:

- Head of School (HOD)
- Dean
- ICT Director
- Procurement Manager
- Accounts Officer
- Vice Chancellor
- System Administrator

---

## Part 1 — How to Access the System

GEMS runs on one central computer (the admin's machine running XAMPP). You do not install anything on your computer. You access the system through your web browser.

### What you need

- A computer connected to the campus LAN or Wi-Fi
- A web browser — Chrome, Firefox, or Edge (latest version)
- The system URL (provided by your IT/admin)
- Your login credentials — email and password (provided by the System Admin)

### The URL

Open your browser and go to:

```
http://<server-ip>/pnguot_gems/
```

For example:

```
http://192.168.1.10/pnguot_gems/
```

> The System Admin will give you the exact IP address or URL to use. If you are unsure, contact the ICT Department.

### Logging In

1. Enter your **email address** (e.g. `john.doe@pnguot.ac.pg`)
2. Enter your **password**
3. Click **Sign In**

You will be taken to your Dashboard automatically.

> If you cannot log in, contact the System Admin to confirm your account has been created and your role has been assigned.

---

## Part 2 — How the Approval Workflow Works

When a staff member submits a GE form, it travels through a chain of approvers. Each approver is notified automatically when it is their turn. You do not need to go looking for GEs — the system brings them to you.

### Approval chains by purchase type

**Standard Purchase**
```
Claimant → Head of School → Dean → Procurement Manager → Accounts Verification → Accounts Processing ✅
```

**ICT Purchase**
```
Claimant → Head of School → Dean → ICT Director → Procurement Manager → Accounts Verification → Accounts Processing ✅
```

**Capital Item (over K3,000)**
```
Claimant → Head of School → Dean → Procurement Manager → Vice Chancellor → Accounts Processing ✅
```

If any approver raises a **Query**, the GE is returned to the claimant with a comment. Once the claimant fixes and resubmits, the workflow resumes from where it was queried.

---

## Part 3 — What Each Delegate Does

---

### Head of School (HOD)

**Role:** First-level departmental approval. You certify that the expense is legitimate and within your department's budget.

**When you are notified:** Immediately after a claimant submits a GE from your department.

**Steps to approve:**

1. Log in and go to **Pending Approvals** in the left sidebar
2. You will see a table of GEs waiting for your action
3. Click **Review** on the GE you want to action
4. A panel opens showing:
   - GE number, payee, claimant, department, amount
   - Full line items (description, quantity, unit price, total)
   - All supporting documents — click **View** to open each one
5. Read through everything carefully
6. Click **📱 Send OTP to Phone** to receive your one-time password
7. Enter the 6-digit OTP code in the field provided
8. Add any **comments** if needed
9. Click **Approve**
10. A **HOD Certification modal** will appear — you must:
    - Select your **designation** from the dropdown
    - Draw your **signature** on the signature pad
    - Click **Confirm Approval**

The GE moves to the Dean automatically.

**If you have concerns:**

Click **Query** instead of Approve. Enter your query message (minimum 10 characters explaining what needs to be fixed). The GE is returned to the claimant with your message. You will be notified when they resubmit.

---

### Dean

**Role:** Faculty-level approval. You confirm the expense is appropriate at the faculty level.

**When you are notified:** After the HOD has approved.

**Steps to approve:**

1. Log in → **Pending Approvals**
2. Click **Review** on the GE
3. Review line items, amount, and supporting documents
4. Click **📱 Send OTP to Phone** → enter the OTP
5. Add comments if needed
6. Click **Approve**

The GE moves to the Procurement Manager (or ICT Director for ICT purchases).

**If you have concerns:** Click **Query** — same process as HOD above.

---

### ICT Director

**Role:** Reviews and approves ICT-specific purchases only. You confirm the purchase is technically justified and appropriate.

**When you are notified:** After the Dean approves, but only for GEs where `procurement_type = ICT`.

**Steps to approve:**

1. Log in → **Pending Approvals**
2. Click **Review**
3. Check that the ICT items are justified and correctly specified
4. Review the supplier quotations in the documents section
5. **📱 Send OTP** → enter OTP → **Approve**

The GE moves to the Procurement Manager.

**If you have concerns:** Click **Query**.

---

### Procurement Manager

**Role:** Reviews procurement compliance for all GE types. You verify that proper procurement procedures were followed — correct number of quotations, amounts are reasonable, and documentation is complete.

**When you are notified:** After the Dean (or ICT Director for ICT purchases) approves.

**Steps to approve:**

1. Log in → **Pending Approvals**
2. Click **Review**
3. Check:
   - At least 3 supplier quotations are attached
   - The selected supplier and amount are justified
   - A justification letter is attached if required
4. **📱 Send OTP** → enter OTP → **Approve**

The GE moves to Accounts Verification.

**If you have concerns:** Click **Query**.

---

### Accounts Officer

**Role:** Two-step financial verification and payment processing. You also fill in the Financial Coding fields (CFC and Commitment numbers).

**When you are notified:** After the Procurement Manager approves (Verification step), and again after Verification is complete (Processing step).

#### Step A — Accounts Verification

1. Log in → **Pending Approvals**
2. Click **Review** on the GE at `PENDING_ACCOUNTS_VERIFICATION`
3. Verify:
   - Financial details are correct
   - Amounts match the supporting documents
   - Budget coding lines are filled in
4. **📱 Send OTP** → enter OTP → **Approve**

#### Step B — Filling in Financial Coding

You are the only person (along with the System Admin) who can fill in the CFC Number and Commitment Number on the GE form. To do this:

1. Go to **All GEs** or open the GE directly
2. Scroll to the **Financial Coding** section
3. Enter the **C.F.C. Number** and **Commitment Number**
4. Click **💾 Save Financial Coding**

#### Step C — Accounts Processing

1. Log in → **Pending Approvals**
2. Click **Review** on the GE at `PENDING_ACCOUNTS_PROCESSING`
3. Confirm payment has been or will be made
4. Click **📤 Send to Accounts** to mark the GE as physically received by the accounts office
5. **📱 Send OTP** → enter OTP → **Approve**

The GE status changes to **COMPLETED ✅**.

---

### Vice Chancellor

**Role:** Final approval for capital items only (purchases over K3,000).

**When you are notified:** After the Procurement Manager approves a capital item GE.

**Steps to approve:**

1. Log in → **Pending Approvals**
2. Click **Review**
3. Review the full GE — line items, total amount, all documents
4. **📱 Send OTP** → enter OTP → **Approve**

The GE moves to Accounts Processing.

**If you have concerns:** Click **Query**.

---

## Part 4 — System Administrator Functions

As System Admin you have full access to everything in the system. Your responsibilities fall into two categories: **setup** and **ongoing management**.

### Setup (before staff can use the system)

**1. Create user accounts**

Go to **👥 Users → + New User** for each staff member. Fill in:
- First name, last name
- Email address (used to log in)
- Department
- Role(s) — assign all roles the person holds

| Staff Member | Role(s) to assign |
|---|---|
| Any staff submitting expenses | CLAIMANT |
| Head of School | HEAD_OF_SCHOOL + CLAIMANT |
| Dean | DEAN + CLAIMANT |
| ICT Director | ICT_DIRECTOR + CLAIMANT |
| Procurement Manager | PROCUREMENT_MANAGER + CLAIMANT |
| Accounts Officer | ACCOUNTS_OFFICER + CLAIMANT |
| Vice Chancellor | VICE_CHANCELLOR + CLAIMANT |
| IT staff managing the system | SYSTEM_ADMIN |

**2. Assign delegates**

Go to **🔗 Delegates → + Assign Delegate**. This tells the system which person holds each approval role.

| Role | Scope | Assign to |
|---|---|---|
| HEAD_OF_SCHOOL | Department: SBS | Head of School of Business Studies |
| DEAN | Faculty code (SBS faculty) | Dean responsible for SBS |
| ICT_DIRECTOR | Global | University ICT Director |
| PROCUREMENT_MANAGER | Global | University Procurement Manager |
| ACCOUNTS_OFFICER | Global | Finance / Accounts Officer |
| VICE_CHANCELLOR | Global | Vice Chancellor |

> A user must have the matching role on their account before they can be assigned as a delegate for that role.

> Without delegate assignments, submitted GEs will get stuck with a **Workflow Configuration Error**.

### Ongoing management

**All GEs** (`admin-all-ges.html`)
- View every GE in the system from every department and claimant
- Filter by status, department, date range
- Search by GE number, payee, or claimant name
- Cancel any GE if needed

**Approvals** (`admin-approvals.html`)
- See all pending approval tasks across all workflow steps
- Identify bottlenecks — GEs that have been sitting too long at a particular step

**Reports** (`admin-reports.html`)
- System-wide expenditure by department
- GE counts and values by status
- Expenditure over time by period
- Use for finance reconciliation and budget monitoring

**Notifications** (`admin-notifications.html`)
- View and manage all system notifications sent to all users

**Financial Coding**
- You share edit access to CFC Number and Commitment Number fields with the Accounts Officer
- All other roles can see these fields but cannot edit them

### What only the System Admin can do

| Function | Admin only |
|---|---|
| Create and edit user accounts | ✅ |
| Assign and remove delegate mappings | ✅ |
| View all GEs from all departments | ✅ |
| Cancel any GE regardless of owner | ✅ |
| View system-wide reports | ✅ |
| Access the Admin Panel | ✅ |

---

## Part 5 — Quick Reference

### What to do when you log in

| Your role | Where to go first |
|---|---|
| HOD, Dean, ICT Director, Procurement Manager, VC | Pending Approvals |
| Accounts Officer | Pending Approvals (check for Verification and Processing tasks) |
| System Admin | Admin Panel → check All GEs and Approvals for any stuck items |
| Claimant | Dashboard → My GE Forms |

### GE Status meanings

| Status | Means |
|---|---|
| DRAFT | Claimant is still filling in the form |
| READY_FOR_DOCUMENTS | Form validated, claimant uploading documents |
| SUBMITTED | Sent into the approval workflow |
| PENDING_HOS | Waiting for Head of School |
| PENDING_DEAN | Waiting for Dean |
| PENDING_ICT | Waiting for ICT Director |
| PENDING_PROCUREMENT | Waiting for Procurement Manager |
| PENDING_ACCOUNTS_VERIFICATION | Waiting for Accounts to verify |
| PENDING_ACCOUNTS_PROCESSING | Waiting for Accounts to process payment |
| PENDING_VC | Waiting for Vice Chancellor |
| QUERIED | Returned to claimant with a question |
| COMPLETED | Fully approved and payment confirmed ✅ |
| CANCELLED | Cancelled by claimant or admin |

### OTP notes

- OTP is a 6-digit code sent to your registered phone number
- It expires after a short time — use it immediately
- If you are on a **development/test** server, the OTP is shown on screen automatically (no SMS needed)
- Each approval step requires its own OTP — you cannot reuse a previous code

---

## Part 6 — Getting Help

| Problem | Who to contact |
|---|---|
| Cannot log in | System Admin — confirm your account and role exist |
| GE stuck in workflow | System Admin — check delegate assignments |
| Did not receive OTP | System Admin — check phone number on your account |
| Wrong amount or details on a GE | Claimant must cancel and resubmit, or approver raises a Query |
| Technical issues with the system | ICT Department |

---

*Papua New Guinea University of Technology — ICT Department*  
*Document version 1.0 — October 2026*
