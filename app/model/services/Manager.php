<?php

namespace Model\Services;

use DateTime;
use Model\Middlewares\AuthMW;
use Model\Services\DataAccess;
use Model\Utilities\CodeGenerator;
use Model\Utilities\Log;

class Manager
{
    /////////////////////////////////////////////////////////////
    #region - - - PUBLIC

    ///////////////////////////////////////////////////////////// GET
    public static function GetAllEntities($request, $response)
    {
        $_req = self::GetRequest($request);
        $type = $_req['entidad'];
        $data = DataAccess::Select($type);
        $csv = isset($_req['csv']) ? $_req['csv'] : null;
        
        if($type == 'usuarios')
        {
            $aux = DataAccess::Select('tipo_usuario');
            foreach($data as &$bit)
            {
                foreach($aux as $au)
                {
                    if($au['codigo'] == $bit['tipo'])
                    {
                        $bit['tipo'] = $au['tipo'];
                    }
                }
            }
        }

        return self::ReturnResponse($request, $response, $data, $csv);
    }

    public static function GetOrdersByCode($request, $response)
    {
        $_req = self::GetRequest($request);
        $token = $request->getHeaderLine('Authorization');        
        $code = $_req['codigo'];
        $csv = isset($_req['csv']) ? $_req['csv'] : null;
        $productos = DataAccess::Select('productos');
        $rol = AuthMW::GetRole($token);
        $data = null;
        $productType = null;
        
        switch($rol)
        {
            case 'cervecero':
                $productType = 'cerveza';
                break;
            case 'bartender':
                $productType = 'bebida';
                break;
            case 'cocinero':
                $productType = 'comida';
                break;
        }        

        if($productType)
        {
            $data = DataAccess::SelectWhere('pedidos', null, ['codigoPedido', 'tipoProducto'], [$code, $productType]);
        }
        else
        {
            $data = DataAccess::SelectWhere('pedidos', null, ['codigoPedido'], [$code]);
        }

        foreach($data as $key => $bit)
        {
            if($bit['fechaBaja'] != null)
            {
                unset($data[$key]);
            }
        }

        foreach($data as &$bit)
        {
            $nombreProducto = '';
            for($i = 0; $i < count($productos); $i++)
            {
                if($productos[$i]['id'] == $bit['idProducto'])
                {
                    $nombreProducto = $productos[$i]['descripcion'];
                    break;
                }
            }

            $bit = self::AssocArrayInsertAt($bit, 'producto', $nombreProducto, 7);
        }

        if(count($data) == 0)
        {
            $data = 'No hay pedidos vigentes para mostrar.';
        }

        return self::ReturnResponse($request, $response, $data, $csv);
    }

    public static function GetAllOrders($request, $response)
    {        
        $_req = self::GetRequest($request);
        $productos = DataAccess::Select('productos');
        $token = $request->getHeaderLine('Authorization');        
        $rol = AuthMW::GetRole($token);
        $data = null;
        $productType = null;
        $csv = isset($_req['csv']) ? $_req['csv'] : null;
        
        switch($rol)
        {
            case 'cervecero':
                $productType = 'cerveza';
                break;
            case 'bartender':
                $productType = 'bebida';
                break;
            case 'cocinero':
                $productType = 'comida';
                break;
        }

        if($productType)
        {
            $data = DataAccess::SelectWhere('pedidos', null, ['tipoProducto'], [$productType]);
        }
        else
        {
            $data = DataAccess::Select('pedidos', null);
        }

        foreach($data as $key => $bit)
        {
            if($bit['fechaBaja'] != '')
            {
                unset($data[$key]);
            }
        }

        foreach($data as &$bit)
        {
            $nombreProducto = '';
            for($i = 0; $i < count($productos); $i++)
            {
                if($productos[$i]['id'] == $bit['idProducto'])
                {
                    $nombreProducto = $productos[$i]['descripcion'];
                    break;
                }
            }

            $bit = self::AssocArrayInsertAt($bit, 'producto', $nombreProducto, 7);
        }

        if(count($data) == 0)
        {
            $data = 'No hay pedidos vigentes para mostrar.';
        }

        return self::ReturnResponse($request, $response, $data, $csv);
    }

    public static function GetUsersByRole($request, $response)
    {
        $params = self::GetRequest($request);
        $csv = isset($params['csv']) ? $params['csv'] : null;
        $data = DataAccess::SelectWithJoin(
            'usuarios', // tabla 1
            'tipo_usuario', // tabla 2
            'tipo', // col de tabla 1
            'codigo', // col de tabla 2
            'tipo', $params['buscar'], // donde col de tabla 2 = 'buscar'
            ['usuarios.user', 'tipo_usuario.tipo', 'usuarios.alta', 'usuarios.baja'] // datos a traer
        );
        return self::ReturnResponse($request, $response, $data ? $data : "No se encontraron usuarios de ese tipo.", $csv);
    }

    public static function GetTables($request, $response)
    {
        $states = DataAccess::Select('estado_mesas');
        $data = DataAccess::Select('mesas');
        $_req = self::GetRequest($request);
        $csv = isset($_req['csv']) ? $_req['csv'] : null;

        foreach($data as &$bit)
        {
            foreach($states as $st)
            {
                if($bit['estado'] == $st['id'])
                {
                    $bit['estado'] = $st['estado'];
                    break;
                }
                if($bit['estado'] == null)
                {
                    $bit['estado'] = 'sin estado';
                }
            }
        }
        return self::ReturnResponse($request, $response, $data ? $data : "No se encontraron mesas.", $csv);
    }

    public static function GetProducts($request, $response)
    {        
        $data = DataAccess::Select('productos');
        $_req = self::GetRequest($request);
        $csv = isset($_req['csv']) ? $_req['csv'] : null;

        foreach($data as $key => &$bit)
        {
            if($bit['fechaBaja'] != null)
            {
                unset($data[$key]);
            }
        }
        return self::ReturnResponse($request, $response, $data ? $data : "No se encontraron mesas.", $csv);
    }

    ///////////////////////////////////////////////////////////// PUT
    
    public static function UpdateEntity($request, $response)
    {
        $_req = self::GetRequest($request);
        $table = $_req['objeto'];
        $columns = $_req['col'];
        $values = $_req['val'];

        $data = DataAccess::Update($table, $columns, $values, $_req['where'], $_req['value']);
        return self::ReturnResponse($request, $response, $data ? "Actualizacion exitosa." : "Error en la actualizacion.");
    }

    public static function UpdateOrder($request, $response)
    {
        $params = self::GetRequest($request);
        $id = $params['id'];
        $columns = [];
        $values = [];
        foreach($params as $key => $val)
        {
            if(in_array($key, ['estado', 'tiempoEstimado', 'tiempoInicio', 'tiempoEntregado', 'fechaBaja']))
            {
                if($val != '')
                {
                    array_push($columns, $key);                
                    array_push($values, $val);
                }
            }
        }       
        
        $data = DataAccess::Update('pedidos', $columns, $values, 'id', $id);
        return self::ReturnResponse($request, $response, $data ? "Actualizacion exitosa." : "Error en la actualizacion.");
    }

    public static function UpdateTable($request, $response)
    {
        $params = self::GetRequest($request);
        $id = $params['id'];
        $columns = [];
        $values = [];
        foreach($params as $key => $val)
        {
            if(in_array($key, ['codigoMesa', 'estado']))
            {
                if($val != '')
                {
                    array_push($columns, $key);                
                    array_push($values, $val);
                }
            }
        }       
        
        $data = DataAccess::Update('mesas', $columns, $values, 'id', $id);
        return self::ReturnResponse($request, $response, $data ? "Actualizacion exitosa." : "Error en la actualizacion.");
    }

    public static function UpdateProduct($request, $response)
    {
        $params = self::GetRequest($request);
        $id = $params['id'];
        $columns = [];
        $values = [];
        foreach($params as $key => $val)
        {
            if(in_array($key, ['descripcion', 'tipo', 'precio', 'fechaAlta', 'fechaBaja']))
            {
                if($val != '' && !($key == 'tipo' && !in_array($val, [1, 2, 3])))
                {
                    array_push($columns, $key);                
                    array_push($values, $val);
                }
            }
        }       
        
        $data = DataAccess::Update('productos', $columns, $values, 'id', $id);
        return self::ReturnResponse($request, $response, $data ? "Actualizacion exitosa." : "Error en la actualizacion.");
    }
    
    ///////////////////////////////////////////////////////////// POST
    public static function CreateEmployee($request, $response)
    {
        $params = self::GetRequest($request);
        $table = 'usuarios';
        $id = DataAccess::GetID($table);
        $id += 1;        
        $date = date("Y-m-d");
        $uploadedFiles = $request->getUploadedFiles();
        
        if(isset($uploadedFiles['csv']))
        {
            $uploadedFile = $uploadedFiles['csv'];                        
            $fileContents = $uploadedFile->getStream()->getContents();
            $lines = explode(PHP_EOL, $fileContents);            
            $columns = explode(',', $lines[0]);
            $values = explode(',', $lines[1]);
        }
        else
        {
            $columns = $params['col'];
            $values = $params['val'];            
        }
        array_push($columns, 'id', 'alta');
        array_push($values, $id, $date);
                    
        if(count(array_diff(ENTITIES['User'], $columns)) == 0) // Comparo si al menos los elementos obligatorios estan dentro de los parametros.
        {
            $data = DataAccess::Insert($table, $columns, $values);
            return self::ReturnResponse($request, $response, $data ? "Empleado creado con éxito." : "Error en la interacción con la base de datos");
        }
        else
        {            
            return self::ReturnResponse($request, $response, "Error interno.");
        }
    }

    public static function CreateTable($request, $response)
    {
        $table = 'mesas';
        $id = DataAccess::GetID($table);
        $id += 1;
        $columns = [];
        $values = [];        

        $tableCodes = DataAccess::Select('mesas', 'codigo_mesa');
        $code = '';
        if($tableCodes)
        {
            $set = true;
            do
            {
                $code = CodeGenerator::RandomAlphaNumCode();
                foreach($tableCodes as $tc)
                {
                    if($tc == $code)
                    {
                        $set = false;
                        break;
                    }
                }
            }while($set == false);
        }
        else
        {
            $code = CodeGenerator::RandomAlphaNumCode();
        }        

        array_push($columns, 'id', 'codigo_mesa', 'estado');
        array_push($values, $id, $code, 5);        

        if(count(array_diff(ENTITIES['Table'], $columns)) == 0)
        {
            $data = DataAccess::Insert($table, $columns, $values);
            return self::ReturnResponse($request, $response, $data ? "Mesa creada con éxito." : "Error en la interacción con la base de datos");
        }
        else
        {
            return self::ReturnResponse($request, $response, "Error interno.");
        }
    }

    public static function CreateProduct($request, $response)
    {
        $params = self::GetRequest($request);
        $table = 'productos';
        $id = DataAccess::GetID($table);
        $id += 1;
        $date = date("Y-m-d");
        $uploadedFiles = $request->getUploadedFiles();
        
        if(isset($uploadedFiles['csv']))
        {
            $uploadedFile = $uploadedFiles['csv'];                        
            $fileContents = $uploadedFile->getStream()->getContents();
            $lines = explode(PHP_EOL, $fileContents);            
            $columns = explode(',', $lines[0]);
            $values = explode(',', $lines[1]);
        }
        else
        {
            $columns = $params['col'];
            $values = $params['val'];
        }
        array_push($columns, 'id', 'fechaAlta');
        array_push($values, $id, $date);

        if(count(array_diff(ENTITIES['Product'], $columns)) == 0)
        {
            $data = DataAccess::Insert($table, $columns, $values);
            return self::ReturnResponse($request, $response, $data ? "Producto creada con éxito." : "Error en la interacción con la base de datos");
        }
        else
        {
            return self::ReturnResponse($request, $response, "Error interno.");
        }
    }

    public static function CreateOrder($request, $response)
    {
        $params = self::GetRequest($request);
        $uploadedFiles = $request->getUploadedFiles();
        $id = DataAccess::GetID('pedidos');
        $date = date("Y-m-d H:i:s");
        $fotoMesa = false;

        // ----------------------- ASSIGN ORDER CODE
        $currentCode = DataAccess::SelectLast('pedidos', 'codigoPedido');
        if($currentCode)
        {
            $code = CodeGenerator::RandomSequentialAlphaNumCode($currentCode);
        }
        else
        {
            $code = 'AAAA1';
        }
        // -----------------------        

        if(isset($uploadedFiles['csv']))
        {
            $uploadedFile = $uploadedFiles['csv'];                        
            $fileContents = $uploadedFile->getStream()->getContents();
            $lines = explode(PHP_EOL, $fileContents);            
            $fileColumns = explode(',', $lines[0]);
            preg_match_all('/(?:\[.*?\]|[^,\[\]]++)+/', $lines[1], $matches);
            $fileValues = $matches[0];
            
            $idMesa = $fileValues[array_search('idMesa', $fileColumns)];
            $idProductos = json_decode(trim($fileValues[array_search('idProductos', $fileColumns)], '"'));            
            $cantidadProductos = json_decode(trim($fileValues[array_search('cantidadProductos', $fileColumns)], '"'));
            $tipoProductos = [];
            $nombreCliente = $fileValues[array_search('nombreCliente', $fileColumns)];
        }
        else
        {
            $idMesa = $params['idMesa'];
            $idProductos = json_decode($params['idProductos']);
            $cantidadProductos = json_decode($params['cantidadProductos']);
            $tipoProductos = [];
            $nombreCliente = $params['nombreCliente'];
        }

        $mesa = DataAccess::SelectWhere('mesas', ['estado'], ['id'], [$idMesa])[0];
        if($mesa['estado'] != 4)
        {
            DataAccess::Update('mesas', ['estado'], [1], 'id', $idMesa); // La mesa espera pedido.

            foreach($idProductos as $product)
            {
                $pdo = DataAccess::$pdo;
                $query = "SELECT tipo_producto.tipo FROM productos JOIN tipo_producto ON productos.tipo = tipo_producto.codigo WHERE productos.id = $product";            
                $statement = $pdo->prepare($query);
                $statement->execute();
                $productType = $statement->fetch()['tipo'];            
                array_push($tipoProductos, $productType);
            }

            // ----------------------- PIC UPLOAD

            if (isset($uploadedFiles['fotoMesa']))
            {            
                if(!is_dir('./img'))
                {
                    mkdir('./img', 0777, true);
                }
                $targetPath = './img/' . date_format(new DateTime(), 'Y-m-d_H-i-s') . '_' . $nombreCliente . '_Mesa_' . $idMesa . '.jpg';
                $uploadedFiles['fotoMesa']->moveTo($targetPath);                
                $fotoMesa = $targetPath;
            }

            // ----------------------- ORDER CREATION

            for( $i = 0; $i < count($idProductos); $i ++)
            {
                $id++;
                $columns = ['id', 'codigoPedido', 'idMesa',	'idProducto', 'cantidadProducto', 'tipoProducto', 'nombreCliente', 'estado', 'fecha'];
                $values = [$id, $code, $idMesa, $idProductos[$i], $cantidadProductos[$i], $tipoProductos[$i], $nombreCliente, 1, $date];
                
                if($fotoMesa)
                {
                    array_push($columns, 'fotoMesa');
                    array_push($values, $fotoMesa);
                }
                
                $data = DataAccess::Insert('pedidos', $columns, $values);
                if(!$data)
                {
                    return self::ReturnResponse($request, $response, "Error en la interacción con la base de datos.");        
                }
            }        

            return self::ReturnResponse($request, $response, "Orden creada con éxito.");
        }
        else
        {
            return self::ReturnResponse($request, $response, "La mesa se encuentra cerrada. Pedido no realizado.");
        }        
    }

    public static function UploadOrderImage($request, $response)
    {
        $params = self::GetRequest($request);
        $nombreCliente = $params['cliente'];
        $idMesa = $params['idMesa'];
        $uploadedFiles = $request->getUploadedFiles();

        if (isset($uploadedFiles['fotoMesa']))
        {
            $pedidos = DataAccess::SelectWhere('pedidos', null, ['idMesa', 'nombreCliente'], [$idMesa, $nombreCliente]);

            if($pedidos)
            {
                $pedido = null;
                $fotoMesa = null;
                foreach($pedidos as $ped)
                {
                    if($ped['estado'] !=4)
                    {
                        $pedido = $ped;
                        break;
                    }
                }

                if($pedido)
                {
                    if(!is_dir('./img'))
                    {
                        mkdir('./img', 0777, true);
                    }
                    $targetPath = './img/' . date_format(new DateTime(), 'Y-m-d_H-i-s') . '_' . $nombreCliente . '_Mesa_' . $idMesa . '.jpg';
                    $uploadedFiles['fotoMesa']->moveTo($targetPath);
                    $fotoMesa = $targetPath;

                    DataAccess::UpdateMultipleWhere('pedidos', ['fotoMesa'], [$fotoMesa], ['idMesa', 'nombreCliente', 'estado'], [$idMesa, $nombreCliente, $pedido['estado']]);
                    
                    return self::ReturnResponse($request, $response, 'Imagen subida con exito.');
                }
            }

            return self::ReturnResponse($request, $response, 'Error: No hay pedidos para esa mesa o cliente.');
        }
        else
        {
            return self::ReturnResponse($request, $response, 'Error: No hay imagen.');
        }
    }

    ///////////////////////////////////////////////////////////// DELETE
    public static function CloseAllOrders($request, $response)
    {
        $params = self::GetRequest($request);

        $orders = DataAccess::SelectWhere('pedidos', null, ['codigoPedido'], [$params['codigoPedido']]);
        if($orders)
        {
            $order = $orders[0];
            DataAccess::Update('pedidos', ['estado'], [4], 'codigoPedido', $params['codigoPedido']);
            DataAccess::Update('mesas', ['estado'], [4], 'estado', $order['idMesa']);
            return self::ReturnResponse($request, $response, "Todos los pedidos de codigo {$params['codigoPedido']} han sido cerrados.");
        }
        else
        {
            return self::ReturnResponse($request, $response, 'No hay pedidos abiertos con ese codigo.');
        }
    }

    public static function DeleteEmployee($request, $response)
    {
        $params = self::GetRequest($request);
        $empleado = null;
        $empleados = DataAccess::SelectWhere('usuarios', null, ['id'], [$params['id']]);
        if($empleados)
        {
            $empleado = $empleados[0];
            if($empleado['baja'] == null)
            {
                DataAccess::Update('usuarios', ['baja'], [date_format(new DateTime(), 'Y-m-d_H-i-s')], 'id', $params['id']);
                return self::ReturnResponse($request, $response, 'Empleado dado de baja con exito.');
            }
            else
            {
                return self::ReturnResponse($request, $response, 'Error: Ese empleado ya se encuentra dado de baja.');
            }
        }
        else
        {
            return self::ReturnResponse($request, $response, 'El usuario buscado no existe.');
        }
    }

    public static function OpenTable($request, $response)
    {
        $params = self::GetRequest($request);
        $mesa = DataAccess::SelectWhere('mesas', null, ['id'], [$params['id']]);
        if($mesa)
        {
            $mesa = $mesa[0];
            DataAccess::Update('mesas', ['estado'], [5], 'id', $params['id']);
            return self::ReturnResponse($request, $response, "La mesa {$params['id']} ha sido abierta.");
        }
        else
        {
            return self::ReturnResponse($request, $response, 'La mesa buscada no existe.');
        }
    }
    #endregion
    /////////////////////////////////////////////////////////////
    #region - - - PRIVATE


    private static function ReturnResponse($request, $response, $payload, $csv = null)
    {        
        if($csv)
        {
            $csvContent = '';
            if(!empty($payload))
            {
                if(is_array($payload))
                {
                    $fields = array_keys($payload[0]);
                    $csvContent .= implode(',', $fields) . "\n";   
                    
                    foreach ($payload as $row)
                    {
                        $csvContent .= implode(',', array_values($row)) . "\n";
                    }
                }
                else
                {
                    $csvContent = $payload;
                }
            }
            else
            {
                $csvContent = 'No data available for CSV download.';
            }    
            
            $response = $response->withHeader('Content-Type', 'text/csv');
            $response = $response->withHeader('Content-Disposition', 'attachment; filename="'. "{$csv}" . '.csv"');
            $response->getBody()->write($csvContent);
    
            return $response;
        }
        else
        {
            $response->getBody()->write(json_encode(['response' => $payload]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public static function GetRequest($request)
    {
        if($_SERVER['REQUEST_METHOD'] == 'GET' || $_SERVER['REQUEST_METHOD'] == 'DELETE')
        {
            $params = $request->getQueryParams();
        }
        else
        {
            $params = $request->getParsedBody();
        }        
        
        return $params;
    }

    public static function AssocArrayInsertAt(array $assocArray, $newKey, $newValue, int $position)
    {           
        $firstPart = array_slice($assocArray, 0, $position, true);
        $secondPart = array_slice($assocArray, $position, null, true);        
        $newArray = $firstPart + [$newKey => $newValue] + $secondPart;

        return $newArray;
    }
    #endregion
}