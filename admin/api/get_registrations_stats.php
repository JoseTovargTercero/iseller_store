<?php
// admin/api/get_registrations_stats.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

try {
    // Ultimos 15 días
    $sql = "SELECT DATE(creado_en) as fecha, COUNT(*) as total 
            FROM usuarios 
            WHERE creado_en >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
            GROUP BY DATE(creado_en)
            ORDER BY fecha ASC";
            
    $result = $conexion_store->query($sql);
    
    $labels = [];
    $data = [];
    
    // Fill gaps with 0
    $stats = [];
    while ($row = $result->fetch_assoc()) {
        $stats[$row['fecha']] = (int)$row['total'];
    }
    
    for ($i = 14; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $labels[] = date('d M', strtotime($date));
        $data[] = $stats[$date] ?? 0;
    }

    echo json_encode([
        'success' => true,
        'chart_data' => [
            'labels' => $labels,
            'data' => $data
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
