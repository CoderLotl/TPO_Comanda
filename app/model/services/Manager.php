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

    ///////////////////////////////////////////////////////////// PUT -- GET
    public static function GetAllEntities($request, $response)
    {
        $_req = Blasphemy::GetRequest($request);
        $type = $_req['entidad'];
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
        $_req = Blasphemy::GetRequest($request);
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

            $bit = Blasphemy::AssocArrayInsertAt($bit, 'producto', $nombreProducto, 7);
        }

        if(count($data) == 0)
        {
            $data = 'No hay pedidos vigentes para mostrar.';
        }

        return self::ReturnResponse($request, $response, $data);
    }

    public static function GetAllOrders($request, $response)
    {        
        $_req = Blasphemy::GetRequest($request);
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

            $bit = Blasphemy::AssocArrayInsertAt($bit, 'producto', $nombreProducto, 7);
        }

        if(count($data) == 0)
        {
            $data = 'No hay pedidos vigentes para mostrar.';
        }

        return self::ReturnResponse($request, $response, $data);
    }

    public static function GetUsersByRole($request, $response)
    {
        $params = Blasphemy::GetRequest($request);
        $data = DataAccess::SelectWithJoin(
            'usuarios', // tabla 1
            'tipo_usuario', // tabla 2
            'tipo', // col de tabla 1
            'codigo', // col de tabla 2
            'tipo', $params['buscar'], // donde col de tabla 2 = 'buscar'
            ['usuarios.user', 'tipo_usuario.tipo', 'usuarios.alta', 'usuarios.baja'] // datos a traer
        );
        return self::ReturnResponse($request, $response, $data ? $data : "No se encontraron usuarios de ese tipo.");
    }

    public static function GetTables($request, $response)
    {
        $states = DataAccess::Select('estado_mesas');
        $data = DataAccess::Select('mesas');

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
        return self::ReturnResponse($request, $response, $data ? $data : "No se encontraron mesas.");
    }

    public static function GetProducts($request, $response)
    {        
        $data = DataAccess::Select('productos');

        foreach($data as $key => &$bit)
        {
            if($bit['fechaBaja'] != null)
            {
                unset($data[$key]);
            }
        }
        return self::ReturnResponse($request, $response, $data ? $data : "No se encontraron mesas.");
    }

    ///////////////////////////////////////////////////////////// PUT -- PUT
    
    public static function UpdateEntity($request, $response)
    {
        $_req = Blasphemy::GetRequest($request);
        $table = $_req['objeto'];
        $columns = $_req['col'];
        $values = $_req['val'];

        $data = DataAccess::Update($table, $columns, $values, $_req['where'], $_req['value']);
        return self::ReturnResponse($request, $response, $data ? "Actualizacion exitosa." : "Error en la actualizacion.");
    }

    public static function UpdateOrder($request, $response)
    {
        $params = Blasphemy::GetRequest($request);
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
        $params = Blasphemy::GetRequest($request);
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
        $params = Blasphemy::GetRequest($request);
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
        $params = Blasphemy::GetRequest($request);
        $table = 'usuarios';
        $id = self::GetID($table);
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
        else
        {
            $columns = $params['col'];
            $values = $params['val'];
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
    }

    public static function CreateTable($request, $response)
    {
        $table = 'mesas';
        $id = self::GetID($table);
        $id += 1;
        $columns = [];
        $values = [];
        $uploadedFiles = $request->getUploadedFiles();

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
        
        if(isset($uploadedFiles['csv']))
        {
            $uploadedFile = $uploadedFiles['csv'];                        
            $fileContents = $uploadedFile->getStream()->getContents();
            $lines = explode(PHP_EOL, $fileContents);            
            $columns = explode(',', $lines[0]);
            $values = explode(',', $lines[1]);           

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
        else
        {
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
    }

    public static function CreateProduct($request, $response)
    {
        $params = Blasphemy::GetRequest($request);
        $table = 'productos';
        $columns = $params['col'];
        $values = $params['val'];
        $id = self::GetID($table);
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
        else
        {
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
    }

    public static function CreateOrder($request, $response)
    {
        $params = Blasphemy::GetRequest($request);
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
            if (isset($uploadedFiles['fotoMesa']))
            {            
                if(!is_dir('./img'))
                {
                    mkdir('./img', 0777, true);
                }
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