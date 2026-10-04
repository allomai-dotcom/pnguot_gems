<?php
// ============================================================
// PNGUOT GEMS — Application Configuration
// ============================================================

define('APP_NAME',    'PNGUOT General Expense Monitoring System');
define('APP_VERSION', '1.0');
define('APP_ENV',     'production'); // 'development' | 'production'

// Database — Local / LAN Server (XAMPP)
define('DB_HOST',     'localhost');
define('DB_PORT',     3306);
define('DB_NAME',     'pnguot_gems');
define('DB_USER',     'root');
define('DB_PASS',     '');           // Change this if you set a MySQL root password
define('DB_CHARSET',  'utf8mb4');

// Session
define('SESSION_NAME',     'PNGUOT_GEMS');
define('SESSION_LIFETIME', 28800); // 8 hours in seconds

// File uploads — stored inside public root under /uploads/documents/ (InfinityFree)
define('UPLOAD_BASE_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/documents/');
define('MAX_UPLOAD_SIZE',  10 * 1024 * 1024); // 10 MB
define('ALLOWED_MIME_TYPES', ['application/pdf', 'image/jpeg', 'image/png', 'image/gif']);

// OTP
define('OTP_LENGTH',          6);
define('OTP_EXPIRY_MINUTES',  5);
define('OTP_MAX_ATTEMPTS',    5);
define('OTP_RESEND_COOLDOWN', 60); // seconds

// Development flags
define('DEV_BYPASS_OTP', APP_ENV === 'development'); // skip OTP in dev mode

// GE Number format: GE-YYYY-NNNNNN
define('GE_NUMBER_PREFIX', 'GE');
define('GE_NUMBER_PADDING', 6);

// Capital item threshold (Kina)
define('CAPITAL_ITEM_THRESHOLD', 3000.00);

// CORS — LAN deployment (allow same origin)
define('CORS_ALLOWED_ORIGIN', '*'); // Restrict to LAN IP if needed, e.g. 'http://192.168.1.100'
