<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
    $offset = ($page - 1) * $limit;

    $stmt = $pdo->prepare('SELECT p.*, t.libelletype FROM projet p LEFT JOIN typeprojet t ON p.idtype = t.idtype ORDER BY p.idp DESC LIMIT :limit OFFSET :offset');
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $projets = $stmt->fetchAll();

    echo json_encode($projets);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch projects']);
}
