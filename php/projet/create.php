<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$nomp = trim($input['nomp'] ?? '');
$description = trim($input['description'] ?? '');
$dated = trim($input['dated'] ?? '');
$datf = trim($input['datf'] ?? '');
$budget = $input['budget'] ?? '';
$statut = trim($input['statut'] ?? '');
$idtype = $input['idtype'] ?? '';

if ($nomp === '' || $description === '' || $dated === '' || $datf === '' || $budget === '' || $statut === '' || $idtype === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO projet (nomp, description, dated, datf, budget, statut, idtype) VALUES (:nomp, :description, :dated, :datf, :budget, :statut, :idtype)');
    $stmt->execute([
        ':nomp' => $nomp,
        ':description' => $description,
        ':dated' => $dated,
        ':datf' => $datf,
        ':budget' => (int) $budget,
        ':statut' => $statut,
        ':idtype' => (int) $idtype
    ]);

    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create project: ' . $e->getMessage()]);
}
