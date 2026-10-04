<?php
// GET /api/admin/departments.php — List all departments, grouped by faculty
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

require_auth(); // any logged-in user can fetch departments

$db = Database::getInstance();

// Fetch all departments ordered by faculty then name
$stmt = $db->query(
    'SELECT id, code, name, faculty_code, is_active
     FROM departments
     WHERE is_active = 1
     ORDER BY COALESCE(faculty_code, code), name'
);
$all = $stmt->fetchAll();

// Build flat list
$flat = [];
foreach ($all as $d) {
    $flat[] = [
        'id'           => (int)$d['id'],
        'code'         => $d['code'],
        'name'         => $d['name'],
        'faculty_code' => $d['faculty_code'],
    ];
}

// Build grouped list — for use in <optgroup> selects
// Top-level (faculty_code IS NULL) are groups or standalone
$groups = [];
$standalone = [];

foreach ($all as $d) {
    if (!$d['faculty_code']) {
        // Could be a faculty/top-level or a standalone dept
        $groups[$d['code']] = [
            'id'       => (int)$d['id'],
            'code'     => $d['code'],
            'name'     => $d['name'],
            'children' => [],
        ];
    }
}
foreach ($all as $d) {
    if ($d['faculty_code'] && isset($groups[$d['faculty_code']])) {
        $groups[$d['faculty_code']]['children'][] = [
            'id'   => (int)$d['id'],
            'code' => $d['code'],
            'name' => $d['name'],
        ];
    } elseif ($d['faculty_code']) {
        // Faculty code doesn't match a known group — add as standalone
        $standalone[] = [
            'id'   => (int)$d['id'],
            'code' => $d['code'],
            'name' => $d['name'],
        ];
    }
}

json_success('OK', [
    'departments' => $flat,
    'grouped'     => array_values($groups),
    'standalone'  => $standalone,
]);
