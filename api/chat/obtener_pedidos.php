<?php
/**
 * API: Obtener Pedidos del Usuario
 * Retorna los pedidos del usuario para asociar con conversaciones
 */

require_once('../../core/db.php');
require_once('../../core/session.php');

header('Content-Type: application/json');

// Verificar autenticación
requireLogin();
$usuario_id = getUserId();

try {
    $sql = "SELECT id, valor_compra, fecha, estatus
            FROM compras_por_usuarios
            WHERE usuario_id = ?
            ORDER BY fecha DESC
            LIMIT 20";
    
    $stmt = $conexion_store->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $pedidos = [];
    while ($row = $result->fetch_assoc()) {
        $pedidos[] = [
            'id' => (int)$row['id'],
            'valor' => number_format((float)$row['valor_compra'], 2),
            'fecha' => $row['fecha'],
            'estatus' => $row['estatus']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'pedidos' => $pedidos
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error al obtener pedidos'
    ]);
}
?>
