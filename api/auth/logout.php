<?php
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();
start_session();

// Audit the logout only if DB is available — never block logout on DB failure
try {
    $user = get_authenticated_user();
    if ($user) audit($user['id'], 'LOGOUT', 'User logged out');
} catch (Throwable $e) {
    // DB unavailable — skip audit, proceed with session destruction
    error_log('Logout audit skipped: ' . $e->getMessage());
}

$_SESSION = [];
session_destroy();
json_success('Logged out successfully.');
