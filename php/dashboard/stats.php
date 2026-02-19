<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

try {
    // Total number of projects
    $stmtTotal = $pdo->query('SELECT COUNT(*) as total FROM projet');
    $totalProjets = $stmtTotal->fetch()['total'];

    // Projects by status
    $stmtStatus = $pdo->query('SELECT statut, COUNT(*) as count FROM projet GROUP BY statut ORDER BY count DESC');
    $projetsByStatus = $stmtStatus->fetchAll();

    // Affectations (agent - projet)
    $stmtAffectationsList = $pdo->query('
        SELECT a.nom, a.prenom, p.nomp, tr.role
        FROM travailler tr
        JOIN agent a ON tr.ida = a.idA
        JOIN projet p ON tr.idp = p.idp
        ORDER BY a.nom, a.prenom
    ');
    $affectationsList = $stmtAffectationsList->fetchAll();

    // Total budget
    $stmtBudget = $pdo->query('SELECT COALESCE(SUM(budget), 0) as total_budget FROM projet');
    $totalBudget = $stmtBudget->fetch()['total_budget'];

    // Number of agents
    $stmtAgents = $pdo->query('SELECT COUNT(*) as total FROM agent');
    $totalAgents = $stmtAgents->fetch()['total'];

    // Number of project types
    $stmtTypes = $pdo->query('SELECT COUNT(*) as total FROM typeprojet');
    $totalTypes = $stmtTypes->fetch()['total'];

    // Number of assignments
    $stmtAffectations = $pdo->query('SELECT COUNT(*) as total FROM travailler');
    $totalAffectations = $stmtAffectations->fetch()['total'];

    echo json_encode([
        'totalProjets' => (int)$totalProjets,
        'projetsByStatus' => $projetsByStatus,
        'affectationsList' => $affectationsList,
        'totalBudget' => (float)$totalBudget,
        'totalAgents' => (int)$totalAgents,
        'totalTypes' => (int)$totalTypes,
        'totalAffectations' => (int)$totalAffectations
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch dashboard statistics']);
}
