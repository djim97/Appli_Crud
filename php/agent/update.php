<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Entrée JSON invalide']);
    exit;
}

$id = $input['idA'] ?? null;
$nom = trim($input['nom'] ?? '');
$prenom = trim($input['prenom'] ?? '');
$fonction = trim($input['fonction'] ?? '');
$email = trim($input['email'] ?? '');
$telephone = $input['telephone'] ?? '';
$dateEmbauche = trim($input['date_embauche'] ?? '');
$salaire = $input['salaire'] ?? '';

if (!$id || !is_numeric($id) || (int) $id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Un ID d\'agent valide est requis']);
    exit;
}

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
    $check = $pdo->prepare('SELECT idA FROM agent WHERE (email = :email OR telephone = :telephone) AND idA != :idA');
    $check->execute([':email' => $email, ':telephone' => (int) $telephone, ':idA' => (int) $id]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        http_response_code(409);
        $checkEmail = $pdo->prepare('SELECT idA FROM agent WHERE email = :email AND idA != :idA');
        $checkEmail->execute([':email' => $email, ':idA' => (int) $id]);
        if ($checkEmail->fetch()) {
            echo json_encode(['success' => false, 'error' => "L'email \"$email\" existe déjà"]);
        } else {
            echo json_encode(['success' => false, 'error' => "Le téléphone \"$telephone\" existe déjà"]);
        }
        exit;
    }

    $stmt = $pdo->prepare('UPDATE agent SET nom = :nom, prenom = :prenom, fonction = :fonction, email = :email, telephone = :telephone, date_embauche = :date_embauche, salaire = :salaire WHERE idA = :idA');
    $stmt->execute([
        ':idA' => (int) $id,
        ':nom' => $nom,
        ':prenom' => $prenom,
        ':fonction' => $fonction,
        ':email' => $email,
        ':telephone' => (int) $telephone,
        ':date_embauche' => $dateEmbauche,
        ':salaire' => (int) $salaire
    ]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Agent introuvable']);
    } else {
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Échec de la mise à jour de l\'agent']);
}
