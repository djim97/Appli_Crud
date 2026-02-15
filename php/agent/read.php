<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->query('SELECT * FROM agent ORDER BY idA DESC');
    $agents = $stmt->fetchAll();
    echo json_encode($agents);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch agents: ' . $e->getMessage()]);
}
