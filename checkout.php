<?php
/**
 * Página de Checkout
 * Procesa la compra del carrito
 */

require_once('core/check_maintenance.php');
require_once('core/db.php');
require_once('core/_tasas_cambio.php');
require_once('core/session.php');
// carga la calculadora de precios
require_once('core/_calculadrora_precios.php');

// Variables para mensajes
$mensaje = '';
$tipo_mensaje = '';
$calculadora = new CalculadoraPrecios($pesoDolar, $peso_bolivar, $dolarBolivar, $bolivar_peso, $bcv, $data_monedas);
$costo_envio_dolares = 2;
$costo_envio_bs = $calculadora->convertirMonto($costo_envio_dolares, 'usd', 'v');
$costo_envio_bs = $costo_envio_bs['bs'];

// nivel del usuario
$getUserLevel = getUserLevel();
if (@$getUserLevel) {
    $nivelUsuario = $getUserLevel[0];
    $puntosUsuario = $getUserLevel[1];
}

$nombreCompleto = htmlspecialchars(getUserName());
$primerNombre = explode(" ", $nombreCompleto)[0];
$primerNombre = ucfirst(strtolower($primerNombre));

// --- FETCH STORE CONFIGURATION ---
$store_config = [
    'horario' => 'Lunes a Sábado, 9:00 AM - 9:00 PM',
    'horario_delivery' => '8:00 AM - 11:30 AM / 2:00 PM - 6:00 PM',
    'direccion' => 'Urb Simon Bolívar, Av. Principal, Diagonal a la 52 brigada'
];

$sqlConfig = "SELECT horario_atencion, horario_delivery, direccion FROM tienda_configuracion LIMIT 1";
$resConfig = $conexion_store->query($sqlConfig);
if ($resConfig && $rowConfig = $resConfig->fetch_assoc()) {
    if (!empty($rowConfig['horario_atencion'])) {
        $store_config['horario'] = $rowConfig['horario_atencion'];
    }
    if (!empty($rowConfig['horario_delivery'])) {
        $store_config['horario_delivery'] = $rowConfig['horario_delivery'];
    }
    if (!empty($rowConfig['direccion'])) {
        $store_config['direccion'] = $rowConfig['direccion'];
    }
}
// ---------------------------------

/**
 * Envía una notificación por correo a la administración sobre una nueva compra
 */
function enviarNotificacionCompra($orden_id, $user_id, $total_dolares, $num_operacion, $hora_operacion, $tipo_entrega) {
    global $conexion_store;
    
    // Obtener datos del usuario
    $stmt = $conexion_store->prepare("SELECT nombre, email, telefono FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $usuario = $res->fetch_assoc();
    $stmt->close();
    
    $nombre_cliente = $usuario['nombre'] ?? 'N/A';
    $email_cliente = $usuario['email'] ?? 'N/A';
    $telefono_cliente = $usuario['telefono'] ?? 'N/A';

    $from = 'nuevas-compras@iseller-tiendas.com';
    $subject = "Nueva Compra iSeller Store - Orden #$orden_id";
    $to = 'contacto@iseller-tiendas.com';

    $message = "Se ha registrado una nueva compra:\n\n";
    $message .= "Orden ID: #$orden_id\n";
    $message .= "Cliente: $nombre_cliente\n";
    $message .= "Email: $email_cliente\n";
    $message .= "Teléfono: $telefono_cliente\n";
    $message .= "Monto Total: $" . number_format($total_dolares, 2) . "\n";
    $message .= "Ref. Pago: $num_operacion\n";
    $message .= "Fecha/Hora Pago: $hora_operacion\n";
    $message .= "Tipo de Entrega: " . ($tipo_entrega === 'delivery' ? 'Delivery' : 'Retiro en Tienda') . "\n";
    $message .= "\nFavor revisar el panel administrativo para procesar la orden.";

    $headers = "From: $from\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    @mail($to, $subject, $message, $headers);
}

// Procesar solicitudes POST (API)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Validar sesión
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Debe iniciar sesión']);
        exit;
    }

    $user_id = getUserId();
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validar token CSRF para peticiones JSON
    if (!validateCSRFToken($input['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Error de validación CSRF']);
        exit;
    }

    $action = $input['action'] ?? '';

    // 1. Obtener direcciones
    if ($action === 'get_addresses') {
        $stmt = $conexion_store->prepare("SELECT * FROM usuarios_direcciones WHERE usuario_id = ? ORDER BY es_principal DESC, creado_en DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $direcciones = $result->fetch_all(MYSQLI_ASSOC);
        
        echo json_encode(['success' => true, 'addresses' => $direcciones]);
        exit;
    }

    // 2. Guardar nueva dirección
    if ($action === 'save_address') {
        $nombre = trim($input['nombre_receptor'] ?? '');
        $telefono = trim($input['telefono'] ?? '');
        $direccion = trim($input['direccion'] ?? '');
        $referencia = trim($input['referencia'] ?? '');
        $lat = $input['lat'] ?? null;
        $lng = $input['lng'] ?? null;

        if (empty($nombre) || empty($telefono) || empty($direccion)) {
            echo json_encode(['success' => false, 'message' => 'Complete los campos obligatorios']);
            exit;
        }

        // Resetear principal si esta es la primera o se marcó como principal
        // (Simplificación: siempre la última es principal por defecto en este ejemplo o manejamos la lógica)
        // Vamos a hacer la nueva dirección principal automáticamente para facilitar el flujo
        $stmt = $conexion_store->prepare("UPDATE usuarios_direcciones SET es_principal = 0 WHERE usuario_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        $sql = "INSERT INTO usuarios_direcciones (usuario_id, nombre_receptor, telefono, direccion, referencia, lat, lng, es_principal) VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $stmt = $conexion_store->prepare($sql);
        $stmt->bind_param("issssdd", $user_id, $nombre, $telefono, $direccion, $referencia, $lat, $lng);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id, 'message' => 'Dirección guardada']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $conexion_store->error]);
        }
        exit;
    }

    // 3. Procesar Compra
    if (isset($input['carrito'])) {
        // Validaciones extra para checkout
        if (empty($input['address_id']) && $input['tipo_entrega'] === 'delivery') {
            echo json_encode(['success' => false, 'message' => 'Debe seleccionar una dirección de entrega']);
            exit;
        }
        if (empty($input['payment_ref'])) {
             echo json_encode(['success' => false, 'message' => 'Faltan datos del pago']);
             exit;
        }

        $carrito = $input['carrito'];
        $address_id = $input['address_id'];
        $payment_raw = $input['payment_ref']; // Formato: REF | DATE
        
        // Parsear datos de pago
        $parts = explode('|', $payment_raw);
        $num_operacion = trim($parts[0] ?? '');
        $hora_operacion = trim($parts[1] ?? date('Y-m-d H:i:s'));

        // Calcular totales
        $total_dolares = 0;
        $total_pesos = 0;
        $total_bolivares = 0;
        
        foreach ($carrito as $item) {
            $subtotal_dolar = floatval($item['price']) * floatval($item['qty']);
            $subtotal_peso = floatval($item['pricePeso']) * floatval($item['qty']);
            $subtotal_bolivar = floatval($item['priceBolivar']) * floatval($item['qty']);
            
            $total_dolares += $subtotal_dolar;
            $total_pesos += $subtotal_peso;
            $total_bolivares += $subtotal_bolivar;
        }

        // --- 3.0 Lógica de Envío ---
        $tipo_entrega = $input['tipo_entrega'] ?? '';
        $importe_envio = 0;

        if ($tipo_entrega === 'retiro_tienda') {
            // Retiro en tienda: Envío 0, Dirección no obligatoria (aunque el ID podría venir null)
            $address_id = null; 
            $importe_envio = 0.00;
        } elseif ($tipo_entrega === 'delivery') {
            // Delivery
            if (empty($address_id)) {
                echo json_encode(['success' => false, 'message' => 'Para delivery debe seleccionar una dirección']);
                exit;
            }
            // Regla costo de envío
            if ($total_dolares > 35.00) {
                $importe_envio = 0.00;
            } else {
                $importe_envio = $costo_envio_dolares;
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Tipo de entrega inválido']);
            exit;
        }

        // Sumar envío al total (para registro, aunque el total_price de la orden suele ser productos)
        // Decisión: Guardaremos el envío separado en compras_por_usuarios, 
        // pero la orden suele reflejar el total a pagar.
        // Ajustaremos el total_dolares para incluir envío?
        // Según requerimiento: "Guardar correctamente en compras_por_usuarios: valor_compra (sin envío), importe_envio"
        // Entonces mantenemos $total_dolares como subtotal de productos para la tabla 'orden' o lo sumamos?
        // En muchos POS, 'orden.total_price' es el total final. Vamos a sumarlo para la tabla ORDEN,
        // pero en compras_por_usuarios guardamos separado.
        
        $total_pagar_dolares = $total_dolares + $importe_envio;
        // Asumiendo tasa 1:1 para simplificar conversión de envío en otras monedas o usando cambio del día si fuera necesario.
        // Por ahora sumamos solo en dólares al total final.
        
        // --- 3.0.1 Verificar y Aplicar Recompensas Disponibles ---
        $descuento_aplicado = 0;
        $recompensa_usada_id = null;
        $tipo_recompensa_usada = null;
        $generar_puntos = true; // Se desactiva si se usa descuento por ganancia
        $nuevo_monto_recompensa = 0;
        $nuevo_estado_recompensa = '';
        
        // Buscar recompensa disponible (prioridad: monetaria primero, luego descuento)
        $stmtRewards = $conexion_store->prepare("
            SELECT id, tipo, monto, porcentaje
            FROM recompensas_usuario 
            WHERE usuario_id = ? AND estado = 'disponible'
            ORDER BY tipo DESC, monto DESC
            LIMIT 1
        ");
        $stmtRewards->bind_param("i", $user_id);
        $stmtRewards->execute();
        $resRewards = $stmtRewards->get_result();
        
        if ($rowReward = $resRewards->fetch_assoc()) {
            $recompensa_usada_id = $rowReward['id'];
            $tipo_recompensa_usada = $rowReward['tipo'];
            $monto_recompensa = floatval($rowReward['monto']);
            $porcentaje_recompensa = $rowReward['porcentaje'];            
            if ($tipo_recompensa_usada === 'monetaria') {
                // Recompensa monetaria: descuento directo
                if ($total_pagar_dolares >= $monto_recompensa) {
                    // Consumir toda la recompensa
                    $descuento_aplicado = $monto_recompensa;
                    $nuevo_monto_recompensa = 0;
                    $nuevo_estado_recompensa = 'usado';
                } else {
                    // Uso parcial
                    $descuento_aplicado = $total_pagar_dolares;
                    $nuevo_monto_recompensa = $monto_recompensa - $total_pagar_dolares;
                    $nuevo_estado_recompensa = 'disponible';
                }
                
            } elseif ($tipo_recompensa_usada === 'descuento_ganancia') {
                // Descuento por ganancia: necesitamos calcular la ganancia total
                // Usaremos los datos de costos que ya tenemos más adelante en el flujo
                // Por ahora marcamos que se debe calcular
                // La ganancia se calculará en el loop de productos
                if ($porcentaje_recompensa != '0.50') {
                    $generar_puntos = false; // No genera puntos este tipo de compra
                }
                $nuevo_estado_recompensa = 'usado';
                $nuevo_monto_recompensa = 0;
            }
        }
        $stmtRewards->close();
        
        // Aplicar descuento al total (para monetaria ya está calculado, para ganancia se hará después)
        if ($tipo_recompensa_usada === 'monetaria') {
            $total_pagar_dolares -= $descuento_aplicado;
            if ($total_pagar_dolares < 0) $total_pagar_dolares = 0;
        }
        
        // --- 3.1 Insertar Venta en POS (Tabla 'orden') ---
        $conexion->begin_transaction();
        $conexion_store->begin_transaction();

        try {
            $fechaVenta = date('Y-m-d');
            $mes = date('Y-m');
            $ano = date('Y');
            $semana = date('Y-W');
            $dia = date('N');
            $id_sucursal = 1; // Default Online Store
            // $bss_id viene de db.php
            
            $status = 1; // 1 = Pendiente/Venta Normal
            $tipoVenta = 1; // 1 = Contado
            $pagoTipo = 2; // Default a Transferencia/Otro (Ajustar según sistema)

            // Insertar ORDEN
            $stmt = $conexion_store->prepare("
                INSERT INTO orden (
                    status, customer_id, total_price, created, modified, fecha,
                    semana, ano, total_price_bs, total_price_cop, tipoPago,
                    dia, id_sucursal, bss_id
                ) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->bind_param(
                "iisssssddsiii", 
                $tipoVenta, $user_id, $total_pagar_dolares, $fechaVenta, $mes, $semana, $ano,
                $total_bolivares, $total_pesos, $pagoTipo, $dia, $id_sucursal, $bss_id
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error creando orden: " . $stmt->error);
            }
            $orden_id = $stmt->insert_id;
            $stmt->close();

            // --- 3.2 Obtener Precios de Costo para Cálculo de Ganancia ---
            // Preparar consulta para obtener precio_compra, cantidad_unidades y mayor de todos los productos del carrito
            $product_ids = array_column($carrito, 'id');
            $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
            $sqlCostos = "SELECT id, precio_compra, cantidad_unidades, mayor FROM productos WHERE id IN ($placeholders) AND bss_id = ?";
            $stmtCostos = $conexion->prepare($sqlCostos);
            
            // Bind parameters dinámicamente
            $types = str_repeat('i', count($product_ids)) . 'i';
            $params = array_merge($product_ids, [$bss_id]);
            $stmtCostos->bind_param($types, ...$params);
            $stmtCostos->execute();
            $resCostos = $stmtCostos->get_result();
            
            // Almacenar datos de costos en array asociativo
            $costos_productos = [];
            while ($rowCosto = $resCostos->fetch_assoc()) {
                $costos_productos[$rowCosto['id']] = $rowCosto;
            }
            $stmtCostos->close();
            
            // Variable para acumular ganancia total
            $ganancia_total = 0;

            // --- 3.3 Insertar Artículos y Actualizar Stock ---
            $stmtItem = $conexion_store->prepare("
                INSERT INTO orden_articulos (
                    order_id, product_id, quantity, precio, bolivar, peso,
                    precio_venta_dolar, precio_venta_bs, precio_venta_cop, id_sucursal, bss_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // Stock queries
            $stmtStock = $conexion->prepare("SELECT stock, id_stock FROM stock WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ? LIMIT 1");
            $stmtUpdStock = $conexion->prepare("UPDATE stock SET stock = ? WHERE id_producto = ? AND id_sucursal = ? AND bss_id = ?");

            foreach ($carrito as $item) {
                // Insertar Item
                $qty = $item['qty'];
                $price = floatval($item['price']);
                $priceBs = floatval($item['priceBolivar']);
                $priceCop = floatval($item['pricePeso']);
                
                // --- CÁLCULO DE GANANCIA POR PRODUCTO ---
                $producto_id = $item['id'];
                if (isset($costos_productos[$producto_id])) {
                    $datoCosto = $costos_productos[$producto_id];
                    $precio_compra = floatval($datoCosto['precio_compra']);
                    $cantidad_unidades = floatval($datoCosto['cantidad_unidades']);
                    $mayor = $datoCosto['mayor'];
                    
                    // Calcular precio costo unitario
                    // Si mayor == 1, se vende por unidad completa, sino por cantidad_unidades
                    $precio_costo_unitario = $precio_compra / ($mayor == '1' ? 1 : $cantidad_unidades);
                    
                    // Ganancia por este producto = (precio_venta - precio_costo) * cantidad
                    $ganancia_producto = ($price - $precio_costo_unitario) * $qty;
                    $ganancia_total += $ganancia_producto;
                }
                
                // Nota: precio_venta_* y precio_* parecen redundantes o costo vs venta. Usaremos precio venta en ambos por seguridad si no tenemos costo.
                $stmtItem->bind_param(
                    "iiddddddddi",
                    $orden_id, $item['id'], $qty, 
                    $price, $priceBs, $priceCop, // precios base?
                    $price, $priceBs, $priceCop, // precios venta
                    $id_sucursal, $bss_id
                );
                $stmtItem->execute();

                // Actualizar Stock
                $stmtStock->bind_param("iii", $item['id'], $id_sucursal, $bss_id);
                $stmtStock->execute();
                $resStock = $stmtStock->get_result();
                if ($resStock->num_rows > 0) {
                    $rowStock = $resStock->fetch_assoc();
                    $newStock = max(0, $rowStock['stock'] - $qty);
                    $stmtUpdStock->bind_param("iiii", $newStock, $item['id'], $id_sucursal, $bss_id);
                    $stmtUpdStock->execute();
                }
            }
            $stmtItem->close();
            $stmtStock->close();
            $stmtUpdStock->close();
            
            $descuento_porcentaje = $puntosUsuario == '0.00' ? 0.50 : 0.90;

            // --- 3.3.1 Aplicar Descuento por Ganancia (si aplica) ---
            if ($tipo_recompensa_usada === 'descuento_ganancia' && $ganancia_total > 0) {
                // Calcular 90% de la ganancia como descuento
                $descuento_aplicado = $ganancia_total * $descuento_porcentaje;
                
                // Actualizar el total a pagar (restar del total que ya incluye envío)
                $total_pagar_dolares -= $descuento_aplicado;
                if ($total_pagar_dolares < 0) $total_pagar_dolares = 0;
                
                // Actualizar también el total de la orden que se guardó
                // Necesitamos actualizar la orden ya creada
                $stmtUpdateOrden = $conexion_store->prepare("UPDATE orden SET total_price = ? WHERE id = ?");
                $stmtUpdateOrden->bind_param("di", $total_pagar_dolares, $orden_id);
                $stmtUpdateOrden->execute();
                $stmtUpdateOrden->close();
            }

            // --- 3.4 Calcular Puntos de Fidelidad (solo si generar_puntos es true) ---
            $puntos_generados = 0;
            $puntos_nuevos = 0;
            $nivel_nuevo = 1;
            $subio_nivel = false;
            
            if ($generar_puntos) {
                // Obtener puntos actuales del usuario desde la base de datos
                $stmtUserPoints = $conexion_store->prepare("SELECT puntos, nivel FROM usuarios WHERE id = ?");
                $stmtUserPoints->bind_param("i", $user_id);
                $stmtUserPoints->execute();
                $resUserPoints = $stmtUserPoints->get_result();
                $userData = $resUserPoints->fetch_assoc();
                $puntos_actuales = floatval($userData['puntos'] ?? 0);
                $nivel_actual = intval($userData['nivel'] ?? 1);
                $stmtUserPoints->close();

                // Calcular puntos generados: mínimo entre ganancia_total y 10
                $puntos_generados = min($ganancia_total, 10);

                // Calcular nuevos valores acumulados
                $puntos_nuevos = $puntos_actuales + $puntos_generados;
                $nivel_nuevo = floor($puntos_nuevos / 10) + 1;
                $subio_nivel = ($nivel_nuevo > $nivel_actual);

                // --- 3.4.1 Registrar Recompensas por Subida de Nivel ---
                if ($subio_nivel) {
                    $stmtReward = $conexion_store->prepare("
                        INSERT INTO recompensas_usuario (usuario_id, nivel_desbloqueo, tipo, monto, porcentaje, estado)
                        VALUES (?, ?, ?, ?, ?, 'disponible')
                    ");

                    // Iterar por cada nivel subido (por si sube más de 1 de golpe)
                    for ($lvl = $nivel_actual + 1; $lvl <= $nivel_nuevo; $lvl++) {
                        $tipo_recompensa = 'descuento_ganancia';
                        $monto_recompensa = 0.00;
                        $porcentaje_recompensa = 0.90;

                        // Regla de negocio: Multiplos de 5 dan recompensa monetaria de $5
                        if ($lvl % 5 == 0) {
                            $tipo_recompensa = 'monetaria';
                            $monto_recompensa = 5.00;
                            $porcentaje_recompensa = 0.00;
                        }

                        $stmtReward->bind_param("iisdd", $user_id, $lvl, $tipo_recompensa, $monto_recompensa, $porcentaje_recompensa);
                        
                        if (!$stmtReward->execute()) {
                            throw new Exception("Error registrando recompensa: " . $stmtReward->error);
                        }
                    }
                    $stmtReward->close();
                }
            } else {
                // Si no se generan puntos, obtener valores actuales sin modificar
                $stmtUserPoints = $conexion_store->prepare("SELECT puntos, nivel FROM usuarios WHERE id = ?");
                $stmtUserPoints->bind_param("i", $user_id);
                $stmtUserPoints->execute();
                $resUserPoints = $stmtUserPoints->get_result();
                $userData = $resUserPoints->fetch_assoc();
                $puntos_nuevos = floatval($userData['puntos'] ?? 0);
                $nivel_nuevo = intval($userData['nivel'] ?? 1);
                $stmtUserPoints->close();
            }

            // --- 3.5 Insertar Relación Usuario-Compra (Tabla Solicitada) ---
            // Validar existencia de IDs (Ya tenemos user_id y orden_id)
            if (!$user_id || !$orden_id) {
                throw new Exception("Error de IDs para relacion usuario-compra");
            }

            $descuento_bs = 0;
            if ($descuento_aplicado > 0) {
                $bs_ratio = ($total_dolares > 0) ? ($total_bolivares / $total_dolares) : 0;
                $descuento_bs = $descuento_aplicado * $bs_ratio;
            }

            $stmtRel = $conexion_store->prepare("
                INSERT INTO compras_por_usuarios (
                    usuario_id, compra_id, direccion_id, valor_compra, valor_compra_bs,
                    numero_operacion_bancaria, hora_operacion_bancaria,
                    tipo_entrega, importe_envio, importe_envio_bs,
                    ganancia_generada, puntos_generados, nivel_resultante, ahorrado, ahorrado_bs
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if($descuento_aplicado > 0){
                $ganancia_total = $ganancia_total - $descuento_aplicado;
            }
            $stmtRel->bind_param(
                "iiidssssddddidd", 
                $user_id, $orden_id, $address_id, $total_dolares, $total_bolivares,
                $num_operacion, $hora_operacion,
                $tipo_entrega, $importe_envio, $costo_envio_bs,
                $ganancia_total, $puntos_generados, $nivel_nuevo, $descuento_aplicado, $descuento_bs
            );
            
            if (!$stmtRel->execute()) {
                throw new Exception("Error guardando relación compra-usuario: " . $stmtRel->error);
            }
            $stmtRel->close();

            // --- 3.6 Insertar en Historial de Puntos ---
            $stmtHistorial = $conexion_store->prepare("
                INSERT INTO historial_puntos (
                    usuario_id, compra_id, ganancia_base, puntos_aplicados, fecha
                ) VALUES (?, ?, ?, ?, NOW())
            ");
            
            $stmtHistorial->bind_param("iidd", $user_id, $orden_id, $ganancia_total, $puntos_generados);
            
            if (!$stmtHistorial->execute()) {
                throw new Exception("Error guardando historial de puntos: " . $stmtHistorial->error);
            }
            $stmtHistorial->close();

            // --- 3.7 Actualizar Puntos y Nivel del Usuario (solo si se generaron puntos) ---
            if ($generar_puntos) {
                $stmtUpdateUser = $conexion_store->prepare("
                    UPDATE usuarios 
                    SET puntos = ?, nivel = ?
                    WHERE id = ?
                ");
                $stmtUpdateUser->bind_param("dii", $puntos_nuevos, $nivel_nuevo, $user_id);
                
                if (!$stmtUpdateUser->execute()) {
                    throw new Exception("Error actualizando puntos del usuario: " . $stmtUpdateUser->error);
                }
                $stmtUpdateUser->close();
            }
            
            // --- 3.8 Actualizar Estado de Recompensa Usada ---
            if ($recompensa_usada_id !== null) {
                $fecha_uso = ($nuevo_estado_recompensa === 'usado') ? date('Y-m-d H:i:s') : null;
                
                $stmtUpdateReward = $conexion_store->prepare("
                    UPDATE recompensas_usuario 
                    SET monto = ?, estado = ?, fecha_uso = ?
                    WHERE id = ?
                ");
                $stmtUpdateReward->bind_param("dssi", $nuevo_monto_recompensa, $nuevo_estado_recompensa, $fecha_uso, $recompensa_usada_id);
                
                if (!$stmtUpdateReward->execute()) {
                    throw new Exception("Error actualizando recompensa: " . $stmtUpdateReward->error);
                }
                $stmtUpdateReward->close();
            }

            // Confirmar transacciones
            $conexion->commit();
            $conexion_store->commit();

            // Enviar notificación por correo
            enviarNotificacionCompra($orden_id, $user_id, $total_pagar_dolares, $num_operacion, $hora_operacion, $tipo_entrega);

            echo json_encode([
                'success' => true,
                'message' => 'Compra procesada exitosamente',
                'orden_id' => $orden_id, // ID real
                'total_dolares' => number_format($total_pagar_dolares, 2),
                'importe_envio' => $importe_envio,
                'orden_detalles' => [
                    'direccion_entrega' => $address_id,
                    'pago_ref' => $num_operacion,
                    'fecha_pago' => $hora_operacion
                ],
                // Información de puntos de fidelidad
                'puntos_ganados' => round($puntos_generados, 2),
                'puntos_totales' => round($puntos_nuevos, 2),
                'nivel_actual' => $nivel_nuevo,
                'subio_nivel' => $subio_nivel,
                // Información de recompensas aplicadas
                'descuento_aplicado' => round($descuento_aplicado, 2),
                'recompensa_usada' => $tipo_recompensa_usada
            ]);

        } catch (Exception $e) {
            $conexion->rollback();
            $conexion_store->rollback();
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - iSeller Store</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <!-- Global Styles -->
    <link rel="stylesheet" href="assets/css/global-styles.css">
    <!-- Chat System CSS -->
    <link rel="stylesheet" href="assets/css/chat.css">
    
    <meta name="csrf-token" content="<?php echo getCSRFToken(); ?>">
    
    <style>
        body { 
            background: var(--bg-secondary);
            font-family: 'Inter', sans-serif;
            padding-top: 80px; /* Space for fixed navbar */
        }
        .checkout-container { max-width: 1000px; margin: 0 auto; padding: 40px 20px; }
        .checkout-card { 
            background: white; 
            border-radius: var(--radius-lg); 
            box-shadow: var(--shadow-sm); 
            padding: 30px; 
            margin-bottom: 20px; 
            overflow: hidden; 
            border: 1px solid var(--border-color);
        }
        .step-indicator { display: flex; justify-content: space-between; margin-bottom: 30px; position: relative; }
        .step-indicator::before { content: ''; position: absolute; top: 15px; left: 0; right: 0; height: 2px; background: #e0e0e0; z-index: 1; }
        .step { position: relative; z-index: 2; background: white; padding: 0 10px; text-align: center; color: #aaa; }
        .step .circle { 
            width: 30px; height: 30px; 
            background: #e0e0e0; 
            border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; 
            margin: 0 auto 5px; color: white; font-weight: bold; transition: all 0.3s; 
        }
        .step.active .circle { background: var(--primary-color); }
        .step.active { color: var(--text-primary); font-weight: 600; }
        .step.completed .circle { background: #198754; }
        
        /* Map Styles */
        #map { height: 300px; width: 100%; border-radius: var(--radius-md); margin-top: 10px; z-index: 0; }
        
        .address-card { 
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md); 
            padding: 15px; 
            cursor: pointer; 
            transition: all 0.2s; 
            height: 100%;
            background: var(--bg-secondary);
        }
        .address-card:hover { border-color: var(--primary-color); background: white; }
        .address-card.selected { 
            border-color: var(--primary-color); 
            background: white; 
            box-shadow: 0 0 0 1px var(--primary-color);
            position: relative; 
        }
        .address-card.selected::after { content: '\f26a'; font-family: bootstrap-icons; position: absolute; top: 10px; right: 10px; color: var(--primary-color); font-size: 1.2rem; }
        
        .bank-info { 
            background: #F0FDF4; /* Light green tint */
            border-radius: var(--radius-md); 
            padding: 20px; 
            margin-bottom: 20px; 
        }
        .bank-data-row { display: flex; justify-content: space-between; border-bottom: 1px dashed var(--border-color); padding: 8px 0; }
        .bank-data-row:last-child { border-bottom: none; }
        
        .wizard-step { display: none; animation: fadeIn 0.4s; }
        .wizard-step.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .btn-primary-custom { 
            background-color: var(--primary-color); 
            border: none; 
            color: white; 
            border-radius: 50px;
            padding: 0.5rem 1.5rem;
            font-weight: 600;
        }
        .btn-primary-custom:hover { 
            background-color: var(--primary-dark);
            color: white; 
            transform: translateY(-1px);
        }

        .copy-btn {
            cursor: pointer;
            color: var(--primary-color);
            transition: all 0.2s;
            padding: 2px 6px;
            border-radius: 4px;
        }
        .copy-btn:hover {
            background-color: rgba(25, 135, 84, 0.1);
            transform: scale(1.1);
        }
    </style>
    
    <!-- Dexie & Leaflet -->
    <script src="https://cdn.jsdelivr.net/npm/dexie@3.2.4/dist/dexie.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
</head>

<body data-user-logged-in="<?php echo isLoggedIn() ? 'true' : 'false'; ?>">
    <!-- Navbar (Same as index) -->
    <nav class="navbar navbar-custom fixed-top">
        <div class="container-fluid d-flex align-items-center justify-content-between px-3 px-md-5">
            <!-- Left: Logo -->
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-shop-window text-success"></i>
                <span style="color: var(--primary-color);">iSeller</span> <span style="color: var(--text-primary);">Store</span>
            </a>

            <!-- Right: Actions -->
            <div class="header-actions d-flex align-items-center gap-3">
                 <div class="dropdown">
                    <?php if (isLoggedIn()): ?>
                        <button class="btn-icon" data-bs-toggle="dropdown" title="Mi Cuenta">
                            <i class="bi bi-person"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end custom-dropdown-menu">
                            <li><h6 class="dropdown-header"><?php echo htmlspecialchars(getUserName()); ?></h6></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="checkout.php"><i class="bi bi-cart-check me-2"></i> Checkout</a></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    <?php else: ?>
                        <a href="login.php" class="btn-icon" title="Iniciar Sesión">
                            <i class="bi bi-person"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <div class="checkout-container">
        <?php if (!isLoggedIn()): ?>
             <div class="checkout-card text-center" style="padding: 60px 20px;">
                <div class="mb-4">
                     <span class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle" style="width: 80px; height: 80px;">
                        <i class="bi bi-lock text-muted" style="font-size: 2rem;"></i>
                     </span>
                </div>
                <h3 class="fw-bold mb-2">Inicia sesión</h3>
                <p class="text-muted mb-4">Debes ingresar a tu cuenta para realizar el pedido de forma segura.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="login.php" class="btn btn-primary-custom px-4">Ingresar</a>
                    <a href="registro.php" class="btn btn-outline-secondary rounded-pill px-4">Registrarse</a>
                </div>
            </div>
        <?php else: ?>
        
        <!-- Header User -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="mb-0 fw-bold text-dark">Finalizar Compra</h3>
        </div>

        <div class="checkout-card">
            <!-- Stepper -->
            <div class="step-indicator">
                <div class="step active" id="step-ind-1">
                    <div class="circle">1</div>
                    <small>Entrega</small>
                </div>
                <div class="step" id="step-ind-2">
                    <div class="circle">2</div>
                    <small>Pago y Confirmar</small>
                </div>
            </div>

            <!-- STEP 1: ENTREGA -->
            <div id="step-1" class="wizard-step active">
                <h5 class="mb-4 fw-bold">Método de Entrega</h5>
                
                <!-- Selector Tipo Entrega -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card p-3 h-100 cursor-pointer border-success delivery-option selected" id="opt-delivery" onclick="setDeliveryType('delivery')">
                            <div class="d-flex align-items-center">
                                <div class="bg-success text-white rounded-circle p-2 me-3 avatar-circle">
                                    <i class="bi bi-truck fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Delivery</h6>
                                    <small class="text-muted">Costo de envío: $<?php echo $costo_envio_dolares . ' / ' . $costo_envio_bs . ' Bs'; ?> </small>
                                </div>
                                <div class="ms-auto text-success check-icon">
                                    <i class="bi bi-check-circle-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-3 h-100 cursor-pointer delivery-option" id="opt-pickup" onclick="setDeliveryType('retiro_tienda')">
                            <div class="d-flex align-items-center">
                                <div class="bg-light text-dark avatar-circle rounded-circle p-2 me-3">
                                    <i class="bi bi-shop fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-1">Retiro en Tienda</h6>
                                    <small class="text-muted">Pasa a buscar tu pedido</small>
                                </div>
                                <div class="ms-auto text-success check-icon d-none">
                                    <i class="bi bi-check-circle-fill fs-4"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="delivery-address-section">
                    <h5 class="mb-3 fw-bold">Dirección de Entrega</h5>

                    <!-- Lista de direcciones -->
                    <div id="address-list" class="row g-3 mb-4"></div>
                    
                    <button class="btn btn-outline-secondary w-100 py-2 border-dashed rounded-pill" onclick="modalNuevaDireccion()">
                        <i class="bi bi-plus-lg"></i> Agregar Nueva Dirección
                    </button>
                </div>
                
                <div id="pickup-info-section" class="d-none alert alert-success border-0 shadow-sm">
                    <div class="d-flex">
                        <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                        <div>
                            <h6 class="fw-bold">Información de Retiro</h6>
                            <p class="mb-0 small">Puedes retirar tu pedido en nuestra tienda principal:<br>
                            <strong><?php echo $store_config['direccion']; ?></strong><br>
                            Horario: <?php echo $store_config['horario']; ?></p>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                    <button class="btn btn-primary-custom px-5" onclick="nextStep(2)" id="btn-next-1" disabled>
                        Siguiente <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>



            <!-- STEP 2: RESUMEN Y PAGO -->
            <div id="step-2" class="wizard-step">
                <h5 class="mb-4 fw-bold">Resumen y Pago</h5>
                
                <div class="alert alert-light border">
                    <div class="d-flex justify-content-between">
                        <span><strong>Método de Entrega:</strong></span>
                        <a href="#" onclick="prevStep(1)" class="small text-decoration-none">Cambiar</a>
                    </div>
                    <p class="mb-2 text-muted small" id="summary-delivery-type">...</p>
                    
                    <div id="summary-address-block">
                        <div class="d-flex justify-content-between">
                            <span><strong>Dirección:</strong></span>
                        </div>
                        <p class="mb-0 text-muted small" id="summary-address">...</p>
                    </div>
                    
                    <hr class="my-2">
                    
                    <div class="d-flex justify-content-between">
                         <span class="text-success"><i class="bi bi-check-circle-fill"></i> Verifica tu pedido y registra el pago abajo.</span>
                    </div>
                </div>


                <div class="accordion accordion-flush mb-3" id="accordionFlushExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="flush-headingOne">
                        <button class="accordion-button collapsed light border rounded" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
                            <b>Ver lista de productos</b>
                        </button>
                        </h2>
                        <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
                        <div class="accordion-body">
                            <div id="cart-items-preview">
                                <!-- Se llena con JS -->
                            </div>
                        </div>
                        </div>
                    </div>
                    </div>
                <div class="bg-light p-3 rounded mb-3">
                     <div class="d-flex justify-content-between mb-1"><span>Subtotal:</span> <p><span id="sum-subtotal" class="">$0.00</span> / <span id="sum-subtotal-bs" >0.00 Bs</span></p></div>
                     <div class="d-flex justify-content-between mb-1 d-none" id="delivery-row"><span>Envío:</span> <span id="sum-shipping">$0.00</span></div>
                     <!-- Discount Row (Hidden by default) -->
                     <div id="discount-row" class="d-flex justify-content-between mb-1 text-danger" style="display: none !important;">
                         <span class="d-flex align-items-center gap-2">
                             <i class="bi bi-gift-fill"></i> Descuento:
                             <span id="discount-badge" class="badge bg-warning text-dark" style="font-size: 0.7rem;"></span>
                         </span>
                         <div class="d-flex flex-column align-items-end">
                            <strong id="sum-discount">-$0.00</strong> 
                            <strong id="sum-discount-bs">-0.00 Bs</strong>
                         </div>
                     </div>
                     <hr>
                     <div class="d-flex justify-content-between mb-1"><span>Total USD:</span> <strong class="text-success fs-5" id="sum-total-usd">$0.00</strong></div>
                     <div class="d-flex justify-content-between  mb-1"><span>Total Bs:</span> <span id="sum-total-bs" class="fs-5 fw-bold text-success">0.00 Bs</span></div>
                </div>
                <!-- Sección Pago Movida Aquí -->
                <section id="seccion-pago" class="mt-4 pt-3 border-top row">
                    <div class="col-lg-6">
                        <h5 class="mb-3 fw-bold">Datos para el Pago</h5>
                    <div class="bank-info">
                        <h6 class="fw-bold mb-3 text-success"><i class="bi bi-bank me-2"></i>Transferencia Bancaria / Pago móvil</h6>
                        <div class="bank-data-row"><span>Propietario:</span> <div class="d-flex m-auto align-items-center gap-2"><strong>José Ricardo Romero Tovar</strong> <i class="bi bi-copy copy-btn" onclick="copyToClipboard('José Ricardo Romero Tovar', 'Propietario')" title="Copiar Propietario"></i></div></div>
                        <div class="bank-data-row"><span>Banco:</span> <div class="d-flex m-auto align-items-center gap-2"><strong>Banesco (0134)</strong> <i class="bi bi-copy copy-btn" onclick="copyToClipboard('0134', 'Código de Banco')" title="Copiar Banco"></i></div></div>
                        <div class="bank-data-row"><span>Cédula:</span> <div class="d-flex m-auto align-items-center gap-2"><strong>V-27.640.176</strong> <i class="bi bi-copy copy-btn" onclick="copyToClipboard('27640176', 'Cédula')" title="Copiar Cédula"></i></div></div>
                        <div class="bank-data-row"><span>Teléfono:</span> <div class="d-flex m-auto align-items-center gap-2"><strong>0416-0679095</strong> <i class="bi bi-copy copy-btn" onclick="copyToClipboard('04160679095', 'Teléfono')" title="Copiar Teléfono"></i></div></div>
                        <div class="bank-data-row"><span>Cuenta:</span> <div class="d-flex m-auto align-items-center gap-2"><strong class="text-dark">0134-0444-5144-4121-1410</strong> <i class="bi bi-copy copy-btn" onclick="copyToClipboard('01340444514441211410', 'Cuenta')" title="Copiar Cuenta"></i></div></div>
                    </div>
                    </div>
                   <div class="col-lg-6">
                       <h5 class="mb-3 fw-bold">Reportar Pago</h5>
                           <div class="mb-3">
                               <label class="form-label small text-muted fw-bold">NÚMERO DE REFERENCIA *</label>
                               <input type="number" id="payment-ref" class="form-control">
                           </div>
                           <div class="mb-3">
                               <label class="form-label small text-muted fw-bold">FECHA Y HORA *</label>
                               <input type="datetime-local" id="payment-date" class="form-control">
                           </div>
                   </div>
                  </section>
                <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                    <button class="btn btn-outline-secondary rounded-pill px-4 nowrap" onclick="prevStep(1)"><i class="bi bi-arrow-left me-2"></i> Volver</button>
                    <button class="btn btn-success btn-lg rounded-pill px-5 shadow-sm nowrap" id="btn-finish" onclick="finalizarCompra()">
                        <i class="bi bi-check-lg me-2"></i> Confirmar
                    </button>
                </div>
            </div>
            <!-- Success Step -->
            <div id="step-success" class="wizard-step text-center py-5">
                <div class="mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-success text-white rounded-circle shadow" style="width: 80px; height: 80px;">
                        <i class="bi bi-check-lg" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <h3 class="fw-bold">¡Pedido Realizado!</h3>
                <p class="text-muted" style="width: 60%; margin: auto;"><b><?php echo $primerNombre; ?></b>, tu orden ha sido recibida correctamente y ya estamos procesándola. En breve recibirás una notificación con el estado de tu pedido y los detalles de la entrega.</p>
                <div class="alert alert-light border d-inline-block px-4 py-2 my-3">
                    Orden ID: <strong id="success-order-id" class="text-success">---</strong>
                </div>
                <!-- Mensaje Estimado -->
                <div id="estimated-time-container" class="mt-3 d-none">
                    <div class="alert alert-info d-inline-block px-4 py-2">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <span id="estimated-time-msg" class="fw-bold"></span>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="index.php" class="btn btn-primary-custom px-4">Volver a la tienda</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <!-- Modal Nueva Dirección -->
    <div class="modal fade" id="modalDireccion" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Nueva Dirección</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="form-direccion">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">NOMBRE RECEPTOR *</label>
                                <input type="text" class="form-control" id="addr-name" required placeholder="Quién recibe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">TELÉFONO *</label>
                                <input type="number" class="form-control" id="addr-phone" required placeholder="0414-XXXXXXX">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">DIRECCIÓN EXACTA *</label>
                                <textarea class="form-control" id="addr-text" rows="2" required placeholder="Calle, Casa, Urb..."></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">PUNTO DE REFERENCIA</label>
                                <input type="text" class="form-control" id="addr-ref" placeholder="Ej: Frente al parque...">
                            </div>
                            <!-- Mapa -->
                            <div class="col-12">
                                <label class="form-label text-success"><i class="bi bi-geo-alt-fill"></i> Ubicación en el mapa (Opcional)</label>
                                                         <div id="map"></div>

                                <div class="row mt-2 g-2">
                                    <div class="col-6"><small>Lat: <span id="lbl-lat" class="text-muted">---</span></small></div>
                                    <div class="col-6"><small>Lng: <span id="lbl-lng" class="text-muted">---</span></small></div>
                                </div>
                                <input type="hidden" id="addr-lat">
                                <input type="hidden" id="addr-lng">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary-custom px-4" onclick="guardarDireccion()">Guardar Dirección</button>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/confeti.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/dist/notiflix-Notiflix-67ba12d/dist/notiflix-aio-3.2.8.min.js"></script>

    <?php if (isLoggedIn()): ?>
    <script>
        // --- VARIABLES GLOBALES ---
        let selectedAddressId = null;
        let selectedAddressData = null;
        let deliveryType = 'delivery'; // 'delivery' or 'retiro_tienda'
        let cartItems = [];
        let map, marker;
        let nivelUsuario = <?php echo $nivelUsuario; ?>;
        let puntosUsuario = <?php echo $puntosUsuario; ?>;

        const db = new Dexie("POS_DB");
        db.version(2).stores({ 
            carritoActivo: 'id',
            cart_meta: 'id'
        });

        async function checkCartExpiration() {
            const meta = await db.cart_meta.get('last_updated');
            if (meta) {
                const now = Date.now();
                const diff = now - meta.timestamp;
                const limit = 20 * 60 * 1000;

                if (diff > limit) {
                    await db.carritoActivo.clear();
                    await db.cart_meta.delete('last_updated');
                    return true;
                }
            }
            return false;
        }

        // --- INICIALIZACIÓN ---
        document.addEventListener('DOMContentLoaded', () => {
             cargarCarrito();
             cargarDirecciones();
        });

        // --- FUNCIONES WIZARD ---
        function nextStep(step) {
            // Validaciones
            if(step === 2) {
                if(deliveryType === 'delivery' && !selectedAddressId) {
                    Notiflix.Notify.failure('Por favor selecciona una dirección de entrega');
                    return;
                }
                actualizarResumen();
            }

            // Cambiar vista
            document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
            document.getElementById(`step-${step}`).classList.add('active');
            
            // Actualizar indicador
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active', 'completed'));
            for(let i=1; i<step; i++) document.getElementById(`step-ind-${i}`).classList.add('completed');
            document.getElementById(`step-ind-${step}`).classList.add('active');
        }

        function prevStep(step) {
            document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
            document.getElementById(`step-${step}`).classList.add('active');
            
            // Actualizar indicador
            document.querySelectorAll('.step').forEach(el => el.classList.remove('active', 'completed'));
            for(let i=1; i<step; i++) document.getElementById(`step-ind-${i}`).classList.add('completed');
            document.getElementById(`step-ind-${step}`).classList.add('active');
        }

        // --- FUNCIONES CARRITO ---
        async function cargarCarrito() {
            await checkCartExpiration();
            cartItems = await db.carritoActivo.toArray();
            if(cartItems.length === 0) {
                 document.querySelector('.checkout-card').innerHTML = `<div class='text-center py-5'><h3>Carrito vacío</h3><a href='index.php' class='btn btn-primary-custom'>Ir a comprar</a></div>`;
                 return;
            }
        }

        async function actualizarResumen() {

             // Delivery Info
             const typeLabel = deliveryType === 'delivery' ? 'Delivery (Envío a domicilio)' : 'Retiro en Tienda';
             document.getElementById('summary-delivery-type').innerHTML = `<strong>${typeLabel}</strong>`;

             if(deliveryType === 'delivery') {
                 document.getElementById('summary-address-block').style.display = 'block';
                 if(selectedAddressData) {
                    document.getElementById('summary-address').innerHTML = `
                        <strong>${selectedAddressData.nombre_receptor}</strong> (${selectedAddressData.telefono})<br>
                        ${selectedAddressData.direccion}<br>
                        <i class="small text-muted">${selectedAddressData.referencia || ''}</i>
                    `;
                 }
             } else {
                 document.getElementById('summary-address-block').style.display = 'none';
             }
             
             
             // Payment (Ahora inputs en esta vista, no resumen estático)
             // const ref = document.getElementById('payment-ref').value;
             // let date = document.getElementById('payment-date').value.replace('T', ' ');
             // document.getElementById('summary-payment').innerHTML = `Transacción: <strong>${ref}</strong><br>Fecha: ${date}`;

            // Cart Totals
            let subtotalUsd=0, subtotalBs=0, totalCop=0, gananciaUsd = 0, gananciaBs = 0;
            // Calcular subtotal
            cartItems.forEach(item => {
                 subtotalUsd += item.price * item.qty;
                 subtotalBs += item.priceBolivar * item.qty;
                 totalCop += item.pricePeso * item.qty;
             
                 gananciaUsd += (item.price - item.price_C) * item.qty;
                 gananciaBs += (item.priceBolivar - item.price_C_Bs) * item.qty;
            });

            // Capture subtotal in Bs before any modification
            const subtotalBsDisplay = subtotalBs;
            
            // Calcular Envío
            let shippingCost = 0;
            let shippingCostBs = 0;
            if(deliveryType === 'delivery') {
                if(subtotalUsd > 35) {
                    shippingCost = 0; // Gratis
                    shippingCostBs = 0;
                } else {
                    shippingCost = parseFloat(<?php echo $costo_envio_dolares; ?>); // aqui se asigna el costo de envio
                    shippingCostBs = parseFloat(<?php echo $costo_envio_bs; ?>);
                }
            } else {
                shippingCost = 0;
                shippingCostBs = 0;
            }

            let totalUsd = subtotalUsd + shippingCost;
            let totalBs = subtotalBs + shippingCostBs;
            
            // --- Verificar Descuento Disponible ---
            let discountUsd = 0;
            let discountBs = 0;
            let discountType = null;
            
            try {
                const resRewards = await fetch('api/recompensas_check.php');
                const dataRewards = await resRewards.json();

                    const descuento_porcentaje = puntosUsuario == '0.00' ? 0.50 : 0.90;


                if(dataRewards.success && dataRewards.has_rewards && dataRewards.rewards.length > 0) {
                    const reward = dataRewards.rewards[0]; // Primera recompensa disponible
                    discountType = reward.tipo;

              
                    if(reward.tipo === 'monetaria') {
                        // Descuento monetario directo
                        discountUsd = Math.min(parseFloat(reward.monto), totalUsd);
                        // Calcular equivalente en Bs (aproximado usando ratio)
                        const bsRatio = totalBs / subtotalUsd;
                        discountBs = discountUsd * bsRatio;
                        
                    } else if(reward.tipo === 'descuento_ganancia') {
                        // Calcular ganancia total (aproximado: 90% de la ganancia)
                        // Nota: El cálculo exacto se hace en el backend
                        // Aquí mostramos un estimado para UX
                        const Profit = gananciaUsd; 
                        discountUsd = Profit * descuento_porcentaje;
                        const bsRatio = totalBs / subtotalUsd;
                        discountBs = discountUsd * bsRatio;
                    }
                    // Mostrar fila de descuento
                    if(discountUsd > 0 && reward.estado === 'disponible') {
                        document.getElementById('discount-row').style.display = 'flex';
                        document.getElementById('sum-discount').textContent = '-$' + discountUsd.toFixed(2);
                        document.getElementById('sum-discount-bs').textContent = '-' + discountBs.toFixed(2) + ' Bs';
                        document.getElementById('sum-subtotal').classList.add('text-decoration-line-through');
                        document.getElementById('sum-subtotal-bs').classList.add('text-decoration-line-through');
                        // Badge con tipo de recompensa
                        const badgeText = reward.tipo === 'monetaria' ? 'Bono' : 'Descuento Especial';
                        document.getElementById('discount-badge').textContent = badgeText;
                        // Aplicar descuento al total
                        totalUsd -= discountUsd;
                        totalBs -= discountBs;
                    }
                }
            } catch(e) {
                console.error('Error verificando descuentos:', e);
            }

            // Render Preview Items
             let html = '<ul class="list-group mb-3">';
             cartItems.forEach(item => {
                 html += `<li class="list-group-item d-flex justify-content-between lh-sm">
                    <div><h6 class="my-0">${item.name}</h6><small class="text-muted">x${item.qty}</small></div>
                    <span class="text-muted">$${(item.price*item.qty).toFixed(2)}</span>
                 </li>`;
            });
            html += '</ul>';
            
            document.getElementById('cart-items-preview').innerHTML = html;
            
            if(deliveryType !== 'delivery'){
                document.getElementById('delivery-row').classList.add('d-none');
            }else{
                document.getElementById('delivery-row').classList.remove('d-none');
            }
            document.getElementById('sum-subtotal').textContent = '$' + subtotalUsd.toFixed(2);
            document.getElementById('sum-subtotal-bs').textContent = subtotalBsDisplay.toFixed(2) + ' Bs';
            document.getElementById('sum-shipping').textContent = shippingCost === 0 ? 'GRATIS' : '$' + shippingCost.toFixed(2) + ' / ' + shippingCostBs.toFixed(2) + ' Bs';
            document.getElementById('sum-total-usd').textContent = '$' + totalUsd.toFixed(2);
            
            document.getElementById('sum-total-bs').textContent = totalBs.toFixed(2) + ' Bs';
        }

        // --- FUNCIONES DIRECCIONES ---
        function cargarDirecciones() {
            fetch('checkout.php', {
                method: 'POST',
                body: JSON.stringify({ 
                    action: 'get_addresses',
                    csrf_token: document.querySelector('meta[name="csrf-token"]').content
                }),
                headers: {'Content-Type': 'application/json'}
            })
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('address-list');
                container.innerHTML = '';
                
                if(data.success && data.addresses.length > 0) {
                    data.addresses.forEach(addr => {
                        const isSelected = selectedAddressId == addr.id;
                        const card = document.createElement('div');
                        card.className = 'col-md-6';
                        card.innerHTML = `
                            <div class="address-card ${isSelected ? 'selected' : ''}" onclick="seleccionarDireccion(this, ${addr.id})" data-json='${JSON.stringify(addr)}'>
                                <h6 class="fw-bold mb-1">${addr.nombre_receptor}</h6>
                                <p class="mb-1 small">${addr.direccion}</p>
                                <p class="mb-0 small text-muted"><i class="bi bi-telephone"></i> ${addr.telefono}</p>
                            </div>
                        `;
                        container.appendChild(card);
                        // Auto-select si no hay seleccionado o es principal
                        if(!selectedAddressId && addr.es_principal == 1) {
                            seleccionarDireccion(card.querySelector('.address-card'), addr.id);
                        }
                    });
                     // Habilitar btn wizard si hay seleccionado
                     if(selectedAddressId) document.getElementById('btn-next-1').disabled = false;
                } else {
                    container.innerHTML = '<div class="col-12 text-center text-muted py-3">No tienes direcciones guardadas.</div>';
                }
            });
        }

        function seleccionarDireccion(el, id) {
            document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
            el.classList.add('selected');
            selectedAddressId = id;
            selectedAddressData = JSON.parse(el.dataset.json);
            document.getElementById('btn-next-1').disabled = false;
        }

        // --- MAPA ---
        function modalNuevaDireccion() {
             const modalEl = document.getElementById('modalDireccion');
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
            

            modalEl.addEventListener('shown.bs.modal', () => {
                initMap();
               map.invalidateSize();
            }, { once: true });
        }

     function initMap() {
            if (map) {
                map.invalidateSize();
                return;
            }

            const defaultLat = 5.642742;
            const defaultLng = -67.602310;

            map = L.map('map').setView([defaultLat, defaultLng], 16);


            googleHybrid = L.tileLayer('http://{s}.google.com/vt/lyrs=s,h&x={x}&y={y}&z={z}',{
                    maxZoom: 20,
                    subdomains:['mt0','mt1','mt2','mt3']
            });

            googleHybrid.addTo(map);

            marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);

            map.on('click', e => {
                updateMarker(e.latlng.lat, e.latlng.lng);
            });

            marker.on('dragend', e => {
                const { lat, lng } = marker.getLatLng();
                updateUiCoords(lat, lng);
            });

        }



        function updateMarker(lat, lng) {
            marker.setLatLng([lat, lng]);
            updateUiCoords(lat, lng);
        }
        
        function updateUiCoords(lat, lng) {
            document.getElementById('addr-lat').value = lat.toFixed(8);
            document.getElementById('addr-lng').value = lng.toFixed(8);
            document.getElementById('lbl-lat').textContent = lat.toFixed(6);
            document.getElementById('lbl-lng').textContent = lng.toFixed(6);
        }

        function guardarDireccion() {
            const nombre = document.getElementById('addr-name').value;
            const telefono = document.getElementById('addr-phone').value;
            const direccion = document.getElementById('addr-text').value;
            const ref = document.getElementById('addr-ref').value;
            const lat = document.getElementById('addr-lat').value;
            const lng = document.getElementById('addr-lng').value;
            
            if(!nombre || !telefono || !direccion) {
                Notiflix.Notify.failure('Completa los campos obligatorios');
                return;
            }

            const btn = document.querySelector('#modalDireccion .btn-primary-custom');
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerText = 'Guardando...';

            fetch('checkout.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    action: 'save_address',
                    nombre_receptor: nombre,
                    telefono: telefono,
                    direccion: direccion,
                    referencia: ref,
                    lat: lat,
                    lng: lng,
                    csrf_token: document.querySelector('meta[name="csrf-token"]').content
                })
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    // Cerrar modal y recargar
                    const modalEl = document.getElementById('modalDireccion');
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    modal.hide();
                    
                    // Limpiar form
                    document.getElementById('form-direccion').reset();
                    
                    cargarDirecciones(); 
                } else {
                    Notiflix.Notify.failure(data.message);
                }
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }

        // --- LOGICA MENSAJE ESTIMADO ---
     /*   function getEstimatedDeliveryMessage(type) {
            const now = new Date();
            const day = now.getDay(); // 0: Dom, 1: Lun, ..., 6: Sab
            const hour = now.getHours();
            const min = now.getMinutes();
            const timeVal = hour * 100 + min;

            const isWeekday = (day >= 1 && day <= 5);

            console.log('Hora real JS:', hour + ':' + min, 'timeVal:', timeVal);

            if (type === 'retiro_tienda') {
                const isWithinPickup = isWeekday && timeVal >= 900 && timeVal < 1900;

                if (!isWithinPickup) {
                    return "Tu compra estará disponible para retiro en tienda dentro del horario de atención: Lunes a Viernes de 9:00 AM a 7:00 PM.";
                }
                return "";
            }

            if (type === 'delivery') {
                if (!isWeekday) {
                    return "Tu pedido será enviado el lunes a partir de las 8:00 AM.";
                }

                if (timeVal < 800) {
                    return "Tu pedido será enviado hoy a partir de las 8:00 AM.";
                }

                if ((timeVal >= 800 && timeVal <= 1130) || (timeVal >= 1400 && timeVal < 1800)) {
                    return "Tu pedido será enviado en un período aproximado de 20 minutos.";
                }

                if (timeVal > 1130 && timeVal < 1400) {
                    return "Tu pedido será enviado hoy a partir de las 2:00 PM.";
                }

                return day === 5
                    ? "Tu pedido será enviado el lunes a partir de las 8:00 AM."
                    : "Tu pedido será enviado mañana a partir de las 8:00 AM.";
            }

            return "";
        }
*/

            // --- LOGICA MENSAJE ESTIMADO ---
            function getEstimatedDeliveryMessage(type) {
                const now = new Date();
                const day = now.getDay(); // 0: Dom, 1: Lun, ..., 6: Sab
                const hour = now.getHours();
                const min = now.getMinutes();
                const timeVal = hour * 100 + min;

                const isWeekday = (day >= 1 && day <= 5);
                const isSaturday = (day === 6);

                console.log('Hora real JS:', hour + ':' + min, 'timeVal:', timeVal);

                // --- RETIRO EN TIENDA (solo Lunes a Viernes) ---
                if (type === 'retiro_tienda') {
                    const isWithinPickup = isWeekday && timeVal >= 900 && timeVal < 1900;

                    if (!isWithinPickup) {
                        return "Tu compra estará disponible para retiro en tienda dentro del horario de atención: Lunes a Viernes de 9:00 AM a 7:00 PM.";
                    }
                    return "";
                }

                // --- DELIVERY (Lunes a Sábado) ---
                if (type === 'delivery') {

                    // Domingo: no se trabaja
                    if (day === 0) {
                        return "Tu pedido será enviado el lunes a partir de las 8:00 AM.";
                    }

                    // Antes de las 8 AM
                    if (timeVal < 800) {
                        return "Tu pedido será enviado hoy a partir de las 8:00 AM.";
                    }

                    // Ventanas de despacho
                    if ((timeVal >= 800 && timeVal <= 1130) || (timeVal >= 1400 && timeVal < 1800)) {
                        return "Tu pedido será enviado en un período aproximado de 20 minutos.";
                    }

                    // Pausa de mediodía
                    if (timeVal > 1130 && timeVal < 1400) {
                        return "Tu pedido será enviado hoy a partir de las 2:00 PM.";
                    }

                    // Fuera de horario
                    if (timeVal >= 1800) {
                        if (isSaturday) {
                            return "Tu pedido será enviado el lunes a partir de las 8:00 AM.";
                        }
                        return "Tu pedido será enviado mañana a partir de las 8:00 AM.";
                    }
                }

                return "";
            }
        // --- FINALIZAR COMPRA ---
        function finalizarCompra() {
            // Validacion de Pago
            const ref = document.getElementById('payment-ref').value;
            const date = document.getElementById('payment-date').value;
            if(!ref || !date) {
                Notiflix.Notify.failure('Por favor completa los datos del pago (Referencia y Fecha)');
                return;
            }

            const btn = document.getElementById('btn-finish');
            btn.disabled = true; 
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
            
            const carritoObj = {};
            cartItems.forEach(i => carritoObj[i.id] = i);

            const payload = {
                carrito: cartItems, 
                address_id: deliveryType === 'delivery' ? selectedAddressId : null,
                payment_ref: document.getElementById('payment-ref').value + ' | ' + document.getElementById('payment-date').value,
                tipo_entrega: deliveryType,
                csrf_token: document.querySelector('meta[name="csrf-token"]').content
            };

            fetch('checkout.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(payload)
            })
            .then(async res => {
                const text = await res.text();

                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Respuesta no es JSON:\n' + text);
                }
            })
            .then(async data => {
                if(data.success) {
                    await db.carritoActivo.clear();
                    await db.cart_meta.delete('last_updated');
                    
                    document.getElementById('success-order-id').textContent = data.orden_id;
                    
                    // Mostrar mensaje estimado
                    const msg = getEstimatedDeliveryMessage(deliveryType);
                    if (msg) {
                        document.getElementById('estimated-time-msg').textContent = msg;
                        document.getElementById('estimated-time-container').classList.remove('d-none');
                    } else {
                        document.getElementById('estimated-time-container').classList.add('d-none');
                    }
                    
                    // Mostrar información de puntos si está disponible
                 if (data.puntos_ganados !== undefined) {
    const puntosInfo = document.createElement('div');
    const puntosRestantes = (10 - data.puntos_totales).toFixed(2);

    puntosInfo.className = 'alert alert-success mt-3 puntos-fidelidad';
    puntosInfo.innerHTML = `
        <h6 class="fw-bold mb-2"><i class="bi bi-trophy-fill me-2"></i>¡Puntos de Fidelidad!</h6>
        <p class="mb-1"><strong>Ganaste:</strong> ${data.puntos_ganados} puntos</p>
        <p class="mb-1"><strong>Total acumulado:</strong> ${data.puntos_totales} puntos</p>
        <p class="mb-1"><strong>Puntos faltantes para subir de nivel:</strong> ${puntosRestantes} puntos</p>
        <p class="mb-0"><strong>Nivel actual:</strong> ${data.nivel_actual}</p>
        ${data.subio_nivel ? `
            <div class="subio-nivel mt-3">
                <i class="bi bi-stars me-2"></i>🎉 ¡Subiste de nivel!
                <div class="barra-progreso-container mt-2">
                    <div class="barra-progreso"></div>
                </div>
            </div>
        ` : ''}
    `;

    document.getElementById('step-success').appendChild(puntosInfo);

    if (data.subio_nivel) {
        const nivelBadge = puntosInfo.querySelector('.subio-nivel');
        const barra = nivelBadge.querySelector('.barra-progreso');

        // Animación de barra de progreso
        setTimeout(() => {
            barra.style.width = '100%';
        }, 100);

        // Efecto pulso
        nivelBadge.classList.add('animate-subio-nivel');

        // Confeti simple
        lanzarConfeti();
    }
}


                    document.querySelectorAll('.wizard-step').forEach(el => el.classList.remove('active'));
                    document.getElementById('step-success').classList.add('active');
                    document.querySelector('.step-indicator').style.display = 'none';
                } else {
                    Notiflix.Notify.failure(data.message);
                    btn.disabled = false;
                    btn.innerHTML = 'Confirmar Pedido';
                }
            })
           .catch(err => {
                console.error('ERROR COMPLETO:', err.message || err);
                btn.disabled = false;
                btn.innerHTML = 'Confirmar Pedido';
            });
        }


        // --- LOGICA DELIVERY MSG ---
        function setDeliveryType(type) {
            deliveryType = type;
            
            // UI Updates
            document.querySelectorAll('.delivery-option').forEach(el => {
                el.classList.remove('selected', 'border-success');
                el.querySelector('.check-icon').classList.add('d-none');
            });
            
            const selectedEl = type === 'delivery' ? document.getElementById('opt-delivery') : document.getElementById('opt-pickup');
            selectedEl.classList.add('selected', 'border-success');
            selectedEl.querySelector('.check-icon').classList.remove('d-none');

            // Show/Hide Address
            if(type === 'delivery') {
                document.getElementById('delivery-address-section').classList.remove('d-none');
                document.getElementById('pickup-info-section').classList.add('d-none');
                // Habilitar boton next solo si ya hay address seleccionada o validar después
                document.getElementById('btn-next-1').disabled = (!selectedAddressId); 
            } else {
                document.getElementById('delivery-address-section').classList.add('d-none');
                document.getElementById('pickup-info-section').classList.remove('d-none');
                document.getElementById('btn-next-1').disabled = false; // Siempre puede avanzar en pickup
            }
            // Actualizar el resumen para que se vea reflejado el costo de envío
            actualizarResumen();
        }

        function renderPaymentSummary() {
            let subtotalUsd=0, totalBs=0, totalCop=0;
            let itemsHtml = '';

            // Calcular
            cartItems.forEach(item => {
                 subtotalUsd += item.price * item.qty;
                 totalBs += item.priceBolivar * item.qty;
                 totalCop += item.pricePeso * item.qty;
                 
                 itemsHtml += `<li class="list-group-item d-flex justify-content-between lh-sm bg-transparent">
                    <div><span class="fw-bold">${item.name}</span> <small class="text-muted">x${item.qty}</small></div>
                    <span class="text-muted">$${(item.price*item.qty).toFixed(2)}</span>
                 </li>`;
            });
            
            // Envío
            let shippingCost = 0;
            if(deliveryType === 'delivery') {
                shippingCost = (subtotalUsd > 35) ? 0 : 2;
            }

            let totalUsd = subtotalUsd + shippingCost;

            // Update DOM Elements Step 2
            document.getElementById('pay-subtotal').textContent = '$' + subtotalUsd.toFixed(2);
            document.getElementById('pay-shipping').textContent = shippingCost === 0 ? 'GRATIS' : '$' + shippingCost.toFixed(2);
            document.getElementById('pay-total-usd').textContent = '$' + totalUsd.toFixed(2);
            document.getElementById('pay-total-bs').textContent = totalBs.toFixed(2) + ' Bs';
            
            document.getElementById('cart-items-payment').innerHTML = itemsHtml;
        }


        // validar telefono
         const telefonoInput = document.getElementById('addr-phone');

        telefonoInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 11) {
                this.value = this.value.slice(0, 11);
            }
        });

        // --- COPIAR AL PORTAPAPELES ---
        function copyToClipboard(text, label) {
            if (!navigator.clipboard) {
                // Fallback para navegadores antiguos
                const textArea = document.createElement("textarea");
                textArea.value = text;
                document.body.appendChild(textArea);
                textArea.select();
                try {
                    document.execCommand('copy');
                    Notiflix.Notify.success(`${label} copiado al portapapeles`);
                } catch (err) {
                    Notiflix.Notify.failure('Error al copiar');
                }
                document.body.removeChild(textArea);
                return;
            }
            
            navigator.clipboard.writeText(text).then(() => {
                Notiflix.Notify.success(`${label} copiado al portapapeles`);
            }).catch(err => {
                Notiflix.Notify.failure('Error al copiar: ' + err);
            });
        }
    </script>
    <?php endif; ?>
    
    <!-- Chat Component (Simplified) -->
    <?php include 'assets/components/chat-button.php'; ?>
    <script src="assets/js/chat-simple.js"></script>
</body>
</html>
