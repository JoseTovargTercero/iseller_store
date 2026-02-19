<?php
header('Content-Type: application/json');

require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

try {
    $stmt = $conexion_store->prepare("
        SELECT u.id, u.nombre, u.email, COUNT(r.id) AS total_disponibles
        FROM recompensas_usuario r
        JOIN usuarios u ON r.usuario_id = u.id
        WHERE r.estado = 'disponible'
        GROUP BY u.id, u.nombre, u.email
        ORDER BY total_disponibles DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode(['success' => true, 'users' => $users]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
