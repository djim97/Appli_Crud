<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $stmt = $pdo->query('SELECT tr.*, a.nom, a.prenom, p.nomp FROM travailler tr LEFT JOIN agent a ON tr.ida = a.idA LEFT JOIN projet p ON tr.idp = p.idp ORDER BY tr.numt DESC');
    $assignments = $stmt->fetchAll();
    echo json_encode($assignments);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch assignments: ' . $e->getMessage()]);
}
