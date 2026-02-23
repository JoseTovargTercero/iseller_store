<?php
// admin/api/get_customers_locations.php
header('Content-Type: application/json');
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/session.php';

requireAdminLogin();

try {
    $sql = "SELECT u.id, u.nombre, ad.direccion, ad.lat, ad.lng 
            FROM usuarios u
            JOIN usuarios_direcciones ad ON u.id = ad.usuario_id
            WHERE ad.lat IS NOT NULL AND ad.lng IS NOT NULL
            AND ad.lat != '' AND ad.lng != ''
            GROUP BY u.id"; // Just one location per user for the map
            
    $result = $conexion_store->query($sql);
    $locations = [];
    
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }

    echo json_encode([
        'success' => true,
        'locations' => $locations
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
