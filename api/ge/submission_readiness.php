<?php
// GET /api/ge/submission_readiness.php?id=X
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
$id   = (int)($_GET['id'] ?? 0);
if (!$id) json_error('GE id required.');

$db   = Database::getInstance();
$stmt = $db->prepare('SELECT * FROM general_expenses WHERE id = ? AND claimant_id = ?');
$stmt->execute([$id, $user['id']]);
$ge = $stmt->fetch();
if (!$ge) json_error('GE not found.', 404);

$checks = [];

// Required documents
$requiredDocs = [
    'QUOTATION_1'         => 'Supplier Quotation 1',
    'QUOTATION_2'         => 'Supplier Quotation 2',
    'QUOTATION_3'         => 'Supplier Quotation 3',
    'JUSTIFICATION_LETTER'=> 'Justification Letter',
];
$docStmt = $db->prepare(
    'SELECT document_type FROM ge_documents WHERE ge_id = ? AND deleted_at IS NULL'
);
$docStmt->execute([$id]);
$uploaded = array_column($docStmt->fetchAll(), 'document_type');

foreach ($requiredDocs as $type => $label) {
    $checks[] = [
        'key'     => $type,
        'label'   => $label,
        'passed'  => in_array($type, $uploaded),
        'required'=> true,
    ];
}

// GE validated (status check)
$checks[] = [
    'key'    => 'FORM_VALIDATED',
    'label'  => 'GE Form Validated',
    'passed' => in_array($ge['status'], ['READY_FOR_DOCUMENTS','SUBMITTED','QUERIED']),
    'required'=> true,
];

// Claimant signature
$checks[] = [
    'key'     => 'CLAIMANT_SIGNATURE',
    'label'   => 'Claimant Signature',
    'passed'  => !empty($ge['claimant_signature_data']),
    'required'=> true,
];

$allPassed = !in_array(false, array_column(
    array_filter($checks, fn($c) => $c['required']), 'passed'
));

json_success('OK', ['ready' => $allPassed, 'checks' => $checks]);
