<?php
namespace Model\Services;

use PDO;
use Model\Services\DataAccess;

class Statistics
{
    public static function MostUsedTable()
    {
        $pdo = DataAccess::$pdo;

        $query =    "SELECT pedidos.idMesa, mesas.codigo_mesa
                    FROM pedidos
                    JOIN mesas ON pedidos.idMesa = mesas.id;";
        $statement = $pdo->prepare($query);
        $statement->execute();

        $data = $statement->fetchAll(PDO::FETCH_ASSOC);        
        $payload = '';
        if($data)
        {
            $contadorMesas = [];
            foreach($data as $mesa)
            {
                if(!isset($contadorMesas[$mesa['codigo_mesa']]))
                {
                    $contadorMesas[$mesa['codigo_mesa']] = 0;
                }
                $contadorMesas[$mesa['codigo_mesa']]++;
            }

            $contadorMax = 0;
            $masUsada = null;
            foreach($contadorMesas as $elem => $count)
            {
                if($count > $contadorMax)
                {
                    $contadorMax = $count;
                    
                    $masUsada = $elem;
                }
            }

            $payload = "La mesa mas usada fue la mesa de codigo: {$masUsada}.";
        }
        else
        {
            $payload = 'No se uso ninguna mesa hasta ahora.';
        }

        return $payload;
    }

    public static function MostConsumedDish()
    {
        $pdo = DataAccess::$pdo;

        $query =    "SELECT pedidos.idProducto, productos.descripcion
                    FROM pedidos
                    JOIN productos ON pedidos.idProducto = productos.id;";
        $statement = $pdo->prepare($query);
        $statement->execute();

        $data = $statement->fetchAll(PDO::FETCH_ASSOC);
        $payload = '';
        if($data)
        {
            $contadorProductos = [];
            foreach($data as $producto)
            {
                if(!isset($contadorProductos[$producto['descripcion']]))
                {
                    $contadorProductos[$producto['descripcion']] = 0;
                }
                $contadorProductos[$producto['descripcion']]++;
            }

            $contadorMax = 0;
            $masConsumido = null;
            foreach($contadorProductos as $elem => $count)
            {
                if($count > $contadorMax)
                {
                    $contadorMax = $count;
                    
                    $masConsumido = $elem;
                }
            }

            $payload = "El producto mas consumido fue: {$masConsumido}.";
        }
        else
        {
            $payload = 'No hubo consumos hasta ahora.';
        }

        return $payload;
    }
}