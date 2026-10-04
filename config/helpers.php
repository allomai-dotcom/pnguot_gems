<?php
// ============================================================
// PNGUOT GEMS — Helper Functions
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

// ── HTTP Helpers ─────────────────────────────────────────────
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $code = 400, array $extra = []): void {
    json_response(array_merge(['success' => false, 'message' => $message], $extra), $code);
}

function json_success(string $message, array $data = [], int $code = 200): void {
    json_response(array_merge(['success' => true, 'message' => $message], $data), $code);
}

// ── CORS ─────────────────────────────────────────────────────
function set_cors_headers(): void {
    header('Access-Control-Allow-Origin: ' . CORS_ALLOWED_ORIGIN);
    header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
}

// ── Auth / Session ────────────────────────────────────────────
function start_session(): void {
    $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    session_name(SESSION_NAME);
    session_set_cookie_params(SESSION_LIFETIME, '/', '', $secure, true);
    if (session_status() === PHP_SESSION_NONE) session_start();
}

function get_authenticated_user(): ?array {
    start_session();
    if (empty($_SESSION['user_id'])) return null;
    $db = Database::getInstance();
    $stmt = $db->prepare(
        'SELECT u.id, u.staff_id, u.first_name, u.last_name, u.email,
                u.phone, u.department_id, d.code AS dept_code, d.name AS dept_name,
                GROUP_CONCAT(r.code ORDER BY r.code SEPARATOR ",") AS roles
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         LEFT JOIN user_roles ur ON ur.user_id = u.id
         LEFT JOIN roles r ON r.id = ur.role_id
         WHERE u.id = ? AND u.is_active = 1
         GROUP BY u.id'
    );
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user) return null;
    $user['roles'] = $user['roles'] ? explode(',', $user['roles']) : [];
    return $user;
}

function require_auth(): array {
    $user = get_authenticated_user();
    if (!$user) json_error('Unauthorised. Please log in.', 401);
    return $user;
}

function require_role(array $allowedRoles): array {
    $user = require_auth();
    $hasRole = !empty(array_intersect($user['roles'], $allowedRoles));
    if (!$hasRole) json_error('Forbidden. Insufficient role.', 403);
    return $user;
}

// ── Input ─────────────────────────────────────────────────────
function get_json_body(): array {
    $raw = file_get_contents('php://input');
    return $raw ? (json_decode($raw, true) ?? []) : [];
}

// ── GE Number ─────────────────────────────────────────────────
function generate_ge_number(): string {
    $db   = Database::getInstance();
    $year = (int) date('Y');
    // Atomic increment using UPDATE + last_insert_id trick
    $db->exec("INSERT INTO ge_number_sequence (year, next_val) VALUES ($year, 2)
               ON DUPLICATE KEY UPDATE next_val = next_val + 1");
    $row = $db->query("SELECT next_val - 1 AS seq FROM ge_number_sequence WHERE year = $year")->fetch();
    $seq = (int)$row['seq'];
    return GE_NUMBER_PREFIX . '-' . $year . '-' . str_pad($seq, GE_NUMBER_PADDING, '0', STR_PAD_LEFT);
}

// ── Audit ─────────────────────────────────────────────────────
function audit(int $userId, string $action, string $desc = '', ?int $geId = null,
               mixed $old = null, mixed $new = null): void {
    $db = Database::getInstance();
    $stmt = $db->prepare(
        'INSERT INTO audit_log (ge_id, user_id, action, description, old_value, new_value, ip_address)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $geId,
        $userId,
        $action,
        $desc,
        $old !== null ? json_encode($old) : null,
        $new !== null ? json_encode($new) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

// ── Notification ──────────────────────────────────────────────
function notify(int $userId, string $type, string $message, ?int $geId = null): void {
    $db = Database::getInstance();
    $db->prepare('INSERT INTO notifications (user_id, ge_id, type, message) VALUES (?,?,?,?)')
       ->execute([$userId, $geId, $type, $message]);
}

// ── UUID for file storage ─────────────────────────────────────
function generate_uuid(): string {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0,0xffff), mt_rand(0,0xffff),
        mt_rand(0,0xffff),
        mt_rand(0,0x0fff)|0x4000,
        mt_rand(0,0x3fff)|0x8000,
        mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff)
    );
}

// ── Status label ─────────────────────────────────────────────
function ge_status_label(string $status): string {
    return match($status) {
        'DRAFT'                         => 'Draft',
        'READY_FOR_DOCUMENTS'           => 'Ready for Documents',
        'SUBMITTED'                     => 'Submitted',
        'PENDING_HOS'                   => 'Pending HOS Approval',
        'PENDING_DEAN'                  => 'Pending Dean Approval',
        'PENDING_ICT'                   => 'Pending ICT Approval',
        'PENDING_PROCUREMENT'           => 'Pending Procurement',
        'PENDING_ACCOUNTS_VERIFICATION' => 'Pending Accounts Verification',
        'PENDING_VC'                    => 'Pending Vice Chancellor',
        'PENDING_PAYMENT'               => 'Pending Payment',
        'PENDING_ACCOUNTS_PROCESSING'   => 'Pending Accounts Processing',
        'QUERIED'                       => 'Queried',
        'COMPLETED'                     => 'Completed',
        'CANCELLED'                     => 'Cancelled',
        'WORKFLOW_CONFIGURATION_ERROR'  => 'Workflow Config Error',
        default                         => $status,
    };
}
