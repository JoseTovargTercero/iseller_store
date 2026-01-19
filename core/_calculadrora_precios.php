<?php
class CalculadoraPrecios
{
    private float $pesoDolar;
    private float $peso_bolivar;
    private float $dolarBolivar;
    private float $bolivar_peso;
    private float $bcv;
    private array $data_monedas;

    public function __construct(
        float $pesoDolar,
        float $peso_bolivar,
        float $dolarBolivar,
        float $bolivar_peso,
        float $bcv,
        array $data_monedas
    ) {
        $this->pesoDolar = $pesoDolar;
        $this->peso_bolivar = $peso_bolivar;
        $this->dolarBolivar = $dolarBolivar;
        $this->bolivar_peso = $bolivar_peso;
        $this->bcv = $bcv;
        $this->data_monedas = $data_monedas;
    }

    public function calcularPrecios(array $producto): array
    {
        $cantidadUnidad = (float) $producto["cantidad_unidades"];
        $origen = $producto["origen"];
        $precioCompra = (float) $producto["precio_compra"];
        $porcentaje = (float) $producto["porcentaje"];
        $mayor = $producto["mayor"];
        $cantidad_por_precio = ($mayor == '1' ? 1 : $cantidadUnidad);

        // Precio en dólares por unidad

        $precioDolarCompra = $precioCompra / $cantidad_por_precio;


        // Precio de venta en dólares
        $precioDolarVenta = $precioDolarCompra + ($precioDolarCompra * $porcentaje / 100);
        $precioDolarVisible = number_format($precioDolarVenta, 2, '.', ',');

        // Precio en pesos (dólar → pesos)
        $precioPesoVenta = $this->formatPeso($precioDolarVenta * $this->pesoDolar);

        // Precio en bolívares según el origen
        if ($origen === 'c') {
            $precioBsVenta = ($precioPesoVenta / $this->peso_bolivar) / 1000;
        } else {
            $precioBsVenta = $precioDolarVenta * $this->dolarBolivar;
        }

        // Bolívares → pesos
        $precioBolivarPeso = $this->formatPeso(($precioBsVenta * $this->bolivar_peso) * 1000);

        // Bolívares → dólares
        $precio_bolivar_dolar = $precioBsVenta / $this->bcv;

        // Determinar precio visible en pesos // precio_peso_visible es el resultado de multiplicar los dolares * la tasa de pesos

        $valorPesos = isset($this->data_monedas['precio_peso_visible']) ||
            $origen == 'c' ? $precioPesoVenta : $precioBolivarPeso;





        // Determinar precio visible en dólares
        $precioDolar = isset($this->data_monedas['precio_dolar_visible']) ? $precioDolarVisible : number_format($precio_bolivar_dolar, 2, '.', ',');

        return [
            'precio_venta_dolar' => (float) $precioDolar,
            'precio_venta_bs' => (float) $precioBsVenta,
            'precio_venta_peso' => (float) $valorPesos
        ];
    }

    public function convertirMonto($monto, $monedaOrigen, $origen)
    {
        $resultado = [
            'usd' => 0,
            'bs' => 0,
            'cop' => 0
        ];

        // Validación rápida
        if (!in_array($monedaOrigen, ['bs', 'usd', 'cop'])) {
            return $resultado;
        } // si no exta se retorna el array con valores 0

        // Caso 1 - Si la moneda origen es dolar y el producto es venezonlano
        if ($monedaOrigen === 'usd' && $origen != 'c') {

            // ** Precio en dólares
            $dolares = $monto; // Monto en dólares

            $resultado['usd'] = round($dolares, 2);



            // ** Precio en bolívares (dólar → bolívares)
            $bolivares = $monto * $this->dolarBolivar;
            $resultado['bs'] = round($bolivares, 2);



            // ** Precio en pesos (dólar → pesos)
            $pesos = $monto * $this->pesoDolar;

            // Para los pesos depende de la configuracion
            $valorPesos = isset($this->data_monedas['precio_peso_visible'])
                ? $pesos
                : ($bolivares * $this->bolivar_peso) * 1000;

            $resultado['cop'] = $valorPesos;
        } // REVISADO

        // Caso 2 - Si la moneda origen es dolares y el producto es colombiano
        if ($monedaOrigen === 'usd' && $origen === 'c') {

            // ** Precio en pesos (dólar → pesos)
            $pesos = $monto * $this->pesoDolar;
            $resultado['cop'] = $pesos;

            // ** Precio en bolívares (dólar → bolívares)
            $bolivares = ($pesos / $this->peso_bolivar) / 1000;
            $resultado['bs'] = round($bolivares, 2);

            // ** Precio en dólares
            $resultado['usd'] = round($monto, 2);
        } // REVISADO

        // Caso 3 - Si la moneda origen es bolívares y el producto es venezolano
        if ($monedaOrigen === 'bs' && $origen != 'c') {

            // ** Precio en dólares
            // Para los dolares dependiendo de la configuracion
            $valorDolares =  $monto / $this->dolarBolivar;

            $resultado['usd'] = round($valorDolares, 2);



            // ** Precio en bolívares (dólar → bolívares)
            $resultado['bs'] = round($monto, 2);



            // ** Precio en pesos (bolivar → pesos)
            $pesos = $valorDolares * $this->pesoDolar;

            // Para los pesos depende de la configuracion
            $valorPesos = isset($this->data_monedas['precio_peso_visible'])
                ? $pesos
                : ($monto * $this->bolivar_peso) * 1000;

            $resultado['cop'] = $valorPesos;
        } // REVISADO

        // Caso 4 - Si la moneda origen es bolivares y el producto es colombiano
        if ($monedaOrigen === 'bs' && $origen === 'c') { // primero se debe llevar a pesos y luego a dolares

            // ** Precio en pesos (bolivar → pesos)
            $pesos = ($monto * $this->peso_bolivar) * 1000;
            $resultado['cop'] = $pesos;


            // ** Precio en dólares
            $valorDolares = $pesos / $this->pesoDolar;
            $resultado['usd'] = round($valorDolares, 2);


            // ** Precio en bolívares (dólar → bolívares)
            $resultado['bs'] = round($monto, 2);
        } // REVISADO

        // Caso 5 - Si la moneda origen es pesos y el producto es venezolano
        if ($monedaOrigen === 'cop' && $origen != 'c') {

            if (isset($this->data_monedas['precio_bolivar_peso'])) { // TODO: HERE
                // ** Precio en bolívares (peso → bolívares)
                $bolivares = ($monto / $this->bolivar_peso) / 1000;
                $resultado['bs'] = round($bolivares, 2);

                // ** Precio en dólares
                $valorDolares = $bolivares / $this->bcv;
                $resultado['usd'] = round($valorDolares, 2);
            } else {
                // ** Precio en dólares
                $dolares = $monto / $this->pesoDolar;
                $resultado['usd'] = round($dolares, 2);

                // ** Precio en bolívares (dólar → bolívares)
                $bolivares = $dolares * $this->dolarBolivar;
                $resultado['bs'] = round($bolivares, 2);
            }

            // ** Precio en pesos
            $resultado['cop'] = round($monto, 0); // Monto en pesos ya que es la moneda de origen
        } // REVISADO

        // Caso 6 - Si la moneda origen es pesos y el producto es colombiano
        if ($monedaOrigen === 'cop' && $origen === 'c') {

            // ** Precio en pesos
            $resultado['cop'] = round($monto, 0); // Monto en pesos ya que es la moneda de origen

            // ** Precio en dólares
            $dolares = $monto / $this->pesoDolar;
            $resultado['usd'] = round($dolares, 2);

            // ** Precio en bolívares (peso → bolívares)
            $bolivares = ($monto / $this->peso_bolivar) / 1000;
            $resultado['bs'] = round($bolivares, 2);
        }
        return $resultado;
    }



    private function formatPeso(float $valor): float
    {
        if ($valor < 100) {
            return 100;
        }

        $residuo = $valor % 100;

        if ($residuo >= 50) {
            return ceil($valor / 100) * 100; // Redondear hacia la siguiente centena
        } else {
            return floor($valor / 100) * 100; // Redondear hacia abajo
        }
    }
}
