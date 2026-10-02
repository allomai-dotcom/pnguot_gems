<?php
// GET /api/ge/quotations.php — Quotation tracker: GEs with uploaded quotation docs
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') json_error('Method not allowed.', 405);

$db = Database::getInstance();

// Fetch GEs that have at least one quotation document uploaded
$stmt = $db->prepare(
    "SELECT DISTINCT ge.id, ge.ge_number, ge.payee_name, ge.total_amount, ge.status, ge.created_at,
            d.name AS department_name
     FROM general_expenses ge
     JOIN departments d ON d.id = ge.department_id
     JOIN ge_documents gd ON gd.ge_id = ge.id
     WHERE gd.document_type IN ('QUOTATION_1','QUOTATION_2','QUOTATION_3')
       AND gd.deleted_at IS NULL
     ORDER BY ge.created_at DESC"
);
$stmt->execute();
$ges = $stmt->fetchAll();

if (!$ges) {
    json_success('OK', ['quotations' => []]);
    exit;
}

// Fetch all quotation documents for these GEs in one query
$geIds = array_column($ges, 'id');
$placeholders = implode(',', array_fill(0, count($geIds), '?'));
$docStmt = $db->prepare(
    "SELECT ge_id, document_type, id AS doc_id, original_name, file_size, uploaded_at
     FROM ge_documents
     WHERE ge_id IN ($placeholders)
       AND document_type IN ('QUOTATION_1','QUOTATION_2','QUOTATION_3')
       AND deleted_at IS NULL"
);
$docStmt->execute($geIds);
$allDocs = $docStmt->fetchAll();

// Index documents by ge_id
$docsByGe = [];
foreach ($allDocs as $doc) {
    $docsByGe[$doc['ge_id']][] = $doc;
}

// Build response
$result = [];
foreach ($ges as $ge) {
    $statusLabel = ge_status_label($ge['status']);
    $quotations  = [];
    $docs        = $docsByGe[$ge['id']] ?? [];
    foreach ($docs as $doc) {
        $slot = match($doc['document_type']) {
            'QUOTATION_1' => 1,
            'QUOTATION_2' => 2,
            'QUOTATION_3' => 3,
            default       => 0,
        };
        if (!$slot) continue;
        $quotations[] = [
            'slot'          => $slot,
            'reference'     => 'QT-' . $ge['ge_number'] . '-' . $slot,
            'doc_id'        => $doc['doc_id'],
            'original_name' => $doc['original_name'],
            'file_size'     => (int)$doc['file_size'],
            'uploaded_at'   => $doc['uploaded_at'],
            'view_url'      => '../api/documents/view.php?doc_id=' . $doc['doc_id'],
        ];
    }
    // Sort by slot
    usort($quotations, fn($a, $b) => $a['slot'] <=> $b['slot']);
    $result[] = [
        'ge_id'           => $ge['id'],
        'ge_number'       => $ge['ge_number'],
        'payee_name'      => $ge['payee_name'],
        'department_name' => $ge['department_name'],
        'total_amount'    => (float)$ge['total_amount'],
        'status'          => $ge['status'],
        'status_label'    => $statusLabel,
        'quotations'      => $quotations,
    ];
}

json_success('OK', ['quotations' => $result]);
