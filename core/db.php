<?php
function cargarDotEnv($ruta)
{
	// Construir rutas posibles
	$archivoEnv = rtrim($ruta, '/') . '/.env';
	$archivoAlt = rtrim($ruta, '/') . '/env';

	// Verificar cuál existe
	if (file_exists($archivoEnv)) {
		$archivo = $archivoEnv;
	} elseif (file_exists($archivoAlt)) {
		$archivo = $archivoAlt;
	} else {
		echo "Archivo .env o env no encontrado en $ruta";
		return;
	}

	$lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($lineas as $linea) {
		if (strpos(trim($linea), '#') === 0) continue; // Ignorar comentarios

		list($nombre, $valor) = explode('=', $linea, 2);
		$nombre = trim($nombre);
		$valor = trim($valor);

		// No sobrescribe variables ya definidas
		if (!isset($_ENV[$nombre])) {
			$_ENV[$nombre] = $valor;
		}
	}
}

cargarDotEnv(dirname(__DIR__) . '/../');
$usuario = $_ENV['DB_USER'];
$contrasena = $_ENV['DB_PASS'];
$baseDeDatos = $_ENV['DB_NAME'];
$baseDeDatos_store = $_ENV['DB_NAME_STORE'];


$conexion = new mysqli('localhost', $usuario, $contrasena, $baseDeDatos);
$conexion->set_charset('utf8');

if ($conexion->connect_error) {
	die('Error de conexion');
}



$conexion_store = new mysqli('localhost', $usuario, $contrasena, $baseDeDatos_store);
$conexion_store->set_charset('utf8');

if ($conexion_store->connect_error) {
	die('Error de conexion_store');
}

date_default_timezone_set('America/Manaus');
//error_reporting(0);


	$bss_id = 3;


function formatPeso($amount)
{
	$amount = (int) $amount;
	// Redondear a la centena más cercana
	$roundedAmount = round($amount / 100) * 100;

	// Convertir el número a un formato con separadores de miles
	return $roundedAmount;
}


function formatPesoVista($amount)
{
	// convierte $amount a un entero
	$amount = (int)$amount;

	// Eliminar comas y espacios, convertir a float
	$amount = floatval(str_replace(',', '', trim($amount)));

	// Redondear a la centena más cercana
	$roundedAmount = round($amount / 100) * 100;

	// Formatear con separadores de miles

	return $roundedAmount;
}
