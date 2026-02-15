<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$role = trim($input['role'] ?? '');
$dateaff = trim($input['dateaff'] ?? '');
$ida = $input['ida'] ?? '';
$idp = $input['idp'] ?? '';

if ($role === '' || $dateaff === '' || $ida === '' || $idp === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required (role, dateaff, ida, idp)']);
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
    echo json_encode(['success' => false, 'error' => 'Failed to create assignment: ' . $e->getMessage()]);
}
