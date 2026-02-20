<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Entrée JSON invalide']);
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
    echo json_encode(['success' => false, 'error' => 'Un ID de projet valide est requis']);
    exit;
}

if ($nomp === '' || $description === '' || $dated === '' || $datf === '' || $budget === '' || $statut === '' || $idtype === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tous les champs sont obligatoires']);
    exit;
}

if (!isValidDate($dated)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Format de date de début invalide (attendu aaaa-mm-jj)']);
    exit;
}

if (!isValidDate($datf)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Format de date de fin invalide (attendu aaaa-mm-jj)']);
    exit;
}

if ($dated > $datf) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'La date de début doit être avant ou égale à la date de fin']);
    exit;
}

if (!is_numeric($budget) || (int)$budget < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Le budget doit être un nombre positif']);
    exit;
}

if (!recordExists($pdo, 'typeprojet', 'idtype', $idtype)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Le type de projet sélectionné n.existe pas']);
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
        echo json_encode(['success' => false, 'error' => 'Projet introuvable']);
    } else {
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Échec de la mise à jour du projet']);
}
