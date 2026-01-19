<?php
require_once('../core/db.php');
require_once('../core/session.php');

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = getUserId();

try {
    $stmt = $conexion_store->prepare("
        SELECT id, tipo, monto, nivel_desbloqueo, estado
        FROM recompensas_usuario 
        WHERE usuario_id = ? AND estado = 'disponible'
        ORDER BY nivel_desbloqueo DESC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recompensas = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'has_rewards' => count($recompensas) > 0,
        'count' => count($recompensas),
        'rewards' => $recompensas
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
