<?php

namespace Model\Services;

use DateTime;
use Model\Middlewares\AuthMW;
use Model\Services\DataAccess;
use Model\Utilities\Blasphemy;
use Model\Utilities\CodeGenerator;
use Model\Utilities\Log;

class Manager
{
    /////////////////////////////////////////////////////////////
    #region - - - PUBLIC

    ///////////////////////////////////////////////////////////// GET
    public static function GetAllEntities($request, $response)
    {
        $type = $_GET['entidad'];
        $data = DataAccess::Select($type);
        
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

        return self::ReturnResponse($request, $response, $data);
    }

    public static function GetOrdersByCode($request, $response)
    {
        $_req = $request->getQueryParams();
        $code = $_req['codigo'];
        $productos = DataAccess::Select('productos');
        $rol = AuthMW::GetRole($_req['token']);
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

            $bit = Blasphemy::AssocArrayInsertAt($bit, 'producto', $nombreProducto, 7);
        }

        return self::ReturnResponse($request, $response, $data);
    }

    public static function GetAllOrders($request, $response)
    {        
        $_req = $request->getQueryParams();        
        $productos = DataAccess::Select('productos');
        $rol = AuthMW::GetRole($_req['token']);
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
            $data = DataAccess::SelectWhere('pedidos', null, ['tipoProducto'], [$productType]);
        }
        else
        {
            $data = DataAccess::Select('pedidos', null);
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

            $bit = Blasphemy::AssocArrayInsertAt($bit, 'producto', $nombreProducto, 7);
        }

        return self::ReturnResponse($request, $response, $data);
    }

    public static function GetUsersByRole($request, $response)
    {
        $data = DataAccess::SelectWithJoin(
            'usuarios', // tabla 1
            'tipo_usuario', // tabla 2
            'tipo', // col de tabla 1
            'codigo', // col de tabla 2
            'tipo', $_GET['buscar'], // donde col de tabla 2 = 'buscar'
            ['usuarios.user', 'tipo_usuario.tipo', 'usuarios.alta', 'usuarios.baja'] // datos a traer
        );
        return self::ReturnResponse($request, $response, $data ? $data : "No se encontraron usuarios de ese tipo");
    }

    ///////////////////////////////////////////////////////////// PUT
    public static function UpdateEntity($request, $response)
    {
        $_PUT = file_get_contents("php://input");        
        $_PUT = json_decode($_PUT, true);
        $table = $_PUT['objeto'];
        $columns = $_PUT['col'];
        $values = $_PUT['val'];

        $data = DataAccess::Update($table, $columns, $values, $_PUT['where'], $_PUT['value']);
        return self::ReturnResponse($request, $response, $data ? "Actualizacion exitosa." : "Error en la actualizacion.");
    }

    public static function UpdateOrder($request, $response)
    {
        $params = $request->getParsedBody();
        $state = $params['estado'];
        $id = $params['id'];
        
        $data = DataAccess::Update('pedidos', ['estado'], [$state], 'id', $id);
        return self::ReturnResponse($request, $response, $data ? "Actualizacion exitosa." : "Error en la actualizacion.");
    }
    
    ///////////////////////////////////////////////////////////// POST
    public static function CreateEmployee($request, $response)
    {
        $params = $request->getParsedBody();
        $table = 'usuarios';
        $columns = $params['col'];
        $values = $params['val'];
        $id = self::GetID($table);
        $id += 1;

        $date = date("Y-m-d");

        array_push($columns, 'id', 'alta');
        array_push($values, $id, $date);

        if(count(array_diff(ENTITIES['User'], $columns)) == 0) // Comparo si al menos los elementos obligatorios estan dentro de los parametros.
        {
            $data = DataAccess::Insert($table, $columns, $values);
            return self::ReturnResponse($request, $response, $data ? "Entidad creada con éxito." : "Error en la interacción con la base de datos");
        }
        else
        {            
            return self::ReturnResponse($request, $response, "Error interno.");
        }
    }

    public static function CreateTable($request, $response)
    {
        $table = 'mesas';
        $id = self::GetID($table);
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

        array_push($columns, 'id', 'codigo_mesa');
        array_push($values, $id, $code);        

        if(count(array_diff(ENTITIES['Table'], $columns)) == 0)
        {
            $data = DataAccess::Insert($table, $columns, $values);
            return self::ReturnResponse($request, $response, $data ? "Entidad creada con éxito." : "Error en la interacción con la base de datos");
        }
        else
        {
            return self::ReturnResponse($request, $response, "Error interno.");
        }
    }

    public static function CreateProduct($request, $response)
    {
        $params = $request->getParsedBody();
        $table = 'productos';
        $columns = $params['col'];
        $values = $params['val'];
        $id = self::GetID($table);
        $id += 1;

        $date = date("Y-m-d");
        array_push($columns, 'id', 'fechaAlta');
        array_push($values, $id, $date);

        if(count(array_diff(ENTITIES['Product'], $columns)) == 0)
        {
            $data = DataAccess::Insert($table, $columns, $values);
            return self::ReturnResponse($request, $response, $data ? "Entidad creada con éxito." : "Error en la interacción con la base de datos");
        }
        else
        {
            return self::ReturnResponse($request, $response, "Error interno.");
        }
    }

    public static function CreateOrder($request, $response)
    {
        $params = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        $table = 'pedidos';
        $idMesa = $params['idMesa'];
        $idProductos = json_decode($params['idProductos']);
        $cantidadProductos = json_decode($params['cantidadProductos']);
        $tipoProductos = [];
        $nombreCliente = $params['nombreCliente'];
        $id = self::GetID($table);        

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
        
        foreach($idProductos as $product)
        {
            $pdo = DataAccess::$pdo;
            $query = "SELECT tipo_producto.tipo FROM productos JOIN tipo_producto ON productos.tipo = tipo_producto.codigo WHERE productos.id = $product";            
            $statement = $pdo->prepare($query);
            $statement->execute();
            $productType = $statement->fetch()['tipo'];            
            array_push($tipoProductos, $productType);
        }
        
        $date = date("Y-m-d H:i:s");

        // ----------------------- ORDER CREATION

        for( $i = 0; $i < count($idProductos); $i ++)
        {
            $id++;
            $columns = ['id', 'codigoPedido', 'idMesa',	'idProducto', 'cantidadProducto', 'tipoProducto', 'nombreCliente', 'estado', 'fecha'];
            $values = [$id, $code, $idMesa, $idProductos[$i], $cantidadProductos[$i], $tipoProductos[$i], $nombreCliente, 1, $date];
            if(!is_dir('./img'))
            {
                mkdir('./img', 0777, true);
            }
            if (isset($uploadedFiles['fotoMesa']))
            {            
                $targetPath = './img/' . date_format(new DateTime(), 'Y-m-d_H-i-s') . '_' . $nombreCliente . '_Mesa_' . $id . '.jpg';
                $uploadedFiles['fotoMesa']->moveTo($targetPath);                
                $columns['fotoMesa'] = $targetPath;
            }
            $data = DataAccess::Insert($table, $columns, $values);
            if(!$data)
            {
                return self::ReturnResponse($request, $response, "Error en la interacción con la base de datos.");        
            }
        }        

        return self::ReturnResponse($request, $response, "Entidad creada con éxito.");
    }
    #endregion
    /////////////////////////////////////////////////////////////
    #region - - - PRIVATE
    private static function GetProductArea()
    {
        
    }

    private static function GetID($table)
    {
        $id = 0;
        $lastID = DataAccess::SelectLast($table, 'id');
        
        if($lastID)
        {
            $id = $lastID;
        }
        return $id;
    }

    private static function ReturnResponse($request, $response, $payload)
    {
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
    #endregion
}