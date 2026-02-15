<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->query('SELECT * FROM typeprojet ORDER BY idtype DESC');
    $types = $stmt->fetchAll();
    echo json_encode($types);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch project types: ' . $e->getMessage()]);
}
