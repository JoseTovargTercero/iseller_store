<?php
class TasasCambio
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    private function obtenerDatos($tabla, $id)
    {
        // Guard: Solo permitir tablas específicas
        $allowedTables = ['cambio', 'cambios_mostrados', 'sucursales'];
        if (!in_array($tabla, $allowedTables)) {
            return ["status" => "error", "message" => "Tabla no permitida."];
        }

        $stmt = $this->conexion->prepare("SELECT * FROM `$tabla` WHERE bss_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            return [
                "status" => "success",
                "data" => $fila
            ];
        }

        return [
            "status" => "error",
            "message" => "Información no encontrada en $tabla."
        ];
    }

    public function obtenerBcv()
    {
        $queryHist  = "SELECT id, valor, time 
        FROM cambios_bcv_historico 
        ORDER BY time DESC 
        LIMIT 1";
        $histResult = $this->conexion->query($queryHist);

        if ($histResult && $histResult->num_rows > 0) {
            $rowHist     = $histResult->fetch_assoc();
            return (float)$rowHist['valor'];
        }
        return 0;
    }

    public function obtenerCambio($id)
    {

        $resultado = $this->obtenerDatos('cambio', $id);
        $bcv = $this->obtenerBcv();

        if ($resultado['status'] === 'success') {
            $resultado['data']['bcv'] = $bcv;
        }

        return json_encode($resultado);
    }


    public function tasasMostradas($id)
    {
        $resultado = $this->obtenerDatos('cambios_mostrados', $id);

        if ($resultado['status'] === 'success' && isset($resultado['data']['tasas'])) {
            $resultado['data'] = json_decode($resultado['data']['tasas'], true); // suponiendo que está almacenado como JSON
        }

        return json_encode($resultado);
    }


    public function obtenerStockCritico($id_sucursal)
    {
        $stmt = $this->conexion->prepare("SELECT stockCritico FROM sucursales WHERE id = ?");
        $stmt->bind_param("i", $id_sucursal);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            return [
                "status" => "success",
                "data" => $fila['stockCritico']
            ];
        }


        return json_encode([
            "status" => "error",
            "message" => "Información no encontrada."
        ]);
    }


    public function actualizarTasasMostradas($id, $listaTasas)
    {
        $tasasJson = json_encode($listaTasas);

        $stmt = $this->conexion->prepare("UPDATE cambios_mostrados SET tasas = ? WHERE bss_id = ?");
        $stmt->bind_param("si", $tasasJson, $id);

        if ($stmt->execute()) {
            return json_encode([
                "status" => "success",
                "message" => "Tasas actualizadas correctamente."
            ]);
        }

        return json_encode([
            "status" => "error",
            "message" => "Error al actualizar las tasas: " . $stmt->error
        ]);
    }
}






// Iniciar
// Informacion de la tipo de cambio estandar
$bss_id = 3;
$cambio = new TasasCambio($conexion);



$respuesta = $cambio->obtenerCambio($bss_id);
$tasas = json_decode($respuesta, true); // true = array asociativo

$tasaMostradas = $cambio->tasasMostradas($bss_id);
$tasaMostradas = json_decode($tasaMostradas, true);
$data_monedas = $tasaMostradas['data'];

    $id_sucursal = 9;

$pesoDolar = $tasas['data']['pesoDolar'];
$peso_bolivar = $tasas['data']['peso_bolivar'];
$bolivar_peso = $tasas['data']['bolivar_peso'];
$dolarBolivar = $tasas['data']['DolarBolivar'];
$bsDolar = $dolarBolivar;
$bcv =  $tasas['data']['bcv'];
$tipo_tasa_bs = $tasas['data']['tipo_tasa_bs'];
$redondeo = $tasas['data']['redondeo'];
$margen_neto = $tasas['data']['margen_neto'];
// Informacion de la tipo de cambio estandar



//require_once '_tasas_gestion.php';
