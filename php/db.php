<?php
// ==================== CORS ====================
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ==================== DATABASE ====================
$host = '127.0.0.1';
$dbname = 'crud';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Échec de la connexion à la base de données']);
    exit;
}

// ==================== HELPERS ====================

function isValidDate($dateStr) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        return false;
    }
    $parts = explode('-', $dateStr);
    return checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0]);
}

function recordExists($pdo, $table, $idColumn, $idValue) {
    $allowed = [
        'agent' => 'idA',
        'typeprojet' => 'idtype',
        'projet' => 'idp',
        'travailler' => 'numt'
    ];
    if (!isset($allowed[$table]) || $allowed[$table] !== $idColumn) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT 1 FROM $table WHERE $idColumn = :id LIMIT 1");
    $stmt->execute([':id' => (int)$idValue]);
    return $stmt->fetchColumn() !== false;
}
