<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->query('SELECT p.*, t.libelletype FROM projet p LEFT JOIN typeprojet t ON p.idtype = t.idtype ORDER BY p.idp DESC');
    $projets = $stmt->fetchAll();
    echo json_encode($projets);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch projects: ' . $e->getMessage()]);
}
