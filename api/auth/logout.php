<?php
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();
start_session();

$user = get_authenticated_user();
if ($user) audit($user['id'], 'LOGOUT', 'User logged out');

$_SESSION = [];
session_destroy();
json_success('Logged out successfully.');
