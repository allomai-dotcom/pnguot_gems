# PNGUOT GEMS — User Manual

**General Expense Monitoring System**  
Papua New Guinea University of Technology  
Version 1.0

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Getting Started](#2-getting-started)
3. [Dashboard Overview](#3-dashboard-overview)
4. [Creating a General Expense (GE) Form](#4-creating-a-general-expense-ge-form)
5. [Uploading Supporting Documents](#5-uploading-supporting-documents)
6. [Submitting for Approval](#6-submitting-for-approval)
7. [Tracking Your GE Forms](#7-tracking-your-ge-forms)
8. [Approval Workflow (For Approvers)](#8-approval-workflow-for-approvers)
9. [Raising and Responding to Queries](#9-raising-and-responding-to-queries)
10. [Notifications](#10-notifications)
11. [Quotation Tracker](#11-quotation-tracker)
12. [Administration (System Admin Only)](#12-administration-system-admin-only)
13. [Reports](#13-reports)
14. [Profile & Password](#14-profile--password)
15. [GE Status Reference](#15-ge-status-reference)
16. [Role Reference](#16-role-reference)
17. [Frequently Asked Questions](#17-frequently-asked-questions)

---

## 1. Introduction

PNGUOT GEMS (General Expense Monitoring System) is the University's electronic system for submitting, tracking, and approving General Expense (GE) forms. It replaces paper-based GE forms with a fully digital workflow that includes:

- Electronic GE form with line items and cost allocation
- Automatic routing to the correct approvers based on purchase type
- Supporting document upload and verification
- OTP-based digital approval authorization
- Full audit trail on every GE

---

## 2. Getting Started

### 2.1 Accessing the System

Open your web browser and go to:
```
http://localhost/pnguot_gems/
```
*(Your IT department will provide the correct URL if hosted on a server.)*

### 2.2 Logging In

1. Enter your **University email address** (e.g. `jane.doe@pnguot.ac.pg`)
2. Enter your **password**
3. Click **Sign In**

![Login screen showing email and password fields]

> If you cannot log in, contact your System Administrator to confirm your account is active and your email is correct.

### 2.3 Logging Out

Click your name in the top-right corner, then select **Sign Out** from the dropdown. You can also click the **•••** menu at the bottom of the sidebar and select Sign Out.

---

## 3. Dashboard Overview

After logging in you land on the **Overview** (Dashboard) page.

```
┌─────────────────────────────────────────────────────────┐
│  SIDEBAR                │  MAIN CONTENT                 │
│  ─────────────────────  │  ─────────────────────────── │
│  👤 Your Name           │  Stat cards (totals)          │
│  Department             │                               │
│  ─────────────          │  Recent GE Forms table        │
│  📊 Overview  ← active  │                               │
│  🔔 Notifications       │  By Department table          │
│  ─────────────          │                               │
│  Expenses               │                               │
│  ➕ New GE              │                               │
│  📋 My GE Forms         │                               │
│  📎 Quotation Tracker   │                               │
│  ─────────────          │                               │
│  Approvals (if role)    │                               │
│  Administration (admin) │                               │
└─────────────────────────────────────────────────────────┘
```

### Stat Cards

| Card | What it shows |
|------|--------------|
| Total GE Forms | All GEs ever created in the system |
| Pending Approval | GEs currently going through the workflow |
| Completed | GEs fully approved and processed |
| Drafts | GEs saved but not yet validated |
| Total Value | Combined Kina value of all GEs |
| My Pending Tasks | Approval tasks waiting for your action (approvers only) |

---

## 4. Creating a General Expense (GE) Form

### 4.1 Start a New GE

Click **➕ New GE** in the sidebar, or the **+ New GE** button in the topbar.

### 4.2 Section 1 — Claim Routing

Fill in the routing information:

| Field | Required | Description |
|-------|----------|-------------|
| Dr. To / Payee Name | ✅ | The supplier or person being paid (e.g. ABC Computer Supplies Ltd) |
| Department | — | Auto-filled from your profile. Read-only. |
| Departmental Reference | ✅ | Your department's internal reference code (e.g. ICT-EQUIPMENT-2026) |
| Claimant Reference | — | Your own reference number (e.g. TEST-001) |
| Procurement Type | ✅ | **Standard Purchase** or **ICT Purchase** |
| Capital Item? | ✅ | Select **Yes** if this is an AC unit, laptop over K3,000, motor vehicle, or major repair |
| Description / Purpose | — | Brief explanation of why this expense is needed |

> **Capital Item threshold:** Any single item valued over **K3,000** is considered a capital item and requires Vice Chancellor approval.

### 4.3 Section 2 — Particulars of Expenditure

This is your line items table — one row per item being purchased.

**To add a line item:**
1. The first row is already present. Fill in:
   - **Description** — what you are purchasing
   - **Qty** — quantity
   - **Unit Rate (K)** — price per unit in Kina
   - **GST%** — GST percentage (enter `10` for 10%, or `0` if GST-exempt)
2. The **Total (K)** column calculates automatically including GST
3. Click **+ Add Line Item** to add more rows
4. Click **✕** on any row to remove it

**Example:**

| Description | Qty | Unit Rate (K) | GST% | Total (K) |
|-------------|-----|--------------|------|-----------|
| Laptop Computer | 2 | 4,500.00 | 10 | 9,900.00 |
| Laptop Carrying Case | 2 | 250.00 | 10 | 550.00 |

The **GE Total** at the bottom right updates as you type.

### 4.4 Section 3 — Department Use (Cost Allocation)

Allocate the expense across account codes. The total of all accounting lines **must equal the GE Total**.

| Field | Description |
|-------|-------------|
| Account Code | Your department's account code |
| Account Name | Description of the account |
| Amount (K) | Amount allocated to this account |

A balance indicator shows whether your accounting total matches the GE total:
- ✅ Balanced — amounts match, you can proceed
- ⚠️ Difference: K X.XX — amounts do not match, adjust before continuing

### 4.5 Section 4 — Claimant Declaration

1. Enter your **Full Name** (auto-filled from your profile)
2. Sign in the **signature box** using your mouse or touchscreen
3. Click **🗑 Clear Signature** if you need to redo it
4. The **Date** is auto-filled to today — change it if needed

> By signing, you certify the expenses are correct and were incurred for official University purposes.

### 4.6 Section 5 — Financial Coding

| Field | Who can edit |
|-------|-------------|
| CFC Number | Accounts Officers and System Admin only |
| Commitment Number | Accounts Officers and System Admin only |

These fields are visible to all users but can only be saved by Finance staff. Claimants may leave them blank.

### 4.7 Section 6 — HOD Certification

This section records the Head of Department's physical certification of the paper form before it is submitted digitally.

| Field | Description |
|-------|-------------|
| HOD Name | Full name of the Head of Department |
| Designation | Select the HOD's role from the dropdown |
| HOD Signature | Capture the HOD's signature digitally |
| Date | Date the HOD certified the form |
| Approved by HOD | Tick when the HOD has approved |
| Sent to Accounts | Tick when the physical form has been forwarded to Accounts |

### 4.8 Section 7 — Supporting Documents

Upload your required and optional supporting documents directly on the form. See [Section 5](#5-uploading-supporting-documents) for full details.

### 4.9 Saving a Draft

Click **💾 Save Draft** at any time to save your progress. Drafts are saved automatically every 3 seconds while you are typing.

- You can close the browser and return later — your draft will be waiting
- A GE Number (e.g. `GE-2026-000001`) is assigned when you first save

### 4.10 Validating and Continuing

When all line items, accounting allocation, and claimant declaration are complete:

1. Click **Continue to Supporting Documents →**
2. The system validates:
   - Payee name is filled
   - Departmental reference is filled
   - At least one line item exists with a price > 0
   - Accounting total equals GE total
3. If validation passes, the GE moves to **Ready for Documents** status
4. If validation fails, a list of errors is shown — fix them and try again

---

## 5. Uploading Supporting Documents

### 5.1 Required Documents

Before you can submit a GE for approval, you **must** upload all four required documents:

| Document | Description |
|----------|-------------|
| Supplier Quotation 1 | First supplier quote |
| Supplier Quotation 2 | Second supplier quote |
| Supplier Quotation 3 | Third supplier quote |
| Justification Letter | Letter explaining why this purchase is needed |

> Three quotations are required by University procurement policy for competitive sourcing.

### 5.2 Optional Documents

These can be uploaded at any stage:

| Document | When to upload |
|----------|---------------|
| Purchase Order | After PO is raised |
| Remittance Advice | After payment is made |
| Invoice | When received from supplier |
| Delivery Docket | When goods are delivered |

### 5.3 How to Upload a Document

1. Click **Upload** next to the document type
2. In the upload dialog:
   - **Drag and drop** your file onto the upload zone, or
   - Click the zone to **browse** for a file
3. Supported formats: **PDF, JPEG, PNG** — maximum **10 MB** per file
4. Click **Upload** to confirm
5. The document status changes to ✅ Uploaded

### 5.4 Viewing and Deleting Documents

- Click **View** to open the document in a new browser tab
- Click **⬇** to download the document
- Click **🗑** to remove the document (only available before submission)

> Documents are stored securely and are not directly accessible via web URL — they are only viewable through authenticated links.

---

## 6. Submitting for Approval

Once all four required documents are uploaded:

1. A green confirmation banner appears: *"All required documents uploaded. You can now submit."*
2. Click **Submit for Approval →**
3. Confirm in the dialog that appears
4. The system automatically:
   - Determines the correct approval workflow based on purchase type and capital item flag
   - Assigns the first approver
   - Sends a notification to that approver
   - Updates the GE status to show which approval step it is at

### Workflow Selection

| Your GE type | Workflow used | Approval steps |
|-------------|--------------|----------------|
| Standard purchase, non-capital | Standard Purchase | HOS → Dean → Procurement → Accounts Verification → Accounts Processing |
| ICT purchase (laptops, computers, etc.), non-capital | ICT Purchase | HOS → Dean → ICT Director → Procurement → Accounts Verification → Accounts Processing |
| Any capital item (value > K3,000) | Capital Item | HOS → Dean → Procurement → Vice Chancellor → Accounts Payment |

---

## 7. Tracking Your GE Forms

### 7.1 My GE Forms Page

Click **📋 My GE Forms** in the sidebar to see all your submitted and draft GEs.

**Filtering:**
- Use the **Status** dropdown to filter by a specific status
- Use the **Search bar** to search by GE number, payee name, or reference

**Actions available per GE:**

| Status | Actions |
|--------|---------|
| Draft / Ready for Documents / Queried | **Edit** — opens the GE form for editing |
| Ready for Documents | **Documents** — goes to document upload page |
| All other statuses | **View** — read-only view of the GE |

### 7.2 Understanding GE Statuses

See the full [Status Reference](#15-ge-status-reference) at the end of this manual.

---

## 8. Approval Workflow (For Approvers)

If you are assigned an approval role (HOS, Dean, ICT Director, Procurement Manager, Accounts Officer, or Vice Chancellor), you will receive notifications when GEs require your action.

### 8.1 Viewing Pending Tasks

Click **✅ Pending Approvals** in the sidebar. A table lists all GEs waiting for your review, showing the GE number, payee, amount, and which step you are reviewing.

### 8.2 Reviewing a GE

Click **Review** on any task to open the full GE details, including:
- Header information (payee, department, claimant, references)
- All line items with amounts
- Supporting documents (with View and Download links)
- OTP authorization section (if required for your step)
- Comments field

### 8.3 OTP Authorization

Most approval steps require a one-time password (OTP) for security:

1. Click **📱 Send OTP to Phone** — a 6-digit code is sent to your registered phone number
2. The OTP is valid for **5 minutes**
3. Enter the code in the **OTP Code** field
4. Proceed to approve or reject

> In **development mode**, the OTP is shown on screen and auto-filled — this is for testing only and is disabled in production.

If you need a new code: wait 60 seconds, then click **Send OTP to Phone** again.

### 8.4 Approving a GE

1. Review all details and documents
2. Add optional **comments** in the comments field
3. Enter your OTP if required
4. Click **✅ Approve**
5. Confirm in the dialog

The GE advances to the next step automatically. The next approver is notified.

### 8.5 Rejecting a GE

1. Enter your **rejection reason** in the comments field (required)
2. Click **✕ Reject**
3. Confirm in the dialog

The GE is **cancelled** and the claimant is notified of the reason.

---

## 9. Raising and Responding to Queries

### 9.1 Raising a Query (Approvers)

If you need clarification before approving:

1. Open the GE review modal
2. Click **❓ Raise Query**
3. Type your query message (minimum 10 characters)
4. Click OK

The GE status changes to **Queried** and the claimant is notified.

### 9.2 Responding to a Query (Claimants)

When your GE is queried:

1. You receive a notification with the query message
2. Go to **📋 My GE Forms** and open the queried GE
3. You can edit the GE form and re-upload documents if needed
4. Enter your response in the query response box
5. Click **Submit Response**

The workflow resumes from the step that raised the query. The approver is notified of your response.

---

## 10. Notifications

Click **🔔 Notifications** in the sidebar to see all your notifications.

### Notification Types

| Type | Meaning |
|------|---------|
| APPROVAL REQUIRED | A GE has been assigned to you for approval |
| GE QUERIED | An approver has raised a query on your GE |
| QUERY RESPONDED | A claimant has responded to your query |
| GE COMPLETED | Your GE has been fully approved |
| GE REJECTED | Your GE was rejected — see the reason in the notification |

### Managing Notifications

- Click any notification to mark it as read
- Click **✓ Mark All Read** in the topbar to clear all unread notifications
- The red badge on the bell icon shows the count of unread notifications

---

## 11. Quotation Tracker

Click **📎 Quotation Tracker** in the sidebar to see all GEs that have quotation documents attached.

The tracker shows a table with one row per GE and three columns for Quotation 1, 2, and 3. Each uploaded quotation shows:
- The quotation reference number
- The original filename
- A **View** link to open it

Use the **filter bar** at the top to search by GE number or payee name.

This page is useful for Procurement staff to quickly verify that three quotes are on file for each purchase.

---

## 12. Administration (System Admin Only)

The Administration section is only visible to users with the **System Administrator** role.

### 12.1 User Management

Go to **👥 Users** in the sidebar.

**Creating a new user:**
1. Click **+ New User**
2. Fill in Staff ID, First Name, Last Name, Email, Password, Phone, and Department
3. Select one or more **Roles** for the user (tick all that apply)
4. Click **Save User**

**Editing a user:**
1. Click **Edit** next to any user in the table
2. Modify the fields as needed
3. Leave the password blank to keep the existing password
4. Click **Save User**

**Deactivating a user:**
1. Click **Edit** on the user
2. Change **Active** to **No**
3. Save — the user can no longer log in

### 12.2 Delegate Assignments

Go to **🔗 Delegates** in the sidebar.

Delegates control who receives approval tasks at each workflow step. The system resolves delegates in this priority order:
1. Explicit department-scoped assignment
2. Faculty-scoped assignment
3. Global/central role assignment
4. Any active user with the role

> **Important:** If no delegate is found for a workflow step, the GE will move to **Workflow Configuration Error** status. Ensure every role used in workflows has at least one delegate assigned.

**Assigning a delegate:**
1. Click **+ Assign Delegate**
2. Select the **Role** (e.g. Head of School)
3. Select the **User** who will fill that role
4. Select the **Scope**:
   - **Global** — applies to all departments (for central roles like VC, ICT Director)
   - **Faculty** — enter the faculty code (e.g. FBE)
   - **Department** — select a specific department
5. Click **Assign**

**Removing a delegate:**
Click **Remove** next to any assignment to deactivate it.

---

## 13. Reports

Go to **📈 Reports** in the sidebar (System Admin and Accounts Officers).

The reports page shows:

| Report | Description |
|--------|-------------|
| By Status | Count and total value of GEs grouped by status |
| Pending by Role | Number of pending approval tasks per approver role |
| Top Departments | Departments with the highest completed GE expenditure |
| Monthly Trend | GE count and value submitted per month over the last 12 months |

Click **🔄 Refresh** to reload with the latest data.

---

## 14. Profile & Password

Click your name in the topbar or sidebar and select **👤 Profile**.

### Viewing Your Profile

Your profile page shows:
- Full name and staff ID
- Email address
- Department
- Phone number
- Assigned roles

### Changing Your Password

1. Enter your **Current Password**
2. Enter your **New Password** (minimum 8 characters)
3. Enter the new password again in **Confirm New Password**
4. Click **Update Password**

> Choose a strong password that includes uppercase letters, numbers, and symbols.

---

## 15. GE Status Reference

| Status | Meaning | Who can act |
|--------|---------|-------------|
| **Draft** | Form saved but not yet validated | Claimant (edit/delete) |
| **Ready for Documents** | Form validated — upload documents | Claimant (upload docs) |
| **Submitted** | Documents complete — workflow started | System (routing) |
| **Pending HOS** | Waiting for Head of School approval | Head of School |
| **Pending Dean** | Waiting for Dean approval | Dean |
| **Pending ICT** | Waiting for ICT Director approval | ICT Director |
| **Pending Procurement** | Waiting for Procurement Manager review | Procurement Manager |
| **Pending Accounts Verification** | Waiting for financial verification | Accounts Officer |
| **Pending VC** | Waiting for Vice Chancellor approval | Vice Chancellor |
| **Pending Payment** | Waiting for payment processing | Accounts Officer |
| **Pending Accounts Processing** | Final accounts processing step | Accounts Officer |
| **Queried** | Approver has raised a query — claimant must respond | Claimant (respond) |
| **Completed** | Fully approved and processed | View only |
| **Cancelled** | Cancelled by claimant or rejected by approver | View only |
| **Workflow Config Error** | No delegate found for a workflow step | System Admin (fix delegates) |

---

## 16. Role Reference

| Role | What they can do |
|------|-----------------|
| **Claimant** | Create, edit, and submit GE forms; upload documents; respond to queries |
| **Head of School** | First-level departmental approval |
| **Dean** | Faculty-level approval |
| **ICT Director** | ICT purchase verification |
| **Procurement Manager** | Procurement review and approval |
| **Accounts Officer** | Financial verification, payment processing, and finance coding |
| **Vice Chancellor** | Final approval for capital item purchases |
| **System Administrator** | Full system access — user management, delegate assignments, reports |

> A user can hold multiple roles. For example, a Head of School can also be a Claimant.

---

## 17. Frequently Asked Questions

**Q: I can't log in. What should I do?**  
A: Check your email address and password. If the problem persists, contact your System Administrator to confirm your account is active.

**Q: My GE total and accounting total don't match. What's wrong?**  
A: The sum of all amounts in Section 3 (Department Use) must exactly equal the GE Total shown in Section 2. Adjust the amounts until the balance indicator shows ✅ Balanced.

**Q: I submitted my GE but forgot to upload a document. Can I add it?**  
A: Once a GE is submitted and the workflow has started, documents cannot be changed unless an approver raises a query. Contact the approver and ask them to raise a query so you can upload the missing document.

**Q: The approver rejected my GE. Can I resubmit it?**  
A: A rejected GE is cancelled and cannot be resubmitted. You will need to create a new GE form.

**Q: My GE shows "Workflow Configuration Error". What does that mean?**  
A: The system could not find an approver for one of the workflow steps. Contact your System Administrator to assign the correct delegate for that role.

**Q: I'm an approver but I'm not receiving any tasks. Why?**  
A: Your role may not be assigned as a delegate for the relevant department or faculty. Contact your System Administrator to check the delegate assignments.

**Q: The OTP SMS didn't arrive. What should I do?**  
A: Wait 60 seconds and click **Send OTP to Phone** again. If you still don't receive it, check with your System Administrator that your phone number is registered correctly on your profile.

**Q: Can I cancel a GE after submitting it?**  
A: Yes, but only before the workflow progresses past Submitted status. Go to the GE form and click **Cancel GE**. You will be asked to provide a reason.

**Q: How do I know which workflow my GE will go through?**  
A: The workflow is selected automatically at submission based on two settings you choose on the form:
- If **Capital Item = Yes** → Capital Item workflow (requires Vice Chancellor)
- If **Procurement Type = ICT** and Capital Item = No → ICT Purchase workflow
- Otherwise → Standard Purchase workflow

---

*For technical support, contact the ICT Department.*  
*For procurement policy questions, contact the Procurement Office.*  
*Document version 1.0 — October 2026*
