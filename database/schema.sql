-- ============================================================
-- PNGUOT General Expense Monitoring System
-- Database Schema
-- MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS pnguot_gems CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pnguot_gems;

-- ============================================================
-- 1. DEPARTMENTS
-- ============================================================
CREATE TABLE departments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(20)  NOT NULL UNIQUE,
    name            VARCHAR(150) NOT NULL,
    faculty_code    VARCHAR(20)  NULL,          -- parent faculty code (NULL = top-level)
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. ROLES
-- ============================================================
CREATE TABLE roles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(50)  NOT NULL UNIQUE,   -- CLAIMANT, HEAD_OF_SCHOOL, DEAN, etc.
    label       VARCHAR(100) NOT NULL,
    description TEXT         NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 3. USERS
-- ============================================================
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id        VARCHAR(20)  NOT NULL UNIQUE,
    first_name      VARCHAR(80)  NOT NULL,
    last_name       VARCHAR(80)  NOT NULL,
    email           VARCHAR(150) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    phone           VARCHAR(20)  NULL,
    department_id   INT UNSIGNED NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 4. USER ROLES  (many-to-many)
-- ============================================================
CREATE TABLE user_roles (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    role_id     INT UNSIGNED NOT NULL,
    assigned_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT UNSIGNED NULL,
    UNIQUE KEY uq_user_role (user_id, role_id),
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id)     REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 5. GE NUMBER SEQUENCE  (concurrency-safe counter)
-- ============================================================
CREATE TABLE ge_number_sequence (
    year        YEAR         NOT NULL,
    next_val    INT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (year)
) ENGINE=InnoDB;

-- ============================================================
-- 6. GENERAL EXPENSES  (master header)
-- ============================================================
CREATE TABLE general_expenses (
    id                      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ge_number               VARCHAR(20)  NULL UNIQUE,           -- GE-YYYY-NNNNNN
    status                  ENUM(
                                'DRAFT',
                                'READY_FOR_DOCUMENTS',
                                'SUBMITTED',
                                'PENDING_HOS',
                                'PENDING_DEAN',
                                'PENDING_ICT',
                                'PENDING_PROCUREMENT',
                                'PENDING_ACCOUNTS_VERIFICATION',
                                'PENDING_VC',
                                'PENDING_PAYMENT',
                                'PENDING_ACCOUNTS_PROCESSING',
                                'QUERIED',
                                'COMPLETED',
                                'CANCELLED',
                                'WORKFLOW_CONFIGURATION_ERROR'
                            ) NOT NULL DEFAULT 'DRAFT',
    claimant_id             INT UNSIGNED NOT NULL,
    department_id           INT UNSIGNED NOT NULL,
    payee_name              VARCHAR(200) NULL,
    departmental_reference  VARCHAR(100) NULL,
    claimant_reference      VARCHAR(100) NULL,
    description             TEXT         NULL,
    procurement_type        ENUM('STANDARD','ICT') NOT NULL DEFAULT 'STANDARD',
    is_capital_item         TINYINT(1)   NOT NULL DEFAULT 0,
    total_amount            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    cfc_number              VARCHAR(50)  NULL,          -- finance only
    commitment_number       VARCHAR(50)  NULL,          -- finance only
    version                 INT UNSIGNED NOT NULL DEFAULT 1,
    submitted_at            DATETIME     NULL,
    completed_at            DATETIME     NULL,
    cancelled_at            DATETIME     NULL,
    cancel_reason           TEXT         NULL,
    created_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (claimant_id)   REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

-- ============================================================
-- 7. GE LINE ITEMS  (particulars)
-- ============================================================
CREATE TABLE ge_line_items (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ge_id           INT UNSIGNED NOT NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    description     VARCHAR(300) NOT NULL,
    quantity        DECIMAL(10,3) NOT NULL DEFAULT 1,
    unit_price      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    total_price     DECIMAL(12,2) GENERATED ALWAYS AS (quantity * unit_price) STORED,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ge_id) REFERENCES general_expenses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 8. GE ACCOUNTING LINES  (cost allocation)
-- ============================================================
CREATE TABLE ge_accounting_lines (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ge_id           INT UNSIGNED NOT NULL,
    sort_order      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    account_code    VARCHAR(50)  NOT NULL,
    account_name    VARCHAR(200) NULL,
    amount          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    notes           TEXT         NULL,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ge_id) REFERENCES general_expenses(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 9. GE DOCUMENTS
-- ============================================================
CREATE TABLE ge_documents (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ge_id           INT UNSIGNED NOT NULL,
    document_type   ENUM(
                        'QUOTATION_1',
                        'QUOTATION_2',
                        'QUOTATION_3',
                        'JUSTIFICATION_LETTER',
                        'PURCHASE_ORDER',
                        'REMITTANCE_ADVICE',
                        'INVOICE',
                        'DELIVERY'
                    ) NOT NULL,
    label           VARCHAR(150) NOT NULL,
    original_name   VARCHAR(255) NOT NULL,
    stored_name     VARCHAR(255) NOT NULL UNIQUE,  -- UUID-based filename on disk
    mime_type       VARCHAR(100) NULL,
    file_size       INT UNSIGNED NULL,
    uploaded_by     INT UNSIGNED NOT NULL,
    uploaded_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at      DATETIME     NULL,
    FOREIGN KEY (ge_id)       REFERENCES general_expenses(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- 10. WORKFLOW DEFINITIONS
-- ============================================================
CREATE TABLE workflow_definitions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(50)  NOT NULL UNIQUE,  -- STANDARD_PURCHASE, ICT_PURCHASE, CAPITAL_ITEM
    label       VARCHAR(150) NOT NULL,
    description TEXT         NULL,
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 11. WORKFLOW STEPS
-- ============================================================
CREATE TABLE workflow_steps (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workflow_id         INT UNSIGNED NOT NULL,
    step_order          SMALLINT UNSIGNED NOT NULL,
    step_code           VARCHAR(50)  NOT NULL,   -- HEAD_OF_SCHOOL, DEAN, etc.
    label               VARCHAR(150) NOT NULL,
    role_required       VARCHAR(50)  NOT NULL,   -- matches roles.code
    requires_otp        TINYINT(1)   NOT NULL DEFAULT 1,
    ge_status_on_reach  VARCHAR(50)  NOT NULL,   -- status set when this step becomes active
    FOREIGN KEY (workflow_id) REFERENCES workflow_definitions(id) ON DELETE CASCADE,
    UNIQUE KEY uq_wf_step (workflow_id, step_order)
) ENGINE=InnoDB;

-- ============================================================
-- 12. WORKFLOW INSTANCES
-- ============================================================
CREATE TABLE workflow_instances (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ge_id               INT UNSIGNED NOT NULL UNIQUE,
    workflow_id         INT UNSIGNED NOT NULL,
    current_step_id     INT UNSIGNED NULL,
    status              ENUM('ACTIVE','SUSPENDED','COMPLETED','CANCELLED') NOT NULL DEFAULT 'ACTIVE',
    started_at          DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at        DATETIME     NULL,
    FOREIGN KEY (ge_id)          REFERENCES general_expenses(id),
    FOREIGN KEY (workflow_id)    REFERENCES workflow_definitions(id),
    FOREIGN KEY (current_step_id) REFERENCES workflow_steps(id)
) ENGINE=InnoDB;

-- ============================================================
-- 13. WORKFLOW TASKS  (one row per step assignment)
-- ============================================================
CREATE TABLE workflow_tasks (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    instance_id     INT UNSIGNED NOT NULL,
    step_id         INT UNSIGNED NOT NULL,
    assignee_id     INT UNSIGNED NULL,
    status          ENUM('PENDING','IN_PROGRESS','COMPLETED','REJECTED','QUERIED') NOT NULL DEFAULT 'PENDING',
    action_taken    ENUM('APPROVED','REJECTED','QUERIED') NULL,
    comments        TEXT         NULL,
    otp_verified    TINYINT(1)   NOT NULL DEFAULT 0,
    ge_version_at_action INT UNSIGNED NULL,
    assigned_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at    DATETIME     NULL,
    FOREIGN KEY (instance_id) REFERENCES workflow_instances(id) ON DELETE CASCADE,
    FOREIGN KEY (step_id)     REFERENCES workflow_steps(id),
    FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 14. DELEGATE ASSIGNMENTS  (who handles which role/department)
-- ============================================================
CREATE TABLE delegate_assignments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id         INT UNSIGNED NOT NULL,
    user_id         INT UNSIGNED NOT NULL,
    department_id   INT UNSIGNED NULL,   -- NULL = global / central role
    faculty_code    VARCHAR(20)  NULL,
    is_active       TINYINT(1)   NOT NULL DEFAULT 1,
    assigned_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    assigned_by     INT UNSIGNED NULL,
    FOREIGN KEY (role_id)       REFERENCES roles(id),
    FOREIGN KEY (user_id)       REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_by)   REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 15. QUERY / CLARIFICATION THREADS
-- ============================================================
CREATE TABLE ge_queries (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ge_id           INT UNSIGNED NOT NULL,
    task_id         INT UNSIGNED NOT NULL,
    raised_by       INT UNSIGNED NOT NULL,
    message         TEXT         NOT NULL,
    status          ENUM('OPEN','RESOLVED') NOT NULL DEFAULT 'OPEN',
    raised_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resolved_at     DATETIME     NULL,
    FOREIGN KEY (ge_id)      REFERENCES general_expenses(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id)    REFERENCES workflow_tasks(id),
    FOREIGN KEY (raised_by)  REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE ge_query_responses (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    query_id    INT UNSIGNED NOT NULL,
    responded_by INT UNSIGNED NOT NULL,
    message     TEXT         NOT NULL,
    responded_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (query_id)      REFERENCES ge_queries(id) ON DELETE CASCADE,
    FOREIGN KEY (responded_by)  REFERENCES users(id)
) ENGINE=InnoDB;

-- ============================================================
-- 16. OTP TOKENS
-- ============================================================
CREATE TABLE otp_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    task_id     INT UNSIGNED NOT NULL,
    code        VARCHAR(6)   NOT NULL,
    attempts    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    is_used     TINYINT(1)   NOT NULL DEFAULT 0,
    expires_at  DATETIME     NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (task_id) REFERENCES workflow_tasks(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 17. AUDIT LOG
-- ============================================================
CREATE TABLE audit_log (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ge_id       INT UNSIGNED NULL,
    user_id     INT UNSIGNED NULL,
    action      VARCHAR(100) NOT NULL,
    description TEXT         NULL,
    old_value   JSON         NULL,
    new_value   JSON         NULL,
    ip_address  VARCHAR(45)  NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ge_id)    REFERENCES general_expenses(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 18. NOTIFICATIONS
-- ============================================================
CREATE TABLE notifications (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    ge_id       INT UNSIGNED NULL,
    type        VARCHAR(50)  NOT NULL,   -- APPROVAL_REQUIRED, QUERIED, APPROVED, etc.
    message     TEXT         NOT NULL,
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (ge_id)   REFERENCES general_expenses(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- 19. SESSIONS  (server-side auth tokens)
-- ============================================================
CREATE TABLE user_sessions (
    id          VARCHAR(64)  PRIMARY KEY,    -- random token
    user_id     INT UNSIGNED NOT NULL,
    ip_address  VARCHAR(45)  NULL,
    user_agent  TEXT         NULL,
    expires_at  DATETIME     NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- INDEXES for common queries
-- ============================================================
CREATE INDEX idx_ge_claimant   ON general_expenses(claimant_id);
CREATE INDEX idx_ge_status     ON general_expenses(status);
CREATE INDEX idx_ge_dept       ON general_expenses(department_id);
CREATE INDEX idx_ge_number     ON general_expenses(ge_number);
CREATE INDEX idx_tasks_assignee ON workflow_tasks(assignee_id, status);
CREATE INDEX idx_notif_user    ON notifications(user_id, is_read);
CREATE INDEX idx_audit_ge      ON audit_log(ge_id);
CREATE INDEX idx_audit_user    ON audit_log(user_id);

-- ============================================================
-- GAP 1 MIGRATION — Structured Budget Coding (Div / FN / Act / Item / SI / D)
-- ============================================================
ALTER TABLE ge_accounting_lines
  ADD COLUMN budget_div  VARCHAR(20) NULL AFTER account_name,
  ADD COLUMN budget_fn   VARCHAR(20) NULL AFTER budget_div,
  ADD COLUMN budget_act  VARCHAR(20) NULL AFTER budget_fn,
  ADD COLUMN budget_item VARCHAR(20) NULL AFTER budget_act,
  ADD COLUMN budget_si   VARCHAR(20) NULL AFTER budget_item,
  ADD COLUMN budget_d    VARCHAR(20) NULL AFTER budget_si;

-- ============================================================
-- GAP 2 MIGRATION — Claimant Declaration Signature
-- ============================================================
ALTER TABLE general_expenses
  ADD COLUMN claimant_signature_data MEDIUMTEXT NULL AFTER description,
  ADD COLUMN claimant_signed_at      DATETIME   NULL AFTER claimant_signature_data;

-- ============================================================
-- GAP 3 MIGRATION — HOD Certification
-- ============================================================
ALTER TABLE workflow_tasks
  ADD COLUMN hod_signature_data MEDIUMTEXT   NULL AFTER comments,
  ADD COLUMN hod_designation    VARCHAR(150) NULL AFTER hod_signature_data,
  ADD COLUMN hod_certified_at   DATETIME     NULL AFTER hod_designation;

-- ============================================================
-- GAP 4 MIGRATION — Sent to Accounts Flag
-- ============================================================
ALTER TABLE general_expenses
  ADD COLUMN sent_to_accounts    TINYINT(1)   NOT NULL DEFAULT 0 AFTER commitment_number,
  ADD COLUMN sent_to_accounts_at DATETIME     NULL               AFTER sent_to_accounts,
  ADD COLUMN sent_to_accounts_by INT UNSIGNED NULL               AFTER sent_to_accounts_at;

ALTER TABLE general_expenses
  ADD CONSTRAINT fk_ge_sent_by
  FOREIGN KEY (sent_to_accounts_by) REFERENCES users(id) ON DELETE SET NULL;
