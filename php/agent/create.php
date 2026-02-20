<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Entrée JSON invalide']);
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
    echo json_encode(['success' => false, 'error' => 'Tous les champs sont obligatoires']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Format d\'email invalide']);
    exit;
}

if (!isValidDate($dateEmbauche)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Format de date d\'embauche invalide (attendu aaaa-mm-jj)']);
    exit;
}

if (!is_numeric($salaire) || (int)$salaire < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Le salaire doit être un nombre positif']);
    exit;
}

try {
    $check = $pdo->prepare('SELECT idA FROM agent WHERE email = :email OR telephone = :telephone');
    $check->execute([':email' => $email, ':telephone' => (int) $telephone]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        http_response_code(409);
        $checkEmail = $pdo->prepare('SELECT idA FROM agent WHERE email = :email');
        $checkEmail->execute([':email' => $email]);
        if ($checkEmail->fetch()) {
            echo json_encode(['success' => false, 'error' => "L'email \"$email\" existe déjà"]);
        } else {
            echo json_encode(['success' => false, 'error' => "Le téléphone \"$telephone\" existe déjà"]);
        }
        exit;
    }

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
    echo json_encode(['success' => false, 'error' => 'Échec de la création de l\'agent']);
}
