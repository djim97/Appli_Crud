<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$id = $input['idp'] ?? null;
$nomp = trim($input['nomp'] ?? '');
$description = trim($input['description'] ?? '');
$dated = trim($input['dated'] ?? '');
$datf = trim($input['datf'] ?? '');
$budget = $input['budget'] ?? '';
$statut = trim($input['statut'] ?? '');
$idtype = $input['idtype'] ?? '';

if (!$id || !is_numeric($id) || (int) $id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Valid project ID is required']);
    exit;
}

if ($nomp === '' || $description === '' || $dated === '' || $datf === '' || $budget === '' || $statut === '' || $idtype === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE projet SET nomp = :nomp, description = :description, dated = :dated, datf = :datf, budget = :budget, statut = :statut, idtype = :idtype WHERE idp = :idp');
    $stmt->execute([
        ':idp' => (int) $id,
        ':nomp' => $nomp,
        ':description' => $description,
        ':dated' => $dated,
        ':datf' => $datf,
        ':budget' => (int) $budget,
        ':statut' => $statut,
        ':idtype' => (int) $idtype
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Project not found']);
    } else {
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update project: ' . $e->getMessage()]);
}
