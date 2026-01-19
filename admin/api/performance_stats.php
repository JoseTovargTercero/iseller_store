<?php
// admin/api/performance_stats.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

// 1. Stats by period (Daily, Weekly, Monthly)
$stats = [
    'daily' => ['income' => 0, 'profit' => 0, 'sales' => 0],
    'weekly' => ['income' => 0, 'profit' => 0, 'sales' => 0],
    'monthly' => ['income' => 0, 'profit' => 0, 'sales' => 0],
    'total' => ['income' => 0, 'profit' => 0, 'sales' => 0]
];

// Daily
$sql_daily = "SELECT SUM(valor_compra) as income, SUM(ganancia_generada) as profit, COUNT(*) as sales 
              FROM compras_por_usuarios 
              WHERE DATE(fecha) = CURDATE() AND deleted_at IS NULL AND estado = 'entregada'";
$res_daily = $conexion_store->query($sql_daily);
if ($row = $res_daily->fetch_assoc()) {
    $stats['daily'] = [
        'income' => (float)($row['income'] ?? 0),
        'profit' => (float)($row['profit'] ?? 0),
        'sales' => (int)$row['sales']
    ];
}

// Weekly (last 7 days)
$sql_weekly = "SELECT SUM(valor_compra) as income, SUM(ganancia_generada) as profit, COUNT(*) as sales 
               FROM compras_por_usuarios 
               WHERE fecha >= DATE_SUB(NOW(), INTERVAL 7 DAY) AND deleted_at IS NULL AND estado = 'entregada'";
$res_weekly = $conexion_store->query($sql_weekly);
if ($row = $res_weekly->fetch_assoc()) {
    $stats['weekly'] = [
        'income' => (float)($row['income'] ?? 0),
        'profit' => (float)($row['profit'] ?? 0),
        'sales' => (int)$row['sales']
    ];
}

// Monthly (this month)
$sql_monthly = "SELECT SUM(valor_compra) as income, SUM(ganancia_generada) as profit, COUNT(*) as sales 
                FROM compras_por_usuarios 
                WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) AND YEAR(fecha) = YEAR(CURRENT_DATE()) 
                AND deleted_at IS NULL AND estado = 'entregada'";
$res_monthly = $conexion_store->query($sql_monthly);
if ($row = $res_monthly->fetch_assoc()) {
    $stats['monthly'] = [
        'income' => (float)($row['income'] ?? 0),
        'profit' => (float)($row['profit'] ?? 0),
        'sales' => (int)$row['sales']
    ];
}

// Total
$sql_total = "SELECT SUM(valor_compra) as income, SUM(ganancia_generada) as profit, COUNT(*) as sales 
              FROM compras_por_usuarios 
              WHERE deleted_at IS NULL AND estado = 'entregada'";
$res_total = $conexion_store->query($sql_total);
if ($row = $res_total->fetch_assoc()) {
    $stats['total'] = [
        'income' => (float)($row['income'] ?? 0),
        'profit' => (float)($row['profit'] ?? 0),
        'sales' => (int)$row['sales']
    ];
}

// 2. Chart data (Last 15 days)
$chart_data = [
    'labels' => [],
    'income' => [],
    'profit' => []
];

$sql_chart = "SELECT DATE(fecha) as date, SUM(valor_compra) as income, SUM(ganancia_generada) as profit 
              FROM compras_por_usuarios 
              WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) 
              AND deleted_at IS NULL AND estado = 'entregada'
              GROUP BY DATE(fecha) 
              ORDER BY DATE(fecha) ASC";
$res_chart = $conexion_store->query($sql_chart);

// Fill missing days with 0
$days_data = [];
$current = new DateTime();
$current->modify('-14 days');
for($i=0; $i<=14; $i++) {
    $date_str = $current->format('Y-m-d');
    $days_data[$date_str] = ['income' => 0, 'profit' => 0];
    $current->modify('+1 day');
}

while ($row = $res_chart->fetch_assoc()) {
    $days_data[$row['date']] = [
        'income' => (float)$row['income'],
        'profit' => (float)$row['profit']
    ];
}

foreach($days_data as $date => $data) {
    $chart_data['labels'][] = date('d M', strtotime($date));
    $chart_data['income'][] = $data['income'];
    $chart_data['profit'][] = $data['profit'];
}

echo json_encode([
    'success' => true, 
    'stats' => $stats, 
    'chart_data' => $chart_data
]);
?>
