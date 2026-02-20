<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Entrée JSON invalide']);
    exit;
}

$libelle = trim($input['libelletype'] ?? '');
$description = trim($input['descriptiont'] ?? '');

if ($libelle === '' || $description === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Le libellé et la description sont obligatoires']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO typeprojet (libelletype, descriptiont) VALUES (:libelletype, :descriptiont)');
    $stmt->execute([
        ':libelletype' => $libelle,
        ':descriptiont' => $description
    ]);

    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Échec de la création du type de projet']);
}
