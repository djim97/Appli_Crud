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

if (!$id || !is_numeric($id) || (int) $id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Un ID de projet valide est requis']);
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM projet WHERE idp = :idp');
    $stmt->execute([':idp' => (int) $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Projet introuvable']);
    } else {
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Échec de la suppression du projet']);
}
