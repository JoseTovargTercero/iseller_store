<?php
// admin/api/dashboard_stats.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

$sql = "
    SELECT estado, COUNT(*) as count 
    FROM compras_por_usuarios 
    WHERE deleted_at IS NULL 
    GROUP BY estado
";
$res = $conexion_store->query($sql);

$stats = [
    'pendiente' => 0,
    'en_revision' => 0,
    'enviada' => 0,
    'entregada' => 0,
    'rechazada' => 0
];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $stats[$row['estado']] = (int)$row['count'];
    }
}

// Visitas Totales
$resVisitas = $conexion_store->query("SELECT COUNT(*) as total FROM visitas");
$visitasTotales = $resVisitas ? $resVisitas->fetch_assoc()['total'] : 0;

// Visitas Hoy
$resVisitasHoy = $conexion_store->query("SELECT COUNT(*) as total FROM visitas WHERE DATE(fecha) = CURDATE()");
$visitasHoy = $resVisitasHoy ? $resVisitasHoy->fetch_assoc()['total'] : 0;

$stats['visitas_totales'] = (int)$visitasTotales;
$stats['visitas_hoy'] = (int)$visitasHoy;

echo json_encode(['success' => true, 'stats' => $stats]);
?>
