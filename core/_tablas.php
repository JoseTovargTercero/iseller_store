<?php

class Tablas
{
    private $conexion;

    public function __construct($conexion)
    {
        $this->conexion = $conexion;
    }

    public function obtenerDatosTablas($tabla, $condiciones = [])
    {
        // Sanitize table name (only alphanumeric and underscore)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tabla)) {
            return ["status" => "error", "message" => "Nombre de tabla inválido."];
        }

        $sql = "SELECT * FROM `$tabla`";
        $tipos = "";
        $valores = [];

        if (!empty($condiciones)) {
            $campos = [];
            foreach ($condiciones as $columna => $valor) {
                // Sanitize column name
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $columna)) {
                    return ["status" => "error", "message" => "Nombre de columna inválido: $columna"];
                }
                $campos[] = "`$columna` = ?";
                $tipos .= is_int($valor) ? "i" : "s";
                $valores[] = $valor;
            }
            $sql .= " WHERE " . implode(" AND ", $campos);
        }

        $stmt = $this->conexion->prepare($sql);

        if (!empty($valores)) {
            $stmt->bind_param($tipos, ...$valores);
        }

        $stmt->execute();
        $resultado = $stmt->get_result();
        $filas = $resultado->fetch_all(MYSQLI_ASSOC);

        if (!empty($filas)) {
            return [
                "status" => "success",
                "data" => $filas
            ];
        }

        return [
            "status" => "error",
            "message" => "No se encontraron registros en $tabla."
        ];
    }
}
