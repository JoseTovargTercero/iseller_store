<?php
require_once('../core/db.php');
require_once('../core/session.php');

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = getUserId();
$conexion_store->begin_transaction();

try {
    $initReward = false;
    initialReward:

    $stmt = $conexion_store->prepare("
        SELECT id, tipo, monto, nivel_desbloqueo, estado
        FROM recompensas_usuario 
        WHERE (usuario_id = ?) AND (estado = 'disponible' OR estado = 'bloqueado')
        ORDER BY nivel_desbloqueo DESC
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recompensas = $result->fetch_all(MYSQLI_ASSOC) ?: [];
    $getUserLevel = getUserLevel();
    
    $nivelUsuario = $getUserLevel[0];
    $puntosUsuario = $getUserLevel[1];
    $stmt->close();

    if ($nivelUsuario == 1 && $puntosUsuario == "0.00" && count($recompensas) == 0) {
        // registrar descuento

          $stmtDiscount = $conexion_store->prepare("
            INSERT INTO recompensas_usuario (usuario_id, nivel_desbloqueo, tipo, monto, estado)
            VALUES (?, 1, 'descuento_ganancia', 0, 'disponible')");
          $stmtDiscount->bind_param("i", $user_id);
          if (!$stmtDiscount->execute()) {
              throw new Exception("Error al agregar descuento: " . $conexion_store->error);
          }
          $stmtDiscount->close();


        $stmtReward = $conexion_store->prepare("
            INSERT INTO recompensas_usuario (usuario_id, nivel_desbloqueo, tipo, monto, estado)
            VALUES (?, 1, 'monetaria', 5.00, 'bloqueado')
        ");
        $stmtReward->bind_param("i", $user_id);
        if (!$stmtReward->execute()) {
            throw new Exception("Error al agregar recompensa inicial: " . $conexion_store->error);
        }
        
        $stmtReward->close();
        $initReward = true;
        goto initialReward;
    }

    echo json_encode([
        'success' => true,
        'has_rewards' => count($recompensas) > 0,
        'count' => count($recompensas),
        'rewards' => $recompensas,
        'init_reward' => $initReward
    ]);
    $conexion_store->commit();
    

} catch (Exception $e) {
    $conexion_store->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
