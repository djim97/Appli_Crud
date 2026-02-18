<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$nom = trim($input['nom'] ?? '');
$prenom = trim($input['prenom'] ?? '');
$fonction = trim($input['fonction'] ?? '');
$email = trim($input['email'] ?? '');
$telephone = $input['telephone'] ?? '';
$dateEmbauche = trim($input['date_embauche'] ?? '');
$salaire = $input['salaire'] ?? '';

if ($nom === '' || $prenom === '' || $fonction === '' || $email === '' || $telephone === '' || $dateEmbauche === '' || $salaire === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid email format']);
    exit;
}

if (!isValidDate($dateEmbauche)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid hire date format (expected yyyy-mm-dd)']);
    exit;
}

if (!is_numeric($salaire) || (int)$salaire < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Salary must be a positive number']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO agent (nom, prenom, fonction, email, telephone, date_embauche, salaire) VALUES (:nom, :prenom, :fonction, :email, :telephone, :date_embauche, :salaire)');
    $stmt->execute([
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':fonction' => $fonction,
        ':email' => $email,
        ':telephone' => (int) $telephone,
        ':date_embauche' => $dateEmbauche,
        ':salaire' => (int) $salaire
    ]);

    echo json_encode(['success' => true, 'id' => (int) $pdo->lastInsertId()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to create agent']);
}
