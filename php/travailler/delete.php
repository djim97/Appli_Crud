<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON input']);
    exit;
}

$id = $input['numt'] ?? null;

if (!$id || !is_numeric($id) || (int) $id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Valid assignment ID is required']);
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM travailler WHERE numt = :numt');
    $stmt->execute([':numt' => (int) $id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Assignment not found']);
    } else {
        echo json_encode(['success' => true]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to delete assignment: ' . $e->getMessage()]);
}
