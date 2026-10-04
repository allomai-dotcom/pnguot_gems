# PNGUOT GEMS — Setup & Run Guide

**How to install and run the General Expense Monitoring System on your computer**  
Papua New Guinea University of Technology  
Version 1.0

---

## Requirements

Before you start, make sure your computer has the following:

| Requirement | Version | Notes |
|-------------|---------|-------|
| **XAMPP** | 8.0 or higher | Includes Apache, MySQL, and PHP |
| **Web Browser** | Chrome, Firefox, or Edge (latest) | Avoid Internet Explorer |
| **Windows** | Windows 10 or 11 | Also works on macOS/Linux with XAMPP |

Download XAMPP from: **https://www.apachefriends.org**

---

## Step 1 — Install XAMPP

1. Download the XAMPP installer from https://www.apachefriends.org
2. Run the installer and follow the on-screen steps
3. When asked which components to install, make sure these are ticked:
   - ✅ Apache
   - ✅ MySQL
   - ✅ PHP
   - ✅ phpMyAdmin
4. Install to the default location: `C:\xampp\`
5. Click **Finish** when done

---

## Step 2 — Copy the Project Files

1. Find the project folder on your Desktop:
   ```
   pnguot_general_expense_monitoring_system
   ```

2. Copy the entire folder into XAMPP's web root:
   ```
   C:\xampp\htdocs\
   ```

3. Rename the copied folder to `pnguot_gems` so it becomes:
   ```
   C:\xampp\htdocs\pnguot_gems\
   ```

   Your final folder structure should look like this:
   ```
   C:\xampp\
   └── htdocs\
       └── pnguot_gems\
           ├── index.html
           ├── pages\
           ├── api\
           ├── assets\
           ├── config\
           ├── database\
           └── uploads\
   ```

---

## Step 3 — Start XAMPP

1. Open **XAMPP Control Panel** (search for it in your Start menu, or find it at `C:\xampp\xampp-control.exe`)

2. Click **Start** next to **Apache**
   - The row should turn green and show port `80` and `443`

3. Click **Start** next to **MySQL**
   - The row should turn green and show port `3306`

   ![XAMPP Control Panel with Apache and MySQL running]

   > If Apache fails to start, another program may be using port 80 (e.g. Skype, IIS). See the [Troubleshooting](#troubleshooting) section.

---

## Step 4 — Set Up the Database

### Option A — Automatic Setup (Recommended)

This is the easiest method. It runs everything automatically.

1. Open your browser and go to:
   ```
   http://localhost/pnguot_gems/database/setup.php?token=pnguot-setup-2026
   ```

2. Wait for the page to finish. You should see:
   ```
   ✅ Schema created.
   ✅ Seed data inserted.
   ✅ Setup complete!

   Admin credentials:
   Email:    admin@pnguot.ac.pg
   Password: StrongPassword123
   ```

3. **Important — delete the setup file immediately after:**
   - Navigate to `C:\xampp\htdocs\pnguot_gems\database\`
   - Delete `setup.php`
   - This prevents anyone else from re-running the setup

---

### Option B — Manual Setup via phpMyAdmin

Use this method if Option A doesn't work.

#### Step 4a — Open phpMyAdmin

Go to: `http://localhost/phpmyadmin`

#### Step 4b — Create the Database

1. Click **New** in the left sidebar
2. In the **Database name** field type: `pnguot_gems`
3. Set the collation to: `utf8mb4_unicode_ci`
4. Click **Create**

#### Step 4c — Import the Schema

1. Make sure `pnguot_gems` is selected in the left sidebar
2. Click the **Import** tab at the top
3. Click **Choose File**
4. Navigate to: `C:\xampp\htdocs\pnguot_gems\database\schema.sql`
5. Click **Go** (bottom of the page)
6. Wait for the success message: *"Import has been successfully finished"*

#### Step 4d — Import the Seed Data

1. Click the **Import** tab again
2. Click **Choose File**
3. Navigate to: `C:\xampp\htdocs\pnguot_gems\database\seed.sql`
4. Click **Go**
5. Wait for the success message

> **Note:** When using manual import, the admin password in `seed.sql` is a placeholder hash. You will need to reset the password — see [Resetting the Admin Password](#resetting-the-admin-password) below.

---

## Step 5 — Open the Application

1. Open your browser and go to:
   ```
   http://localhost/pnguot_gems/
   ```

2. You should see the **PNGUOT GEMS Login** page

3. Log in with the admin account:
   | Field | Value |
   |-------|-------|
   | Email | `admin@pnguot.ac.pg` |
   | Password | `StrongPassword123` |

4. You're in! 🎉

---

## Step 6 — First-Time Configuration

After logging in as admin, complete these steps before other users start using the system.

### 6.1 Change the Admin Password

1. Click your name in the top-right corner
2. Select **👤 Profile**
3. Scroll to **Change Password**
4. Enter the current password and set a new secure password
5. Click **Update Password**

### 6.2 Create User Accounts

1. Go to **👥 Users** in the sidebar
2. Click **+ New User** for each staff member who needs access
3. Assign the correct role(s) to each user (see role table below)
4. Share login credentials with each user

**Role assignment guide:**

| Role | Assign to |
|------|-----------|
| CLAIMANT | Any staff member who submits expense claims |
| HEAD_OF_SCHOOL | Heads of Department/School |
| DEAN | Faculty Deans |
| ICT_DIRECTOR | The ICT Director |
| PROCUREMENT_MANAGER | The Procurement Manager |
| ACCOUNTS_OFFICER | Finance/Accounts staff |
| VICE_CHANCELLOR | The Vice Chancellor |
| SYSTEM_ADMIN | IT staff managing the system |

> A user can have more than one role. For example, a Head of School is usually also a Claimant.

### 6.3 Assign Delegates

This is required for the approval workflow to function. Without delegates, GEs will get stuck in **Workflow Configuration Error**.

1. Go to **🔗 Delegates** in the sidebar
2. Click **+ Assign Delegate**
3. Assign a user to each of these roles at minimum:

| Role | Scope |
|------|-------|
| HEAD_OF_SCHOOL | Per department |
| DEAN | Per faculty |
| ICT_DIRECTOR | Global |
| PROCUREMENT_MANAGER | Global |
| ACCOUNTS_OFFICER | Global |
| VICE_CHANCELLOR | Global |

See the **User Manual** for detailed instructions on delegate scopes.

---

## Configuration File

If you need to change database credentials or other settings, edit this file:

```
C:\xampp\htdocs\pnguot_gems\config\config.php
```

Key settings:

```php
// Database connection
define('DB_HOST', 'localhost');   // Database server (keep as localhost for XAMPP)
define('DB_NAME', 'pnguot_gems'); // Database name
define('DB_USER', 'root');        // MySQL username (XAMPP default is root)
define('DB_PASS', '');            // MySQL password (XAMPP default is blank)

// Environment — change to 'production' on a live server
define('APP_ENV', 'development');

// OTP — in development mode OTP is bypassed automatically
// Change APP_ENV to 'production' to enforce real OTP verification
```

> **Security note:** In XAMPP the default MySQL user is `root` with no password. This is fine for local development but must be changed before deploying to a server accessible by others.

---

## Stopping the System

When you are done using the system:

1. Open the **XAMPP Control Panel**
2. Click **Stop** next to **Apache**
3. Click **Stop** next to **MySQL**

The system will no longer be accessible until you start them again.

---

## Starting the System Again

Each time you want to use the system:

1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
4. Open your browser and go to `http://localhost/pnguot_gems/`

> You can configure Apache and MySQL to start automatically with Windows — in XAMPP Control Panel, click the checkbox in the **Svc** column next to each service, then click **Install**.

---

## Resetting the Admin Password

If you imported the database manually (Option B) or forgot the admin password:

1. Open **phpMyAdmin**: `http://localhost/phpmyadmin`
2. Select the `pnguot_gems` database
3. Click the `users` table
4. Click the **SQL** tab
5. Run this query (replace `NewPassword123` with your chosen password):

```sql
UPDATE users
SET password_hash = '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE email = 'admin@pnguot.ac.pg';
```

> The hash above corresponds to the password `password`. After logging in, **immediately change it** via Profile → Change Password.

Alternatively, generate your own bcrypt hash using this PHP snippet in XAMPP's terminal:

```bash
C:\xampp\php\php.exe -r "echo password_hash('YourNewPassword', PASSWORD_BCRYPT, ['cost'=>12]);"
```

Paste the output hash into the SQL query above.

---

## Troubleshooting

### Apache won't start — Port 80 is in use

Another program is using port 80. Common causes: Skype, IIS, or another web server.

**Fix — change Apache to a different port:**
1. In XAMPP Control Panel, click **Config** next to Apache
2. Click **httpd.conf**
3. Find the line: `Listen 80`
4. Change it to: `Listen 8080`
5. Save the file and restart Apache
6. Access the app at: `http://localhost:8080/pnguot_gems/`

### MySQL won't start — Port 3306 is in use

**Fix:**
1. Press `Ctrl + Shift + Esc` to open Task Manager
2. Look for a **mysqld.exe** process and end it
3. Try starting MySQL again in XAMPP

### Page shows "Database connection failed"

Check these in order:
1. MySQL is running in XAMPP Control Panel (green status)
2. The database `pnguot_gems` exists in phpMyAdmin
3. Credentials in `config/config.php` match your MySQL setup (`DB_USER`, `DB_PASS`)

### Page shows "404 Not Found" or blank page

Check:
1. The folder is named exactly `pnguot_gems` (no spaces, no extra characters)
2. It is inside `C:\xampp\htdocs\` (not inside a subfolder of htdocs)
3. Apache is running

### Setup page shows a database error during import

This usually means the schema was already imported. Check phpMyAdmin — if the `pnguot_gems` database already has tables, the setup ran successfully. Go directly to `http://localhost/pnguot_gems/` to log in.

### Login fails with correct credentials

If you used the manual import method (Option B), the password hash in `seed.sql` is a placeholder and won't work. Follow the [Resetting the Admin Password](#resetting-the-admin-password) steps above.

### Uploaded documents are not displaying

Check:
1. The `uploads/documents/` folder exists at `C:\xampp\htdocs\pnguot_gems\uploads\documents\`
2. The folder is **writable** — right-click the folder → Properties → Security → ensure the user running Apache has write permission

### Session expires too quickly

The default session lifetime is 8 hours. To change it, edit `config/config.php`:

```php
define('SESSION_LIFETIME', 28800); // 8 hours — change to e.g. 43200 for 12 hours
```

---

## Deploying to a Live Server (Production)

If you want to move the system from your local computer to a server accessible over the network:

1. **Upload all project files** to your server's web root (e.g. `/var/www/html/pnguot_gems/`)

2. **Import the database** using phpMyAdmin or the MySQL command line on the server

3. **Update `config/config.php`:**
   ```php
   define('APP_ENV',  'production');  // enables OTP enforcement
   define('DB_HOST',  'localhost');   // or your DB server hostname
   define('DB_USER',  'your_db_user');
   define('DB_PASS',  'your_db_password');
   define('CORS_ALLOWED_ORIGIN', 'https://your-domain.edu.pg');
   ```

4. **Secure the database:**
   - Create a dedicated MySQL user with only SELECT, INSERT, UPDATE, DELETE permissions on `pnguot_gems`
   - Do not use the `root` account in production

5. **Protect sensitive folders** — confirm the `.htaccess` file in `uploads/documents/` is active (denies direct file access)

6. **Delete the setup file:**
   ```
   /var/www/html/pnguot_gems/database/setup.php
   ```

7. **Enable HTTPS** on your server — session cookies and OTP codes should always travel over an encrypted connection

---

## File & Folder Reference

```
pnguot_gems/
│
├── index.html                  ← Login page (entry point)
│
├── pages/                      ← All application pages
│   ├── dashboard.html
│   ├── my-ges.html
│   ├── ge-form.html
│   ├── ge-documents.html
│   ├── approvals.html
│   ├── admin-users.html
│   ├── admin-delegates.html
│   ├── admin-reports.html
│   ├── notifications.html
│   ├── quotation-tracker.html
│   └── profile.html
│
├── assets/
│   ├── css/main.css            ← All styles
│   └── js/
│       ├── api.js              ← API client (all HTTP calls)
│       ├── app.js              ← Core: auth, toast, modal, sidebar
│       ├── ge-form.js          ← GE form logic
│       └── workflow.js         ← Approval workflow logic
│
├── api/                        ← PHP backend endpoints
│   ├── auth/                   ← login, logout, me, change_password
│   ├── ge/                     ← create, list, get, update, submit, cancel…
│   ├── documents/              ← upload, view, delete
│   ├── workflow/               ← my_tasks, approve, query, otp
│   └── admin/                  ← users, delegates, reports, notifications
│
├── config/
│   ├── config.php              ← ⚙️ Application settings (edit this)
│   ├── database.php            ← Database connection
│   └── helpers.php             ← Shared functions
│
├── database/
│   ├── schema.sql              ← Full database structure (import this first)
│   ├── seed.sql                ← Initial data (import this second)
│   ├── migrations/
│   │   └── 001_budget_coding.sql  ← Run only when upgrading an existing DB
│   └── setup.php               ← ⚠️ One-time setup script — delete after use
│
├── uploads/
│   └── documents/              ← Uploaded files stored here (not web-accessible)
│       └── .htaccess           ← Blocks direct browser access to files
│
├── README.md                   ← Technical overview
├── SETUP_GUIDE.md              ← This file
└── USER_MANUAL.md              ← How to use the system
```

---

## Quick Reference Card

| Task | URL |
|------|-----|
| Open the app | `http://localhost/pnguot_gems/` |
| phpMyAdmin | `http://localhost/phpmyadmin` |
| Run setup (first time only) | `http://localhost/pnguot_gems/database/setup.php?token=pnguot-setup-2026` |
| XAMPP Control Panel | `C:\xampp\xampp-control.exe` |
| Config file | `C:\xampp\htdocs\pnguot_gems\config\config.php` |

| Credential | Value |
|------------|-------|
| Admin email | `admin@pnguot.ac.pg` |
| Admin password | `StrongPassword123` |
| MySQL host | `localhost` |
| MySQL port | `3306` |
| Database name | `pnguot_gems` |

---

*For technical support, contact the ICT Department.*  
*Document version 1.0 — October 2026*
