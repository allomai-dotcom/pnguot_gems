-- ============================================================
-- Migration 004 — Uploaded GE Forms
-- Allows claimants to upload already-filled paper GE forms
-- for archiving purposes.
-- ============================================================

CREATE TABLE IF NOT EXISTS ge_uploaded_forms (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uploader_id     INT UNSIGNED NOT NULL,
    department_id   INT UNSIGNED NULL,
    title           VARCHAR(200) NOT NULL,          -- user-supplied description
    ge_reference    VARCHAR(100) NULL,              -- optional GE number on the paper form
    original_name   VARCHAR(255) NOT NULL,
    stored_name     VARCHAR(255) NOT NULL UNIQUE,   -- UUID filename on disk
    mime_type       VARCHAR(100) NOT NULL,
    file_size       INT UNSIGNED NOT NULL,
    notes           TEXT         NULL,
    uploaded_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    deleted_at      DATETIME     NULL,
    FOREIGN KEY (uploader_id)  REFERENCES users(id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_uploaded_forms_uploader ON ge_uploaded_forms(uploader_id);
