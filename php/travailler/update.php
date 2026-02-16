<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$id = $input['numt'] ?? null;
$role = trim($input['role'] ?? '');
$dateaff = trim($input['dateaff'] ?? '');
$ida = $input['ida'] ?? '';
$idp = $input['idp'] ?? '';

if (!$id || !is_numeric($id) || (int) $id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Valid assignment ID is required']);
    exit;
}

if ($role === '' || $dateaff === '' || $ida === '' || $idp === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required (role, dateaff, ida, idp)']);
    exit;
}

if (!isValidDate($dateaff)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid assignment date format (expected yyyy-mm-dd)']);
    exit;
}

if (!recordExists($pdo, 'agent', 'idA', $ida)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Selected agent does not exist']);
    exit;
}

if (!recordExists($pdo, 'projet', 'idp', $idp)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Selected project does not exist']);
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE travailler SET role = :role, dateaff = :dateaff, ida = :ida, idp = :idp WHERE numt = :numt');
    $stmt->execute([
        ':numt' => (int) $id,
        ':role' => $role,
        ':dateaff' => $dateaff,
        ':ida' => (int) $ida,
        ':idp' => (int) $idp
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Assignment not found']);
    } else {
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update assignment']);
}
