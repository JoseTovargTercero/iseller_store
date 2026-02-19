<?php
// admin/api/dashboard_stats.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

/**
 * Obtiene estadísticas resumidas para un periodo dado
 */
function getPeriodStats($conexion_store, $days = null) {
    $where = "WHERE deleted_at IS NULL";
    $whereVisits = "";
    
    if ($days !== null) {
        $interval = "INTERVAL $days DAY";
        $where .= " AND fecha >= (CURDATE() - $interval)";
        $whereVisits = " WHERE fecha >= (CURDATE() - $interval)";
    }

    // 1. Órdenes por estado y Ingresos
    $sql = "
        SELECT 
            estado, 
            COUNT(*) as count,
            SUM(valor_compra_bs + importe_envio_bs - COALESCE(ahorrado_bs, 0)) as total_revenue
        FROM compras_por_usuarios 
        $where
        GROUP BY estado
    ";
    $res = $conexion_store->query($sql);
    
    $period = [
        'orders' => [
            'pendiente' => 0,
            'en_revision' => 0,
            'enviada' => 0,
            'entregada' => 0,
            'rechazada' => 0,
            'total' => 0
        ],
        'revenue' => 0,
        'visits' => 0,
        'unique_visits' => 0
    ];

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $period['orders'][$row['estado']] = (int)$row['count'];
            $period['orders']['total'] += (int)$row['count'];
            if ($row['estado'] !== 'rechazada') {
                $period['revenue'] += (float)$row['total_revenue'];
            }
        }
    }

    // 2. Visitas y Visitas Únicas (por session_id o ip_address+user_agent)
    $sqlVisits = "
        SELECT 
            COUNT(*) as total,
            COUNT(DISTINCT session_id) as unique_total
        FROM visitas
        $whereVisits
    ";
    $resVisits = $conexion_store->query($sqlVisits);
    if ($resVisits && $rowV = $resVisits->fetch_assoc()) {
        $period['visits'] = (int)$rowV['total'];
        $period['unique_visits'] = (int)$rowV['unique_total'];
    }

    return $period;
}

try {
    $stats = [
        'today' => getPeriodStats($conexion_store, 0),
        'week'  => getPeriodStats($conexion_store, 7),
        'month' => getPeriodStats($conexion_store, 30),
        'total' => getPeriodStats($conexion_store, null)
    ];

    echo json_encode(['success' => true, 'stats' => $stats]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
