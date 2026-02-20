<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Entrée JSON invalide']);
    exit;
}

$role = trim($input['role'] ?? '');
$dateaff = trim($input['dateaff'] ?? '');
$ida = $input['ida'] ?? '';
$idp = $input['idp'] ?? '';

if ($role === '' || $dateaff === '' || $ida === '' || $idp === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Tous les champs sont obligatoires (rôle, date affectation, agent, projet)']);
    exit;
}

if (!isValidDate($dateaff)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Format de date d.affectation invalide (attendu aaaa-mm-jj)']);
    exit;
}

if (!recordExists($pdo, 'agent', 'idA', $ida)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'L.agent sélectionné n.existe pas']);
    exit;
}

if (!recordExists($pdo, 'projet', 'idp', $idp)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Le projet sélectionné n.existe pas']);
    exit;
}

$stmtProjet = $pdo->prepare('SELECT statut FROM projet WHERE idp = :idp');
$stmtProjet->execute([':idp' => (int) $idp]);
$projet = $stmtProjet->fetch();
if ($projet && strtolower(trim($projet['statut'])) === 'terminé') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Impossible d\'affecter à un projet terminé']);
    exit;
}

$stmtAgent = $pdo->prepare('SELECT date_embauche FROM agent WHERE idA = :idA');
$stmtAgent->execute([':idA' => (int) $ida]);
$agent = $stmtAgent->fetch();
if ($agent && $dateaff < $agent['date_embauche']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'La date d\'affectation ne peut pas être avant la date d\'embauche de l\'agent']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO travailler (role, dateaff, ida, idp) VALUES (:role, :dateaff, :ida, :idp)');
    $stmt->execute([
        ':role' => $role,
        ':dateaff' => $dateaff,
        ':ida' => (int) $ida,
        ':idp' => (int) $idp
    ]);

    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Échec de la création de l.affectation']);
}
