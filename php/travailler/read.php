<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
    $offset = ($page - 1) * $limit;

    $stmt = $pdo->prepare('SELECT tr.*, a.nom, a.prenom, p.nomp FROM travailler tr LEFT JOIN agent a ON tr.ida = a.idA LEFT JOIN projet p ON tr.idp = p.idp ORDER BY tr.numt DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $assignments = $stmt->fetchAll();

    echo json_encode($assignments);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Échec du chargement des affectations']);
}
